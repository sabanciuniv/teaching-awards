<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: loginCAS.php");
    exit;
  }
require_once '../database/dbConnection.php';
$config = require __DIR__ . '/../config.php';

header('Content-Type: application/json');
$response = [
    "success" => true,
    "logs" => []
];

$yearResponse = file_get_contents('http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/getAcademicYear.php');
$yearData = json_decode($yearResponse, true);

if (!isset($yearData['academicYear'])) {
    echo json_encode(["success" => false, "message" => "Unable to fetch academic year."]);
    exit();
}

$academicYear = $yearData['academicYear'];

$timestamp = date("Ymd_His");
$logDir = rtrim($config['log_dir'], '/') . '/' . $academicYear;

if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);  // Create if missing
}

$logFile = $logDir . "/sync_log_$timestamp.json";

// Function to append logs
function logChanges($section, $inserted, $updated, $deleted, $insertedRows, $updatedRows, $deletedRows) {
    global $response;
    $response["logs"][] = [
        "section" => $section,
        "inserted" => $inserted ?? 0,  // Default to 0 if null
        "updated" => $updated ?? 0,
        "deleted" => $deleted ?? 0,
        "insertedRows" => $insertedRows ?? [],  // Default to empty array if null
        "updatedRows" => $updatedRows ?? [],
        "deletedRows" => $deletedRows ?? [],
        "timestamp" => date("Y-m-d H:i:s")
    ];
}

try {
    // Synchronize Courses
    $courseSyncResponse = file_get_contents('http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/synchronizeCourses.php');
    if (!$courseSyncResponse) {
        throw new Exception("Failed to fetch course synchronization data.");
    }
    $courseSyncResults = json_decode($courseSyncResponse, true);
    
    if (isset($courseSyncResults['error'])) {
        throw new Exception($courseSyncResults['message']);
    }

    logChanges(
        "Courses",
        $courseSyncResults['inserted'] ?? 0, 
        $courseSyncResults['updated'] ?? 0, 
        $courseSyncResults['deleted'] ?? 0,
        $courseSyncResults['insertedRows'] ?? [],
        $courseSyncResults['updatedRows'] ?? [],
        $courseSyncResults['deletedRows'] ?? []
    );

    // Synchronize Students
    $studentsSyncResponse = file_get_contents('http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/synchronizeStudents.php');
    if (!$studentsSyncResponse) {
        throw new Exception("Failed to fetch student synchronization data.");
    }
    $studentsSyncResults = json_decode($studentsSyncResponse, true);
    
    if (isset($studentsSyncResults['error'])) {
        throw new Exception($studentsSyncResults['message']);
    }

    logChanges(
        "Students",
        $studentsSyncResults['inserted'] ?? 0, 
        $studentsSyncResults['updated'] ?? 0, 
        $studentsSyncResults['deleted'] ?? 0,
        $studentsSyncResults['insertedRows'] ?? [],
        $studentsSyncResults['updatedRows'] ?? [],
        $studentsSyncResults['deletedRows'] ?? []
    );

    // Synchronize candidates
    $candidateSyncResponse = file_get_contents('http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/synchronizeCandidates.php');
    if (!$candidateSyncResponse) {
        throw new Exception("Failed to fetch student synchronization data.");
    }
    $candidateSyncResult = json_decode($candidateSyncResponse, true);
    
    if (isset($candidateSyncResult['error'])) {
        throw new Exception($candidateSyncResult['message']);
    }

    logChanges(
        "Candidates",
        $candidateSyncResult['inserted'] ?? 0, 
        $candidateSyncResult['updated'] ?? 0, 
        $candidateSyncResult['deleted'] ?? 0,
        $candidateSyncResult['insertedRows'] ?? [],
        $candidateSyncResult['updatedRows'] ?? [],
        $candidateSyncResult['deletedRows'] ?? []
    );


    // Synchronize Student courses
    $studentCoursesSyncResponse = file_get_contents('http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/synchronizeStudent_Courses.php');
    if (!$studentCoursesSyncResponse) {
        throw new Exception("Failed to fetch student synchronization data.");
    }
    $studentCoursesSyncResult = json_decode($studentCoursesSyncResponse, true);
    
    if (isset($studentCoursesSyncResult['error'])) {
        throw new Exception($studentCoursesSyncResult['message']);
    }

    logChanges(
        "Student Courses Relation",
        $studentCoursesSyncResult['inserted'] ?? 0, 
        $studentCoursesSyncResult['updated_to_enrolled'] ?? 0, 
        $studentCoursesSyncResult['updated_to_dropped'] ?? 0,
        $studentCoursesSyncResult['insertedRows'] ?? [],
        $studentCoursesSyncResult['updatedToEnrolledRows'] ?? [],
        $studentCoursesSyncResult['updatedToDroppedRows'] ?? []
    );       


    // Synchronize candidate courses
    $candidateCoursesSyncResponse = file_get_contents('http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/synchronizeCandidate_Courses.php');
    if (!$candidateCoursesSyncResponse) {
        throw new Exception("Failed to fetch student synchronization data.");
    }
    $candidateCoursesSyncResult = json_decode($candidateCoursesSyncResponse, true);
    
    if (isset($candidateCoursesSyncResult['error'])) {
        throw new Exception($candidateCoursesSyncResult['message']);
    }

    logChanges(
        "Candidate Courses Relation",
        $candidateCoursesSyncResult['inserted'] ?? 0, 
        $candidateCoursesSyncResult['updated_to_enrolled'] ?? 0, 
        $candidateCoursesSyncResult['updated_to_dropped'] ?? 0,
        $candidateCoursesSyncResult['insertedRows'] ?? [],
        $candidateCoursesSyncResult['updatedToEnrolledRows'] ?? [],
        $candidateCoursesSyncResult['updatedToDroppedRows'] ?? []
    );       
    

    // Synchronize student category
    $studentCategorySyncResponse = file_get_contents('http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/synchronizeStudent_Category.php');
    if (!$studentCategorySyncResponse) {
        throw new Exception("Failed to fetch student synchronization data.");
    }
    $studentCategorySyncResult = json_decode($studentCategorySyncResponse, true);
    
    if (isset($studentCategorySyncResult['error'])) {
        throw new Exception($studentCategorySyncResult['message']);
    }

    logChanges(
        "Student Category Relation",
        $studentCategorySyncResult['inserted'] ?? 0, 
        $studentCategorySyncResult['updated_to_enrolled'] ?? 0, 
        $studentCategorySyncResult['updated_to_dropped'] ?? 0,
        $studentCategorySyncResult['insertedRows'] ?? [],
        $studentCategorySyncResult['updatedToEnrolledRows'] ?? [],
        $studentCategorySyncResult['updatedToDroppedRows'] ?? []
    );       


    // Write the logs to a JSON file
    file_put_contents($logFile, json_encode($response["logs"], JSON_PRETTY_PRINT));
    chmod($logFile, 0777);

    // Log into Sync_Logs table
    if (isset($_SESSION['user'])) {
        $username = $_SESSION['user'];
        $filename = basename($logFile); 

        $logInsertStmt = $pdo->prepare("INSERT INTO Sync_Logs (user, filename, academicYear) VALUES (:user, :filename, :year)");
        $logInsertStmt->execute([
            ':user' => $username,
            ':filename' => $filename,
            ':year' => $academicYear
        ]);
    }

    $response["logFilePath"] = $logFile;
    
    $response["message"] = "All synchronizations completed successfully!";

} catch (Exception $e) {
    $response["success"] = false;
    $response["message"] = "Error during synchronization: " . $e->getMessage();
}


echo json_encode($response);
exit();
?>
