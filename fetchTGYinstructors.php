<?php
session_start();
require_once __DIR__ . '/database/dbConnection.php'; // Ensure database connection

header('Content-Type: application/json');
require_once 'api/errorInit.php';

try {
    if (!$pdo) {
        die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
    }

    // Ensure term parameter is provided
    if (!isset($_GET['term']) || empty($_GET['term'])) {
        echo json_encode(['status' => 'error', 'message' => 'Academic term is required']);
        exit();
    }
    $term = $_GET['term'];

    // Debugging Log
    error_log("Fetching instructors for Term: $term");

    // Simplified query fetching data from API_INSTRUCTORS
    $query = "
        SELECT DISTINCT
            i.inst_id AS CandidateID,
            CONCAT(i.INST_FIRST_NAME, ' ', IFNULL(i.INST_MI_NAME, ''), ' ', i.INST_LAST_NAME) AS InstructorName,
            CONCAT(c.Subject_Code, c.Course_Number) AS CourseName
        FROM API_INSTRUCTORS i
        INNER JOIN Candidate_Course_Relation r ON i.inst_id = r.CandidateID
        INNER JOIN Courses_Table c ON r.CourseID = c.CourseID
        INNER JOIN Candidate_Table ct ON i.inst_id = ct.SU_ID -- Match inst_id with SU_ID
        WHERE c.Subject_Code = 'ENG'
            AND c.Course_Number LIKE '00%'
            AND r.Term = :term
        ";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':term' => $term]);
    $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($instructors) {
        echo json_encode(['status' => 'success', 'data' => $instructors]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No instructors found for ENG courses']);
    }

} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database query failed.']);
}


