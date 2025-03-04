<?php
session_start();
require_once __DIR__ . '/../database/dbConnection.php'; 

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check if the user is logged in
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        exit();
    }

    $sunet_username = $_SESSION['user']; // SuNET_Username from session

    // Load database configuration
    $config = include(__DIR__ . '/../config.php'); 
    $dbConfig = $config['database'];

    // Database connection
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Step 1: Retrieve the student ID using SuNET_Username
    $stmt = $pdo->prepare("SELECT id FROM Student_Table WHERE SuNET_Username = :sunet_username");
    $stmt->execute(['sunet_username' => $sunet_username]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Student not found."]);
        exit();
    }

    $student_id = $student['id']; 

    // Step 2: Retrieve categories the student can vote in
    $stmt = $pdo->prepare("
        SELECT c.CategoryID, c.CategoryCode, c.CategoryDescription
        FROM Student_Category_Relation scr
        JOIN Category_Table c ON scr.categoryID = c.CategoryID
        WHERE scr.student_id = :student_id;
    ");
    $stmt->execute(['student_id' => $student_id]); 
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Step 3: Return JSON response
    if (empty($categories)) {
        echo json_encode(["status" => "success", "message" => "No voting categories found.", "student_id" => $student_id]);
    } else {
        echo json_encode(["status" => "success", "categories" => $categories]);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database query failed."]);
}
?>
