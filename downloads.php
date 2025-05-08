<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'api/authMiddleware.php'; // Ensure user authentication
require_once '../ENS491-492/database/dbConnection.php'; 
require_once 'api/commonFunc.php';

init_session();
// Validate and retrieve NominationID from GET request
if (!isset($_GET['nominationID']) || !is_numeric($_GET['nominationID'])) {
    http_response_code(400);
    die("Invalid request.");
}

$nominationID = intval($_GET['nominationID']);

// Fetch file details from the database
$stmt = $pdo->prepare("SELECT DocumentCodedName, DocumentOriginalName FROM AdditionalDocuments_Table WHERE NominationID = ?");
$stmt->execute([$nominationID]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    die("File not found.");
}

// Define the secure file path (stored outside the web root)
$academicYear = getCurrentAcademicYear($pdo);
$uploadDir = "/var/www/html/odul/uploads/" . $academicYear . "/";

$filePath = $uploadDir . $file['DocumentCodedName'];

if (!file_exists($filePath)) {
    http_response_code(404);
    die("File not found.");
}

// Serve the file securely
header("Content-Description: File Transfer");
header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . basename($file['DocumentOriginalName']) . "\"");
header("Content-Length: " . filesize($filePath));
header("Cache-Control: must-revalidate");
header("Pragma: public");

readfile($filePath);
exit();
?>
