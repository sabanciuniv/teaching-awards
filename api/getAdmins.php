<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../database/dbConnection.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

header("Content-Type: application/json");

try {
    $stmt = $pdo->query("
        SELECT AdminSuUsername,
               Role,
               GrantedBy,
               GrantedDate,
               RemovedBy,
               RemovedDate
        FROM Admin_Table
        ORDER BY AdminSuUsername ASC
    ");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($admins);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
