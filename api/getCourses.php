<?php
die("***DISABLED***".__FILE__);
// Include database connection
require_once __DIR__ . '/../database/dbConnection.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT CourseID, CourseName, Subject_Code, Course_Number, Term FROM Courses_Table");
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If no courses found, return empty array
    if (empty($courses)) {
        http_response_code(200); // OK but no content
        echo json_encode([
            'status' => 'success',
            'message' => 'No courses found',
            'data' => []
        ]);
        exit;
    }

    // Return data as JSON
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'data' => $courses
    ]);

} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch courses: ' . $e->getMessage()
    ]);
}
?>
