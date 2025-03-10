<?php
session_start();
require_once 'api/authMiddleware.php'; // Ensure user authentication
require_once '../database/dbConnection.php'; 

if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit();
}

// Validate and retrieve NominationID from GET request
if (!isset($_GET['nominationID']) || !is_numeric($_GET['nominationID'])) {
    http_response_code(400);
    die("Invalid request.");
}

$nominationID = intval($_GET['nominationID']);

// Fetch file details from the database
$stmt = $pdo->prepare("SELECT DocumentCodedName, DocumentOriginalName FROM documents WHERE NominationID = ?");
$stmt->execute([$nominationID]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    die("File not found.");
}

// Define the secure file path (stored outside the web root)
$uploadDir = "/var/www/html/odul/uploads/"; 
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
