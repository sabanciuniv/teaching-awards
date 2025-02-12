<?php
session_start();
require_once __DIR__ . '/../database/dbConnection.php';
header("Content-Type: application/json");

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $adminToRemove = $_POST['delete_admin'];
    $removedBy     = $_SESSION['user'] ?? 'unknown';

    try {
        $stmt = $pdo->prepare("
            UPDATE Admin_Table
            SET checkRole  = 'Removed',
                RemovedBy  = :removedBy,
                RemovedDate = NOW()
            WHERE AdminSuUsername = :adminUsername
        ");
        $stmt->execute([
            ':removedBy'     => $removedBy,
            ':adminUsername' => $adminToRemove
        ]);

        echo json_encode(["success" => "Admin removed successfully"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "SQL Error: " . $e->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["error" => "Invalid request"]);
}
?>
