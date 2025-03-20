<?php
session_start();
require_once '../database/dbConnection.php';
header('Content-Type: application/json');
try {
    $stmt = $pdo->query("SELECT TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, CRSE_TITLE FROM API_COURSES");
    $apiCourses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $apiCourses[$row['CRN']] = $row;
    }

    $stmt = $pdo->query("SELECT * FROM Courses_Table");
    $existingCourses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingCourses[$row['CRN']] = $row;
    }

    // Prepare statements
    $insertStmt = $pdo->prepare("INSERT INTO Courses_Table (CourseName, Subject_Code, Course_Number, Section, CRN, Term, Sync_Date) 
        VALUES (:CourseName, :Subject_Code, :Course_Number, :Section, :CRN, :Term, NOW())");

    $updateStmt = $pdo->prepare("UPDATE Courses_Table SET CourseName = :CourseName, Subject_Code = :Subject_Code, Course_Number = :Course_Number, 
        Section = :Section, Term = :Term, Sync_Date = NOW() WHERE CRN = :CRN");

    $deleteStmt = $pdo->prepare("DELETE FROM Courses_Table WHERE CRN = :CRN");

    $updated = 0;
    $inserted = 0;
    $deleted = 0;

    $updatedRows = [];
    $insertedRows = [];
    $deletedRows = [];

    $apiCRNs = array_keys($apiCourses);

    foreach ($apiCourses as $crn => $course) {
        $course['CRSE_TITLE'] = $course['CRSE_TITLE'] ?? 'Unknown Course';
        $course['SUBJ_CODE'] = $course['SUBJ_CODE'] ?? 'N/A';  
        $course['CRSE_NUMB'] = $course['CRSE_NUMB'] ?? '000';
        $course['SEQ_NUMB'] = $course['SEQ_NUMB'] ?? '';
        $course['TERM_CODE'] = $course['TERM_CODE'] ?? '';

        if (isset($existingCourses[$crn])) {
            $dbCourse = $existingCourses[$crn];
            if ($dbCourse['CourseName'] !== $course['CRSE_TITLE'] ||
                $dbCourse['Subject_Code'] !== $course['SUBJ_CODE'] ||
                $dbCourse['Course_Number'] !== $course['CRSE_NUMB'] ||
                $dbCourse['Section'] !== $course['SEQ_NUMB'] ||
                $dbCourse['Term'] !== $course['TERM_CODE']) {
                
                // Update existing course
                $updateStmt->execute([
                    ':CourseName' => $course['CRSE_TITLE'],
                    ':Subject_Code' => $course['SUBJ_CODE'],
                    ':Course_Number' => $course['CRSE_NUMB'],
                    ':Section' => $course['SEQ_NUMB'],
                    ':CRN' => $crn,
                    ':Term' => $course['TERM_CODE']
                ]);
                $updated++;
                $updatedRows[] = $course;
            }
        } else {
            // Insert new course
            $insertStmt->execute([
                ':CourseName' => $course['CRSE_TITLE'],
                ':Subject_Code' => $course['SUBJ_CODE'],
                ':Course_Number' => $course['CRSE_NUMB'],
                ':Section' => $course['SEQ_NUMB'],
                ':CRN' => $crn,
                ':Term' => $course['TERM_CODE']
            ]);
            $inserted++;
            $insertedRows[] = $course;
        }
    }

    foreach ($existingCourses as $crn => $course) {
        if (!in_array($crn, $apiCRNs)) {
            $deleteStmt->execute([':CRN' => $crn]);
            $deleted++;
            $deletedRows[] = $course;
        }
    }

    // Return detailed results
    return [
        'inserted' => $inserted,
        'updated' => $updated,
        'deleted' => $deleted,
        'insertedRows' => $insertedRows,
        'updatedRows' => $updatedRows,
        'deletedRows' => $deletedRows
    ];

} catch (Exception $e) {
    return [
        'error' => true,
        'message' => "Error: " . $e->getMessage()
    ];
}
?>