<?php
session_start();
require_once __DIR__ . '/../database/dbConnection.php'; 

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);


try {
    // DB connection
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Query to get Instructors and add the totatl points for display
    $query = "
        SELECT 
            c.id AS FacultyMemberID,
            c.Name AS FacultyMemberName,
            v.AcademicYear,
            SUM(v.Points) AS TotalPoints
        FROM Votes_Table v
        JOIN Candidate_Table c ON v.CandidateID = c.id
        WHERE c.Role = 'Instructor'
        GROUP BY c.id, c.Name, v.AcademicYear
        ORDER BY v.AcademicYear DESC, TotalPoints DESC
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $facultyData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return JSON response with turkishh character
    echo json_encode($facultyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);


} catch (PDOException $e) {
    echo json_encode(["error" => "Database query failed: " . $e->getMessage()]);
}
?>