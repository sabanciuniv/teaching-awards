<?php
session_start();

require_once '../database/dbConnection.php';  // Include database connection
require_once 'commonFunc.php'; 

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

$suNetUsername = $_SESSION['user'];
$categoryCode = isset($_GET['category']) ? $_GET['category'] : null;

if (!$categoryCode) {
    echo json_encode(['status' => 'error', 'message' => 'Category code is required']);
    exit();
}

// Call the reusable function
$result = getInstructorsForStudent($pdo, $suNetUsername, $categoryCode);

// Return the result
echo json_encode($result);

?>