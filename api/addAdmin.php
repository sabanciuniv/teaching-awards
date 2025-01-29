
<?php
session_start();
require_once '../database/dbConnection.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST["admin_username"]) || !isset($_POST["role"])) {
        echo json_encode(["status" => "error", "message" => "Invalid request data."]);
        exit();
    }

    $adminUsername = trim($_POST["admin_username"]);
    $role = trim($_POST["role"]);

    if (empty($adminUsername) || empty($role)) {
        echo json_encode(["status" => "error", "message" => "Both fields are required."]);
        exit();
    }

    try {
        // Check if the username already exists
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Admin_Table WHERE AdminSuUsername = :username");
        $checkStmt->execute([":username" => $adminUsername]);
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            echo json_encode(["status" => "error", "message" => "Admin username already exists."]);
            exit();
        }

        // Insert new admin into the database
        $stmt = $pdo->prepare("INSERT INTO Admin_Table (AdminSuUsername, Role) VALUES (:username, :role)");
        $stmt->execute([
            ":username" => $adminUsername,
            ":role" => $role
        ]);

        echo json_encode(["status" => "success", "message" => "Admin added successfully."]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>
