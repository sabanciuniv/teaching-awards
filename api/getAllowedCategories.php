<?php
session_start();
require_once __DIR__ . '/../database/dbConnection.php'; 

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 1) Ensure user is logged in
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        exit();
    }

    $sunet_username = $_SESSION['user'];

    // 2) Get student record from Student_Table
    $stmtStudent = $pdo->prepare("
        SELECT id, YearID
        FROM Student_Table
        WHERE SuNET_Username = :sunet_username
    ");
    $stmtStudent->execute(['sunet_username' => $sunet_username]);
    $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Student not found."]);
        exit();
    }

    $student_id = $student['id'];
    $year_id    = $student['YearID'];

    // 3) Query all categories for which the student is eligible
    //    LEFT JOIN Votes_Table to see if the student has voted in each category
    //    GROUP BY c.CategoryID ensures only one row per category
    $stmtCat = $pdo->prepare("
        SELECT 
            c.CategoryID,
            c.CategoryCode,
            c.CategoryDescription,
            CASE WHEN COUNT(vt.id) > 0 THEN 1 ELSE 0 END AS isVoted
        FROM Student_Category_Relation scr
        JOIN Category_Table c 
            ON scr.categoryID = c.CategoryID
        JOIN Student_Table s 
            ON scr.student_id = s.id
        LEFT JOIN Votes_Table vt
            ON vt.VoterID      = s.id
           AND vt.CategoryID   = c.CategoryID
           AND vt.AcademicYear = s.YearID
        WHERE s.id     = :student_id
          AND s.YearID = :year_id
        GROUP BY c.CategoryID, c.CategoryCode, c.CategoryDescription
    ");
    $stmtCat->execute([
        'student_id' => $student_id,
        'year_id'    => $year_id
    ]);

    $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    if (empty($categories)) {
        echo json_encode([
            "status"  => "success",
            "message" => "No voting categories found for this academic year."
        ]);
        exit();
    }

    // 4) Return the list of categories, each with isVoted = 0 or 1
    echo json_encode([
        "status"     => "success",
        "categories" => $categories
    ]);

} catch (PDOException $e) {
    error_log("Database error in getAllowedCategories: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database query failed."]);
}
