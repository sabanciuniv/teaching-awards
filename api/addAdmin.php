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
    $role          = trim($_POST["role"]);
    $grantedBy     = $_SESSION['user'] ?? 'unknown';

    if (empty($adminUsername) || empty($role)) {
        echo json_encode(["status" => "error", "message" => "Both fields are required."]);
        exit();
    }

    try {
        // STEP 1) Check if row for this username already exists (active or removed).
        $checkStmt = $pdo->prepare("
            SELECT checkRole 
            FROM Admin_Table 
            WHERE AdminSuUsername = :username
        ");
        $checkStmt->execute([":username" => $adminUsername]);
        $existingRow = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingRow) {
            // The row exists. Is it currently removed or active?
            if ($existingRow['checkRole'] !== 'Removed') {
                // That means it's still active
                echo json_encode([
                    "status"  => "error",
                    "message" => "Admin username already exists and is active."
                ]);
                exit();
            } else {
                // The row is "Removed", so let's do a real DELETE to free the username
                $deleteStmt = $pdo->prepare("
                    DELETE FROM Admin_Table 
                    WHERE AdminSuUsername = :username
                ");
                $deleteStmt->execute([":username" => $adminUsername]);
            }
        }

        // STEP 2) Now that there's no row for this username, do a fresh INSERT
        $stmt = $pdo->prepare("
            INSERT INTO Admin_Table (AdminSuUsername, Role, GrantedBy, checkRole, GrantedDate)
            VALUES (:username, :role, :grantedBy, :checkRole, NOW())
        ");
        // If you want checkRole to match the role, do that. 
        // Or if you want it to literally say 'Active', do that instead.
        $stmt->execute([
            ":username"  => $adminUsername,
            ":role"      => $role,
            ":grantedBy" => $grantedBy,
            ":checkRole" => $role  // or 'Active'
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
