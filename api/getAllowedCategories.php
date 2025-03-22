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

    $sunet_username = $_SESSION['user'];

    // Get student ID and YearID from Student_Table
    $stmt = $pdo->prepare("SELECT id, YearID FROM Student_Table WHERE SuNET_Username = :sunet_username");
    $stmt->execute(['sunet_username' => $sunet_username]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Student not found."]);
        exit();
    }

    $student_id = $student['id'];
    $year_id = $student['YearID'];

    // Get categories for that student in the same academic year
    $stmt = $pdo->prepare("
        SELECT c.CategoryID, c.CategoryCode, c.CategoryDescription
        FROM Student_Category_Relation scr
        JOIN Category_Table c ON scr.categoryID = c.CategoryID
        JOIN Student_Table s ON scr.student_id = s.id
        WHERE scr.student_id = :student_id
          AND s.YearID = :year_id
    ");
    $stmt->execute([
        'student_id' => $student_id,
        'year_id' => $year_id
    ]);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($categories)) {
        echo json_encode([
            "status" => "success",
            "message" => "No voting categories found for this academic year.",
            "student_id" => $student_id,
            "year_id" => $year_id
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "categories" => $categories,
            "year_id" => $year_id
        ]);
    }

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database query failed."]);
}
