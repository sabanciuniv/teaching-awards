<?php

require_once '../database/dbConnection.php';
require_once './commonFunc.php';
init_session();

enforceAdminAccess($pdo);

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST["admin_username"]) || !isset($_POST["role"])) {
        echo json_encode(["status" => "error", "message" => "Invalid request data."]);
        exit();
    }

    $adminUsername = trim($_POST["admin_username"]);
    $role          = trim($_POST["role"]);
    $grantedBy     = $_SESSION['user'] ?? 'unknown';

    if (empty($adminUsername) || empty($role)) {
        echo json_encode(["status" => "error", "message" => "Both fields are required."]);
        exit();
    }

    try {
        // STEP 1) Check if there's ANY active (non-Removed) row for this username.
        // If yes, we do NOT allow re-adding to avoid duplicates.
        $checkStmt = $pdo->prepare("
            SELECT AdminID, checkRole 
            FROM Admin_Table 
            WHERE AdminSuUsername = :username
              AND checkRole != 'Removed'
            LIMIT 1
        ");
        $checkStmt->execute([":username" => $adminUsername]);
        $activeRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($activeRow) {
            // That means there's already a row that isn't removed
            echo json_encode([
                "status"  => "error",
                "message" => "Admin username already exists and is active."
            ]);
            exit();
        }

        // STEP 2) If we get here, either no row exists, or only 'Removed' row(s) exist for that username.
        // Insert a brand new row, preserving the old records.
        $stmt = $pdo->prepare("
            INSERT INTO Admin_Table (AdminSuUsername, Role, GrantedBy, checkRole, GrantedDate)
            VALUES (:username, :role, :grantedBy, :checkRole, NOW())
        ");
        $stmt->execute([
            ":username"  => $adminUsername,
            ":role"      => $role,
            ":grantedBy" => $grantedBy,
            // If you want checkRole to literally be the role, use $role,
            // or if you want it to be 'Active', you can do 'Active' here.
            ":checkRole" => $role
        ]);

        echo json_encode([
            "status"  => "success",
            "message" => "Admin added successfully."
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            "status"  => "error",
            "message" => "Database error: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method."]);
}
?>
