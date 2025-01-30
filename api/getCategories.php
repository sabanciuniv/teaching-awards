<?php
session_start();
require_once __DIR__ . '/../database/dbConnection.php'; 

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not established.");
    }

    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        exit();
    }

    $username = $_SESSION['user'];

    // Get StudentID using SuNET_Username
    $stmt = $pdo->prepare("SELECT StudentID FROM Student_Table WHERE SuNET_Username = ?");
    $stmt->execute([$username]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student || !isset($student['StudentID'])) {
        echo json_encode(["status" => "error", "message" => "User not found in Student_Table."]);
        exit();
    }

    $studentID = $student['StudentID'];

    // Fetch all categories except 'E'
    $categoryStmt = $pdo->prepare("SELECT CategoryID, CategoryCode, CategoryDescription FROM Category_Table WHERE CategoryCode != 'E'");
    $categoryStmt->execute();
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    $availableCategories = [];

    foreach ($categories as $category) {
        $categoryCode = $category['CategoryCode'];
        $voteColumn = "{$categoryCode}_Vote";

        // Check if the student has 'Not Voted' for this category
        $voteCheckStmt = $pdo->prepare("SELECT `$voteColumn` FROM Student_Table WHERE StudentID = ?");
        $voteCheckStmt->execute([$studentID]);
        $voteStatus = $voteCheckStmt->fetchColumn();

        if ($voteStatus === 'Not Voted') {
            $availableCategories[] = [
                "id" => $category['CategoryCode'],
                "name" => $category['CategoryDescription']
            ];
        }
    }

    echo json_encode(["status" => "success", "categories" => $availableCategories]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal Server Error: " . $e->getMessage()]);
}
?>
