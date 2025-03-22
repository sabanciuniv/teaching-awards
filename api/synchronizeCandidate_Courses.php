<?php
require_once '../database/dbConnection.php';
header('Content-Type: application/json');

$response = [
    'inserted' => 0,
    'updated' => 0,
    'deleted' => 0,
    'insertedRows' => [],
    'updatedRows' => [],
    'deletedRows' => []
];

try {
    // Load YearID mapping
    $stmt = $pdo->query("SELECT Academic_year, YearID FROM AcademicYear_Table");
    $yearMap = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $yearMap[$row['Academic_year']] = $row['YearID'];
    }

    // Load Courses into a composite key map: TERM_SUBJ_COURSE_CRN => course row
    $stmt = $pdo->query("SELECT * FROM Courses_Table");
    $courses = [];
    $courseIds = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = "{$row['Term']}_{$row['Subject_Code']}_{$row['Course_Number']}_{$row['CRN']}";
        $courses[$key] = $row;
        $courseIds[] = $row['CourseID'];
    }

    // Load Candidates
    $stmt = $pdo->query("SELECT id, SU_ID, Role, Status FROM Candidate_Table");
    $candidates = [];
    $candidateIdsById = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $candidates[$row['SU_ID']] = $row;
        $candidateIdsById[$row['id']] = $row['SU_ID'];
    }

    // Load Existing Relations
    $stmt = $pdo->query("SELECT CandidateID, CourseID FROM Candidate_Course_Relation");
    $existingRelations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingRelations["{$row['CandidateID']}_{$row['CourseID']}"] = true;
    }

    // Prepare Statements
    $insertStmt = $pdo->prepare("INSERT INTO Candidate_Course_Relation (CourseID, CandidateID, Academic_Year, CategoryID, Term)
        VALUES (:CourseID, :CandidateID, :Academic_Year, :CategoryID, :Term)");

    $updateStmt = $pdo->prepare("
        UPDATE Candidate_Course_Relation
        SET Academic_Year = :Academic_Year,
            CategoryID = :CategoryID,
            Term = :Term
        WHERE CandidateID = :CandidateID
          AND CourseID = :CourseID
          AND (
                Academic_Year != :Academic_Year OR
                CategoryID != :CategoryID OR
                Term != :Term OR
                Academic_Year IS NULL OR
                CategoryID IS NULL OR
                Term IS NULL
          )
    ");

    $deleteStmt = $pdo->prepare("DELETE FROM Candidate_Course_Relation WHERE CandidateID = :CandidateID AND CourseID = :CourseID");

    function mapCategoryID($subject, $course, $role, $status) {
        $full = "$subject $course";
        if ($role == 'Instructor' && $status == 'Etkin') {
            if (in_array($full, ['TLL 101', 'TLL 102', 'AL 102'])) return '1';
            if (in_array($full, ['SPS 101', 'SPS 102', 'MATH 101', 'MATH 102', 'IF 100', 'NS 101', 'NS 102', 'HIST 191', 'HIST 192'])) return '2';
            if (in_array($full, ['ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004'])) return '4';
            return '3';
        }
        if ($role == 'TA' && $status == 'Etkin') {
            if (in_array($full, ['CIP 101N','IF 100R', 'MATH 101R', 'MATH 102R', 'NS 101R', 'NS 102R', 'SPS 101D', 'SPS 102D'])) return '5';
        }
        return null;
    }

    $sources = ['API_INSTRUCTORS' => 'INST_ID', 'API_TAS' => 'TA_ID'];
    $validKeys = [];

    foreach ($sources as $table => $idField) {
        $stmt = $pdo->query("SELECT TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, $idField AS SU_ID FROM $table WHERE $idField IS NOT NULL");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $term = $row['TERM_CODE'];
            $crn = $row['CRN'];
            $suId = $row['SU_ID'];
            $subject = $row['SUBJ_CODE'];
            $course = $row['CRSE_NUMB'];

            $yearCode = substr($term, 0, 4);
            $academicYear = $yearMap[$yearCode] ?? null;

            $courseKey = "{$term}_{$subject}_{$course}_{$crn}";
            $courseData = $courses[$courseKey] ?? null;

            if (!$academicYear || !$courseData || !isset($candidates[$suId])) continue;

            $candidateData = $candidates[$suId];
            $key = "{$candidateData['id']}_{$courseData['CourseID']}";
            $validKeys[$key] = true;

            $categoryID = mapCategoryID($subject, $course, $candidateData['Role'], $candidateData['Status']);
            if (!$categoryID) continue;

            $logRow = [
                'SU_ID' => $suId,
                'Subject_Code' => $courseData['Subject_Code'],
                'Course_Number' => $courseData['Course_Number'],
                'CourseName' => $courseData['CourseName'],
                'CategoryID' => $categoryID,
                'Term' => $term
            ];

            if (!isset($existingRelations[$key])) {
                $insertStmt->execute([
                    ':CourseID' => $courseData['CourseID'],
                    ':CandidateID' => $candidateData['id'],
                    ':Academic_Year' => $academicYear,
                    ':CategoryID' => $categoryID,
                    ':Term' => $term
                ]);
                $response['inserted']++;
                $response['insertedRows'][] = $logRow;
            } else {
                $updateStmt->execute([
                    ':Academic_Year' => $academicYear,
                    ':CategoryID' => $categoryID,
                    ':Term' => $term,
                    ':CandidateID' => $candidateData['id'],
                    ':CourseID' => $courseData['CourseID']
                ]);
                if ($updateStmt->rowCount() > 0) {
                    $response['updated']++;
                    $response['updatedRows'][] = $logRow;
                }
            }
        }
    }

    // Delete relations where candidate is "İşten ayrıldı"
    $stmt = $pdo->query("SELECT id, SU_ID FROM Candidate_Table WHERE Status = 'İşten ayrıldı'");
    $resignedCandidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $deleteResignedStmt = $pdo->prepare("DELETE FROM Candidate_Course_Relation WHERE CandidateID = :CandidateID");
    foreach ($resignedCandidates as $candidate) {
        $stmt = $pdo->prepare("SELECT CourseID FROM Candidate_Course_Relation WHERE CandidateID = :CandidateID");
        $stmt->execute([':CandidateID' => $candidate['id']]);
        $coursesToDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($coursesToDelete)) {
            $deleteResignedStmt->execute([':CandidateID' => $candidate['id']]);
            foreach ($coursesToDelete as $cid) {
                $response['deleted']++;
                $response['deletedRows'][] = [
                    'SU_ID' => $candidate['SU_ID'],
                    'CourseID' => $cid,
                    'reason' => 'İşten ayrıldı'
                ];
            }
        }
    }

    // Delete Candidate_Course_Relation entries not found in the API sources (missing from validKeys)
    $stmt = $pdo->query("
        SELECT ccr.CandidateID, ccr.CourseID, ct.SU_ID, ct.Role, ct.Status, c.Subject_Code, c.Course_Number, c.CRN, c.Term
        FROM Candidate_Course_Relation ccr
        JOIN Candidate_Table ct ON ct.id = ccr.CandidateID
        JOIN Courses_Table c ON c.CourseID = ccr.CourseID
    ");

    $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $deleteOrphanStmt = $pdo->prepare("DELETE FROM Candidate_Course_Relation WHERE CandidateID = :CandidateID AND CourseID = :CourseID");

    foreach ($existing as $row) {
        $key = "{$row['CandidateID']}_{$row['CourseID']}";
        if (!isset($validKeys[$key])) {
            $deleteOrphanStmt->execute([
                ':CandidateID' => $row['CandidateID'],
                ':CourseID' => $row['CourseID']
            ]);

            $response['deleted']++;
            $response['deletedRows'][] = [
                'SU_ID' => $row['SU_ID'],
                'CourseID' => $row['CourseID'],
                'reason' => 'Not found in API_INSTRUCTORS or API_TAS'
            ];
        }
    }


    echo json_encode(array_merge(['status' => 'success'], $response));
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
