<?php
session_start();
require_once '../database/dbConnection.php';

header('Content-Type: application/json');

try {
    // Fetch data from API_COURSES (acting as API data)
    $stmt = $pdo->query("SELECT TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, CRSE_TITLE FROM API_COURSES");
    $apiCourses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $apiCourses[$row['CRN']] = $row;
    }

    // Fetch existing courses from Courses_Table
    $stmt = $pdo->query("SELECT * FROM Courses_Table");
    $existingCourses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingCourses[$row['CRN']] = $row;
    }

    // Prepare statements for insert, update, and delete
    $insertStmt = $pdo->prepare("INSERT INTO Courses_Table (CourseName, Subject_Code, Course_Number, Section, CRN, Term, Sync_Date) 
        VALUES (:CourseName, :Subject_Code, :Course_Number, :Section, :CRN, :Term, NOW())");

    $updateStmt = $pdo->prepare("UPDATE Courses_Table SET CourseName = :CourseName, Subject_Code = :Subject_Code, Course_Number = :Course_Number, 
        Section = :Section, Term = :Term, Sync_Date = NOW() WHERE CRN = :CRN");

    $deleteStmt = $pdo->prepare("DELETE FROM Courses_Table WHERE CRN = :CRN");

    $updated = 0;
    $inserted = 0;
    $deleted = 0;

    // Track CRNs that exist in API_COURSES table
    $apiCRNs = array_keys($apiCourses);

    // Compare API_COURSES with existing Courses_Table
    foreach ($apiCourses as $crn => $course) {
        // Ensure no NULL values for NOT NULL columns
        $course['CRSE_TITLE'] = $course['CRSE_TITLE'] ?? 'Unknown Course';
        $course['SUBJ_CODE'] = $course['SUBJ_CODE'] ?? 'N/A';  
        $course['CRSE_NUMB'] = $course['CRSE_NUMB'] ?? '000';
        $course['SEQ_NUMB'] = $course['SEQ_NUMB'] ?? '';
        $course['TERM_CODE'] = $course['TERM_CODE'] ?? '';

        if (isset($existingCourses[$crn])) {
            // Check if an update is needed
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
        }
    }

    // Delete courses that are in Courses_Table but NOT in API_COURSES
    foreach ($existingCourses as $crn => $course) {
        if (!in_array($crn, $apiCRNs)) {
            $deleteStmt->execute([':CRN' => $crn]);
            $deleted++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => "Synchronization completed.",
        'inserted' => $inserted,
        'updated' => $updated,
        'deleted' => $deleted
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => "Error: " . $e->getMessage()
    ]);
}
?>
