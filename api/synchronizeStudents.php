<?php
session_start();
require_once '../database/dbConnection.php';
header('Content-Type: application/json');

try {
    // Mapping API class codes to Student_Table class names
    $classMapping = [
        'SE' => 'Senior',
        'JU' => 'Junior',
        'FR' => 'Freshman',
        'SO' => 'Sophomore'
    ];

    // Build Academic Year map
    $yearStmt = $pdo->query("SELECT YearID, Academic_year FROM AcademicYear_Table");
    $academicYears = [];
    while ($row = $yearStmt->fetch(PDO::FETCH_ASSOC)) {
        $academicYears[$row['Academic_year']] = $row['YearID'];
    }


    // Fetch data from API_STUDENTS (acting as API data)
    $stmt = $pdo->query("SELECT TERM_CODE, STU_ID, STU_FIRST_NAME, STU_MI_NAME, STU_LAST_NAME, 
        STU_USERNAME, STU_EMAIL, STU_CUM_GPA_SU, STU_CLASS_CODE, STU_FACULTY_CODE, STU_PROGRAM_CODE 
        FROM API_STUDENTS");

    $apiStudents = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['STU_CLASS_CODE'] = $classMapping[$row['STU_CLASS_CODE']] ?? 'Unknown';
        $fullName = $row['STU_FIRST_NAME'];
        if (!empty($row['STU_MI_NAME'])) {
            $fullName .= ' ' . $row['STU_MI_NAME'];
        }
        $fullName .= ' ' . $row['STU_LAST_NAME'];
        $row['StudentFullName'] = trim($fullName);

        $row['SuNET_Username'] = $row['STU_USERNAME'] ?: null;
        $row['Mail'] = $row['STU_EMAIL'] ?: null;
        $row['Class'] = $row['STU_CLASS_CODE'] ?: null;
        $row['Faculty'] = $row['STU_FACULTY_CODE'] ?: null;
        $row['Department'] = $row['STU_PROGRAM_CODE'] ?: null;
        $row['CGPA'] = $row['STU_CUM_GPA_SU'] !== null ? (float) $row['STU_CUM_GPA_SU'] : null;
        $row['TermYear'] = (int)substr($row['TERM_CODE'], 0, 4);
        $row['YearID'] = $academicYears[$row['TermYear']] ?? null;

        $apiStudents[$row['STU_ID']] = $row;
    }

    // Fetch existing students from Student_Table
    $stmt = $pdo->query("SELECT StudentID, StudentFullName, SuNET_Username, Mail, Class, Faculty, Department, CGPA, YearID 
        FROM Student_Table");
    
    $existingStudents = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingStudents[$row['StudentID']] = $row;
    }


    // Prepare statements
    $insertStmt = $pdo->prepare("INSERT INTO Student_Table 
        (StudentID, YearID, StudentFullName, SuNET_Username, Mail, Class, Faculty, Department, CGPA, Sync_Date) 
        VALUES (:StudentID, :YearID, :StudentFullName, :SuNET_Username, :Mail, :Class, :Faculty, :Department, :CGPA, NOW())");

    $updateStmt = $pdo->prepare("UPDATE Student_Table SET 
        YearID = :YearID,
        StudentFullName = :StudentFullName, 
        SuNET_Username = :SuNET_Username, 
        Mail = :Mail, 
        Class = :Class, 
        Faculty = :Faculty, 
        Department = :Department, 
        CGPA = :CGPA, 
        Sync_Date = NOW() 
        WHERE StudentID = :StudentID");

    // Step 5: Sync process
    $inserted = 0;
    $updated = 0;
    $deleted = 0;

    $insertedRows = [];
    $updatedRows = [];
    $deletedRows = [];

    $allProcessedIDs = [];

    foreach ($apiStudents as $stu_id => $student) {
        $yearID = $student['YearID'];
        if (!$yearID) continue; // Skip if YearID not found

        if (isset($existingStudents[$stu_id])) {
            $existing = $existingStudents[$stu_id];

            if (
                $existing['StudentFullName'] !== $student['StudentFullName'] ||
                $existing['SuNET_Username'] !== $student['SuNET_Username'] ||
                $existing['Mail'] !== $student['Mail'] ||
                $existing['Class'] !== $student['Class'] ||
                $existing['Faculty'] !== $student['Faculty'] ||
                $existing['Department'] !== $student['Department'] ||
                (float)$existing['CGPA'] !== (float)$student['CGPA'] ||
                (int)$existing['YearID'] !== (int)$yearID
            ) {
                $updateStmt->execute([
                    ':StudentID' => $stu_id,
                    ':YearID' => $yearID,
                    ':StudentFullName' => $student['StudentFullName'],
                    ':SuNET_Username' => $student['SuNET_Username'],
                    ':Mail' => $student['Mail'],
                    ':Class' => $student['Class'],
                    ':Faculty' => $student['Faculty'],
                    ':Department' => $student['Department'],
                    ':CGPA' => $student['CGPA']
                ]);

                if ($updateStmt->rowCount() > 0) {
                    $updated++;
                    $updatedRows[] = $stu_id;
                }
            }

        } else {
            $insertStmt->execute([
                ':StudentID' => $stu_id,
                ':YearID' => $yearID,
                ':StudentFullName' => $student['StudentFullName'],
                ':SuNET_Username' => $student['SuNET_Username'],
                ':Mail' => $student['Mail'],
                ':Class' => $student['Class'],
                ':Faculty' => $student['Faculty'],
                ':Department' => $student['Department'],
                ':CGPA' => $student['CGPA']
            ]);

            if ($insertStmt->rowCount() > 0) {
                $inserted++;
                $insertedRows[] = $stu_id;
            }
        }

        $allProcessedIDs[] = $stu_id;
    }

    // Step 6: Delete students not in API
    if (!empty($allProcessedIDs)) {
        // Step 6.1: Get internal IDs of students to delete
        $placeholders = rtrim(str_repeat('?,', count($allProcessedIDs)), ',');
        $stmt = $pdo->prepare("SELECT id, StudentID FROM Student_Table WHERE StudentID NOT IN ($placeholders)");
        $stmt->execute($allProcessedIDs);
        $studentsToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        if (!empty($studentsToDelete)) {
            $studentIDs = array_column($studentsToDelete, 'StudentID');      // for Student_Table
            $studentInternalIDs = array_column($studentsToDelete, 'id');     // for related tables
    
            if (!empty($studentInternalIDs)) {
                $inPlaceholders = rtrim(str_repeat('?,', count($studentInternalIDs)), ',');
    
                // Step 6.2: Delete related rows manually (no cascade)
                $stmt = $pdo->prepare("DELETE FROM Votes_Table WHERE VoterID IN ($inPlaceholders)");
                $stmt->execute($studentInternalIDs);
    
                $stmt = $pdo->prepare("DELETE FROM Student_Course_Relation WHERE `student.id` IN ($inPlaceholders)");
                $stmt->execute($studentInternalIDs);
    
                // Step 6.3: Delete from Student_Table (CASCADE handles Student_Category_Relation)
                $placeholders = rtrim(str_repeat('?,', count($studentIDs)), ',');
                $stmt = $pdo->prepare("DELETE FROM Student_Table WHERE StudentID IN ($placeholders)");
                $stmt->execute($studentIDs);
    
                $deleted = $stmt->rowCount();
                $deletedRows = $studentIDs;
            }
        }
    }
    

    // Return detailed results
    echo json_encode([
        'status' => 'success',
        'inserted' => $inserted ?? 0,
        'updated' => $updated ?? 0,
        'deleted' => 0,  // No delete operation was performed
        'insertedRows' => $insertedRows ?? [],
        'updatedRows' => $updatedRows ?? [],
        'deletedRows' => []
    ]);
    exit(); // Ensure script execution stops after sending JSON
    

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => "Error: " . $e->getMessage()
    ]);
}
?>
