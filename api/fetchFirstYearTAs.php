<?php
// Include database connection
require_once __DIR__ . '/../database/dbConnection.php';

// Set content type to JSON
header('Content-Type: application/json');
require_once 'api/errorInit.php';

try {
    // Check for first-year courses (adjusting column names as necessary)
    $term = $_GET['term'] ?? '202101';
    $query = "
        SELECT DISTINCT
            c.CourseID, 
            c.Subject_Code, 
            c.Course_Number, 
            c.CRN, 
            c.CourseName AS CourseName,
            t.id AS TAID,
            t.Name AS TAName,
            t.Mail AS TAMail
        FROM Courses_Table c
        INNER JOIN Candidate_Course_Relation r ON c.CourseID = r.CourseID
        INNER JOIN Candidate_Table t ON r.CandidateID = t.id
        WHERE t.Role = 'TA' 
        AND c.Term = :term -- Replace with actual term condition if dynamic
        AND c.Subject_Code IN ('ENG', 'MATH', 'SPS', 'IF');
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['term' => $term]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($result)) {
        echo json_encode([
            'status' => 'success',
            'data' => $result
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No first-year TAs found.'
        ]);
    }
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch data: ' . $e->getMessage()
    ]);
}
?>
