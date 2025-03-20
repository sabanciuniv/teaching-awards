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

    // Fetch data from API_STUDENTS (acting as API data)
    $stmt = $pdo->query("SELECT TERM_CODE, STU_ID, STU_FIRST_NAME, STU_MI_NAME, STU_LAST_NAME, 
        STU_USERNAME, STU_EMAIL, STU_CUM_GPA_SU, STU_CLASS_CODE 
        FROM API_STUDENTS");
    
    $apiStudents = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $row['STU_CLASS_CODE'] = $classMapping[$row['STU_CLASS_CODE']] ?? 'Unknown';
        $row['StudentFullName'] = trim($row['STU_FIRST_NAME'] . ' ' . ($row['STU_MI_NAME'] ?? '') . ' ' . $row['STU_LAST_NAME']);
        $row['SuNET_Username'] = $row['STU_USERNAME'] ?: null;
        $row['Mail'] = $row['STU_EMAIL'] ?: null;
        $row['Class'] = $row['STU_CLASS_CODE'] ?: null;
        $row['CGPA'] = $row['STU_CUM_GPA_SU'] !== null ? (float) $row['STU_CUM_GPA_SU'] : null;

        $apiStudents[$row['STU_ID']] = $row;
    }

    // Fetch existing students from Student_Table
    $stmt = $pdo->query("SELECT StudentID, StudentFullName, SuNET_Username, Mail, Class, CGPA 
        FROM Student_Table");
    
    $existingStudents = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingStudents[$row['StudentID']] = $row;
    }

    // Prepare statements
    $insertStmt = $pdo->prepare("INSERT INTO Student_Table 
        (StudentID, StudentFullName, SuNET_Username, Mail, Class, CGPA, Sync_Date) 
        VALUES (:StudentID, :StudentFullName, :SuNET_Username, :Mail, :Class, :CGPA, NOW())");

    $updateStmt = $pdo->prepare("UPDATE Student_Table SET 
        StudentFullName = :StudentFullName, 
        SuNET_Username = :SuNET_Username, 
        Mail = :Mail, 
        Class = :Class, 
        CGPA = :CGPA, 
        Sync_Date = NOW() 
        WHERE StudentID = :StudentID");

    $updated = 0;
    $inserted = 0;

    $updatedRows = [];
    $insertedRows = [];

    $apiStudentIDs = array_keys($apiStudents);

    // Insert new students and update existing students
    foreach ($apiStudents as $stu_id => $student) {
        if (isset($existingStudents[$stu_id])) {
            $existingStudent = $existingStudents[$stu_id];

            // Check if any data has changed before updating
            if ($existingStudent['StudentFullName'] != $student['StudentFullName'] ||
                $existingStudent['SuNET_Username'] != $student['SuNET_Username'] ||
                $existingStudent['Mail'] != $student['Mail'] ||
                $existingStudent['Class'] != $student['Class'] ||
                (float)$existingStudent['CGPA'] != (float)$student['CGPA']) {

                $updateStmt->execute([
                    ':StudentID' => $stu_id,
                    ':StudentFullName' => $student['StudentFullName'],
                    ':SuNET_Username' => $student['SuNET_Username'],
                    ':Mail' => $student['Mail'],
                    ':Class' => $student['Class'],
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
                ':StudentFullName' => $student['StudentFullName'],
                ':SuNET_Username' => $student['SuNET_Username'],
                ':Mail' => $student['Mail'],
                ':Class' => $student['Class'],
                ':CGPA' => $student['CGPA']
            ]);

            if ($insertStmt->rowCount() > 0) {
                $inserted++;
                $insertedRows[] = $stu_id;
            }
        }
    }

    // Return detailed results
    echo json_encode([
        'status' => 'success',
        'inserted' => $inserted,
        'updated' => $updated,
        'insertedRows' => $insertedRows,
        'updatedRows' => $updatedRows
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => "Error: " . $e->getMessage()
    ]);
}
?>
