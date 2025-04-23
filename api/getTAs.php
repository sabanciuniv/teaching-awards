<?php
require_once '../database/dbConnection.php';
require_once 'commonFunc.php';

header('Content-Type: application/json');
init_session();

$suNetUsername = $_SESSION['user'];
$categoryCode = $_GET['category'] ?? null;

if (!$categoryCode) {
    echo json_encode(['status' => 'error', 'message' => 'Category code is required']);
    exit();
}

// Call the reusable function
$response = getTAsForStudent($pdo, $suNetUsername, $categoryCode);
echo json_encode($response);
