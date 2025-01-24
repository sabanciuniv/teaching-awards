<?php
session_start();
require_once '../database/dbConnection.php';  // Include database connection

header('Content-Type: application/json');

// Check if category parameter is provided
if (!isset($_GET['category']) || empty($_GET['category'])) {
    echo json_encode(['status' => 'error', 'message' => 'Category code is required']);
    exit();
}

$categoryCode = $_GET['category'];
$term = isset($_GET['term']) ? $_GET['term'] : null;

try {
    // Fetch CategoryID from category code (A1, A2, etc.)
    $stmtCategory = $pdo->prepare("SELECT CategoryID FROM Category_Table WHERE CategoryCode = :categoryCode");
    $stmtCategory->execute(['categoryCode' => $categoryCode]);
    $category = $stmtCategory->fetch(PDO::FETCH_ASSOC);

    if (!$category) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid category code']);
        exit();
    }

    $categoryID = $category['CategoryID'];

    // Query to get instructor details based on category and term
    $query = "
        SELECT 
            i.id AS InstructorID,
            i.Name AS InstructorName,
            i.Mail AS InstructorEmail,
            i.Status,
            c.CourseName,
            c.Subject_Code,
            c.Course_Number,
            r.Term
        FROM Candidate_Table i
        INNER JOIN Candidate_Course_Relation r ON i.id = r.CandidateID
        INNER JOIN Courses_Table c ON r.CourseID = c.CourseID
        WHERE i.Role = 'Instructor' 
        AND i.Status = 'Etkin' 
        AND r.CategoryID = :categoryID
    ";

    // Add term filter if provided
    if (!empty($term)) {
        $query .= " AND r.Term = :term";
    }

    $stmt = $pdo->prepare($query);
    $params = ['categoryID' => $categoryID];
    if (!empty($term)) {
        $params['term'] = $term;
    }

    $stmt->execute($params);
    $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($instructors) {
        echo json_encode(['status' => 'success', 'data' => $instructors]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No instructors found for this category and term']);
    }

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database query failed. Please try again later.']);
}
?>
