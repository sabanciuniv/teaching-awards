<?php
require_once '../database/dbConnection.php';

header('Content-Type: application/json');
$response = [
    "success" => true,
    "logs" => []
];

$logFile = '/var/www/html/odul/logs/sync_log.json';

// Function to append logs
function logChanges($section, $inserted, $updated, $deleted, $insertedRows, $updatedRows, $deletedRows) {
    global $response;
    $response["logs"][] = [
        "section" => $section,
        "inserted" => $inserted,
        "updated" => $updated,
        "deleted" => $deleted,
        "insertedRows" => $insertedRows,
        "updatedRows" => $updatedRows,
        "deletedRows" => $deletedRows,
        "timestamp" => date("Y-m-d H:i:s")
    ];
}

try {
    // Synchronize Courses
    $courseSyncResults = include 'synchronizeCourses.php';
    if (!isset($courseSyncResults['error'])) {
        logChanges(
            "Courses",
            $courseSyncResults['inserted'], 
            $courseSyncResults['updated'], 
            $courseSyncResults['deleted'],
            $courseSyncResults['insertedRows'],
            $courseSyncResults['updatedRows'],
            $courseSyncResults['deletedRows']
        );
    } else {
        throw new Exception($courseSyncResults['message']);
    }

    $studentsSyncResults = include 'synchronizeStudents.php';
    if (!isset($studentsSyncResults['error'])) {
        logChanges(
            "Students",
            $studentsSyncResults['inserted'], 
            $studentsSyncResults['updated'], 
            $studentsSyncResults['deleted'],
            $studentsSyncResults['insertedRows'],
            $studentsSyncResults['updatedRows'],
            $studentsSyncResults['deletedRows']
        );
    } else {
        throw new Exception($studentsSyncResults['message']);
    }

    // Write the logs to a JSON file
    file_put_contents($logFile, json_encode($response["logs"], JSON_PRETTY_PRINT));

    $response["message"] = "All synchronizations completed successfully!";
} catch (Exception $e) {
    $response["success"] = false;
    $response["message"] = "Error during synchronization: " . $e->getMessage();
}

// Return response as JSON
echo json_encode($response);
?>
