<?php

// commonFunc.php


function deleteExcludedCandidate(PDO $pdo, int $candidateID): array {
    try {
        $stmt = $pdo->prepare("DELETE FROM Exception_Table WHERE CandidateID = :candidateID");
        $stmt->execute(['candidateID' => $candidateID]);

        return ['success' => true];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

//add to excluded candidate
function addExcludedCandidate(PDO $pdo, int $candidateID, string $excludedBy): array {
    try {
        // Check if candidate exists
        $checkStmt = $pdo->prepare("SELECT id FROM Candidate_Table WHERE id = :id");
        $checkStmt->execute([':id' => $candidateID]);

        if ($checkStmt->rowCount() === 0) {
            return ['success' => false, 'error' => "Candidate with ID $candidateID does not exist"];
        }

        // Check if candidate is already excluded
        $alreadyExcluded = $pdo->prepare("SELECT id FROM Exception_Table WHERE CandidateID = :cid");
        $alreadyExcluded->execute([':cid' => $candidateID]);

        if ($alreadyExcluded->rowCount() > 0) {
            return ['success' => false, 'error' => 'Candidate is already excluded.'];
        }

        // Insert into Exception_Table
        $insertStmt = $pdo->prepare("
            INSERT INTO Exception_Table (CandidateID, excluded_by)
            VALUES (:cid, :eby)
        ");
        $insertStmt->execute([
            ':cid' => $candidateID,
            ':eby' => $excludedBy
        ]);

        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

//add or remove system into the exception table
function handleCandidateExclusion(PDO $pdo): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidateID'], $_POST['action'])) {
        $candidateID = intval($_POST['candidateID']);
        $username = $_SESSION['user'] ?? 'system';

        if ($_POST['action'] === 'exclude') {
            $result = addExcludedCandidate($pdo, $candidateID, $username);
        } elseif ($_POST['action'] === 'unexclude') {
            $result = deleteExcludedCandidate($pdo, $candidateID);
        } else {
            $result = ['success' => false, 'error' => 'Invalid action.'];
        }

        echo json_encode($result);
        exit;
    }
}


function getAllCategories(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT CategoryID, CategoryDescription 
           FROM Category_Table 
          ORDER BY CategoryID ASC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllAcademicYears(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT YearID, Academic_year 
           FROM AcademicYear_Table 
          ORDER BY YearID DESC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//get the current academic year
function getCurrentAcademicYear(PDO $pdo): ?string {
    $stmt = $pdo->query("
        SELECT Academic_year 
        FROM AcademicYear_Table 
        ORDER BY Start_date_time DESC 
        LIMIT 1
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['Academic_year'] : null;
}

function fetchCurrentAcademicYear(PDO $pdo): ?array {
    $stmt = $pdo->query("
        SELECT YearID, Academic_year, Start_date_time, End_date_time
        FROM AcademicYear_Table
        ORDER BY Start_date_time DESC
        LIMIT 1
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}



//get the current year's ID
function getCurrentAcademicYearID(PDO $pdo): ?int {
    $stmt = $pdo->query("
        SELECT YearID 
        FROM AcademicYear_Table 
        ORDER BY Start_date_time DESC 
        LIMIT 1
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['YearID'] : null;
}

//get candidates according to current year
function getCandidatesForYear(PDO $pdo, int $yearID): array {
    $stmt = $pdo->prepare("
        SELECT c.id, c.SU_ID, c.Name, c.Mail, c.Role, c.Sync_Date, c.Status,
            GROUP_CONCAT(DISTINCT cat.CategoryCode SEPARATOR ', ') AS Categories,
            GROUP_CONCAT(DISTINCT CONCAT(co.Subject_Code, ' ', co.Course_Number) SEPARATOR ', ') AS Courses
        FROM Candidate_Table c
        LEFT JOIN Candidate_Course_Relation cc ON c.id = cc.CandidateID
        LEFT JOIN Category_Table cat ON cc.CategoryID = cat.CategoryID
        LEFT JOIN Courses_Table co ON cc.CourseID = co.CourseID
        WHERE (cc.Academic_Year = :academicYear OR cc.Academic_Year IS NULL)
        GROUP BY c.id
        ORDER BY c.Name ASC
    ");
    $stmt->bindParam(':academicYear', $yearID, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//get students according to the year
function getStudentsForYear(PDO $pdo, int $yearID): array {
    $stmt = $pdo->prepare("
        SELECT s.*, 
            GROUP_CONCAT(DISTINCT cat.CategoryCode SEPARATOR ', ') AS Categories
        FROM Student_Table s
        LEFT JOIN Student_Category_Relation scr ON s.id = scr.student_id
        LEFT JOIN Category_Table cat ON scr.categoryID = cat.CategoryID
        WHERE s.YearID = :yearID
        GROUP BY s.id
    ");
    $stmt->bindParam(':yearID', $yearID, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function checkIfUserIsAdmin(PDO $pdo, string $username): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM Admin_Table WHERE AdminSuUsername = :username AND checkRole <> 'Removed' LIMIT 1");
    $stmt->execute([':username' => $username]);
    return (bool) $stmt->fetch();
}


//get the instructors for each student
function getInstructorsForStudent(PDO $pdo, string $suNetUsername, string $categoryCode): array {
    try {
        $academicYearData = fetchCurrentAcademicYear($pdo);
        if (!$academicYearData || !isset($academicYearData['Academic_year'])) {
            return ['status' => 'error', 'message' => 'Failed to retrieve academic year'];
        }

        $academicYear = intval($academicYearData['Academic_year']);

        if ($categoryCode === 'B') {
            $validTerms = [
                ($academicYear)     . '01',
                ($academicYear)     . '02',
                ($academicYear - 1) . '01',
                ($academicYear - 1) . '02'
            ];
        } else {
            $validTerms = [
                ($academicYear) . '01',
                ($academicYear) . '02'
            ];
        }
        

        // Get student ID
        $stmtStudent = $pdo->prepare("SELECT id FROM Student_Table WHERE SuNET_Username = :suNetUsername");
        $stmtStudent->execute(['suNetUsername' => $suNetUsername]);
        $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);
        if (!$student) return ['status' => 'error', 'message' => 'Student not found'];
        $studentID = $student['id'];

        // Get enrolled course IDs
        $stmtCourses = $pdo->prepare("SELECT CourseID FROM Student_Course_Relation WHERE `student.id` = :studentID AND EnrollmentStatus = 'enrolled'");
        $stmtCourses->execute(['studentID' => $studentID]);
        $courses = $stmtCourses->fetchAll(PDO::FETCH_COLUMN);
        if (empty($courses)) return ['status' => 'error', 'message' => 'No enrolled courses found'];

        // Get category ID
        $stmtCategory = $pdo->prepare("SELECT CategoryID FROM Category_Table WHERE CategoryCode = :categoryCode");
        $stmtCategory->execute(['categoryCode' => $categoryCode]);
        $category = $stmtCategory->fetch(PDO::FETCH_ASSOC);
        if (!$category) return ['status' => 'error', 'message' => 'Invalid category code'];
        $categoryID = $category['CategoryID'];

        // Dynamically bind placeholders
        $placeholders = implode(',', array_fill(0, count($courses), '?'));

        // Query instructors and group courses with GROUP_CONCAT
        $query = "
            SELECT 
                i.id AS InstructorID,
                i.Name AS InstructorName,
                i.Mail AS InstructorEmail,
                i.Status,
                GROUP_CONCAT(DISTINCT CONCAT(c.Subject_Code, ' ', c.Course_Number) ORDER BY c.Subject_Code SEPARATOR ', ') AS Courses
            FROM Candidate_Table i
            INNER JOIN Candidate_Course_Relation r ON i.id = r.CandidateID
            INNER JOIN Courses_Table c ON r.CourseID = c.CourseID
            WHERE i.Role = 'Instructor' 
              AND i.Status = 'Etkin' 
              AND r.CategoryID = ?
              AND r.Term IN (?, ?, ?, ?)
              AND r.CourseID IN ($placeholders)
              AND NOT EXISTS (
                  SELECT 1 FROM Exception_Table e WHERE e.CandidateID = i.id
              )
            GROUP BY i.id
        ";

        $params = array_merge([$categoryID], $validTerms, $courses);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $instructors
            ? ['status' => 'success', 'data' => $instructors]
            : ['status' => 'error', 'message' => 'No instructors found'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}



?>