<?php
session_start();
require_once '../database/dbConnection.php';
header('Content-Type: application/json');

// Validate request
if (!isset($_GET['instructorID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing instructorID']);
    exit();
}

$instructorID = intval($_GET['instructorID']);

// Get current academic year
$academicYearResponse = file_get_contents('http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/getAcademicYear.php');
$academicYearData = json_decode($academicYearResponse, true);

if (!$academicYearData || $academicYearData['status'] !== 'success') {
    echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve academic year']);
    exit();
}

$currentYear = intval($academicYearData['academicYear']);
$validTerms = [
    ($currentYear) . '02',
    ($currentYear) . '01',
    ($currentYear - 1) . '02',
    ($currentYear - 1) . '01'
];

try {
    // Fetch courses taught by this instructor in the past 2 academic years
    $placeholders = implode(',', array_fill(0, count($validTerms), '?'));
    $query = "
        SELECT 
            c.CourseName,
            c.Subject_Code,
            c.Course_Number,
            r.Term
        FROM Candidate_Course_Relation r
        INNER JOIN Courses_Table c ON r.CourseID = c.CourseID
        WHERE r.CandidateID = ?
        AND r.Term IN ($placeholders)
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(array_merge([$instructorID], $validTerms));
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $courses]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
