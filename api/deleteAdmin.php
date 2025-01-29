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

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_admin'])) {
    $adminToDelete = $_POST['delete_admin'];

    try {
        $stmt = $pdo->prepare("DELETE FROM Admin_Table WHERE AdminSuUsername = :adminUsername");
        $stmt->execute([':adminUsername' => $adminToDelete]);

        echo json_encode(["success" => "Admin deleted successfully", "redirect" => "manageAdmins.php"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request"]);
}
?>
