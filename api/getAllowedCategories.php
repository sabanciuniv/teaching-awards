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

    // 2) Determine student_id and year_id based on session (impersonated OR logged-in user)
    if (isset($_SESSION['impersonating']) && $_SESSION['impersonating']) {
        // Admin impersonating a student
        $student_id = $_SESSION['student_id'];
        $year_id    = $_SESSION['year_id'] ?? null;

        // Fallback: fetch YearID if not set
        if (!$year_id) {
            $stmtYear = $pdo->prepare("SELECT YearID FROM Student_Table WHERE id = ?");
            $stmtYear->execute([$student_id]);
            $row = $stmtYear->fetch(PDO::FETCH_ASSOC);
            $year_id = $row['YearID'] ?? null;
            $_SESSION['year_id'] = $year_id;
        }
    } else {
        // Normal student login
        $sunet_username = $_SESSION['user'];

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
    }

    // 3) Get allowed categories + vote info
    $stmtCat = $pdo->prepare("
        SELECT 
            c.CategoryID,
            c.CategoryCode,
            c.CategoryDescription,
            CASE WHEN COUNT(vt.id) > 0 THEN 1 ELSE 0 END AS isVoted
        FROM Student_Category_Relation scr
        JOIN Category_Table c ON scr.categoryID = c.CategoryID
        JOIN Student_Table s ON scr.student_id = s.id
        LEFT JOIN Votes_Table vt
            ON vt.VoterID = s.id
           AND vt.CategoryID = c.CategoryID
           AND vt.AcademicYear = s.YearID
        WHERE s.id = :student_id
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

    echo json_encode([
        "status"     => "success",
        "categories" => $categories
    ]);

} catch (PDOException $e) {
    error_log("Database error in getAllowedCategories: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database query failed."]);
}
