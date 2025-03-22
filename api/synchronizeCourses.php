<?php
session_start();
require_once '../database/dbConnection.php';
header('Content-Type: application/json');

try {
    // 1. Load all Academic Years
    $stmt = $pdo->query("SELECT YearID, Academic_year FROM AcademicYear_Table");
    $academicYears = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $academicYears[$row['Academic_year']] = $row['YearID'];
    }

    // 2. Fetch API course data
    $stmt = $pdo->query("SELECT TERM_CODE, CRN, SUBJ_CODE, CRSE_NUMB, SEQ_NUMB, CRSE_TITLE FROM API_COURSES");
    $apiCourses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $apiCourses[$row['CRN']] = $row;
    }

    // 3. Fetch existing DB courses
    $stmt = $pdo->query("SELECT * FROM Courses_Table");
    $existingCourses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingCourses[$row['CRN']] = $row;
    }

    // 4. Prepare statements
    $insertStmt = $pdo->prepare("INSERT INTO Courses_Table 
        (CourseName, Subject_Code, Course_Number, Section, CRN, Term, Sync_Date, YearID) 
        VALUES (:CourseName, :Subject_Code, :Course_Number, :Section, :CRN, :Term, NOW(), :YearID)");

    $updateStmt = $pdo->prepare("UPDATE Courses_Table 
        SET CourseName = :CourseName, Subject_Code = :Subject_Code, Course_Number = :Course_Number, 
        Section = :Section, Term = :Term, Sync_Date = NOW(), YearID = :YearID 
        WHERE CRN = :CRN");

    $deleteStmt = $pdo->prepare("DELETE FROM Courses_Table WHERE CRN = :CRN");

    $updated = 0;
    $inserted = 0;
    $deleted = 0;

    $updatedRows = [];
    $insertedRows = [];
    $deletedRows = [];

    $apiCRNs = array_keys($apiCourses);

    foreach ($apiCourses as $crn => $course) {
        // Default values
        $course['CRSE_TITLE'] = $course['CRSE_TITLE'] ?? 'Unknown Course';
        $course['SUBJ_CODE'] = $course['SUBJ_CODE'] ?? 'N/A';  
        $course['CRSE_NUMB'] = $course['CRSE_NUMB'] ?? '000';
        $course['SEQ_NUMB'] = $course['SEQ_NUMB'] ?? '';
        $course['TERM_CODE'] = $course['TERM_CODE'] ?? '';

        // Extract YearID
        $yearCode = substr($course['TERM_CODE'], 0, 4); // First 4 digits
        $yearID = $academicYears[$yearCode] ?? null;

        if (!$yearID) continue; // Skip if no matching YearID

        $fullCourse = [
            'CourseName' => $course['CRSE_TITLE'],
            'Subject_Code' => $course['SUBJ_CODE'],
            'Course_Number' => $course['CRSE_NUMB'],
            'Section' => $course['SEQ_NUMB'],
            'CRN' => $crn,
            'Term' => $course['TERM_CODE'],
            'YearID' => $yearID
        ];

        if (isset($existingCourses[$crn])) {
            $dbCourse = $existingCourses[$crn];
            if (
                $dbCourse['CourseName'] !== $course['CRSE_TITLE'] ||
                $dbCourse['Subject_Code'] !== $course['SUBJ_CODE'] ||
                $dbCourse['Course_Number'] !== $course['CRSE_NUMB'] ||
                $dbCourse['Section'] !== $course['SEQ_NUMB'] ||
                $dbCourse['Term'] !== $course['TERM_CODE'] ||
                $dbCourse['YearID'] != $yearID
            ) {
                $updateStmt->execute($fullCourse);
                $updated++;
                $updatedRows[] = $fullCourse;
            }
        } else {
            $insertStmt->execute($fullCourse);
            $inserted++;
            $insertedRows[] = $fullCourse;
        }
    }

    // 5. Delete courses not in API
    foreach ($existingCourses as $crn => $course) {
        if (!in_array($crn, $apiCRNs)) {
            $deleteStmt->execute([':CRN' => $crn]);
            $deleted++;
            $deletedRows[] = [
                'CourseName' => $course['CourseName'],
                'Subject_Code' => $course['Subject_Code'],
                'Course_Number' => $course['Course_Number'],
                'Section' => $course['Section'],
                'CRN' => $crn,
                'Term' => $course['Term'],
                'YearID' => $course['YearID']
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'inserted' => $inserted,
        'updated' => $updated,
        'deleted' => $deleted,
        'insertedRows' => $insertedRows,
        'updatedRows' => $updatedRows,
        'deletedRows' => $deletedRows
    ]);
    exit();

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => "Error: " . $e->getMessage()
    ]);
}