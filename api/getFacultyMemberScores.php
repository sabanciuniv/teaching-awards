<?php
session_start();
require_once __DIR__ . '/database/dbConnection.php';  // Adjust if needed

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Ensure we have a valid DB connection ($pdo)
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not established.");
    }

    // 1) Retrieve category & year from GET parameters (both ints)
    $categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;
    $yearId     = isset($_GET['year'])     ? intval($_GET['year'])     : 0;

    // 2) Validate inputs
    if ($categoryId <= 0 || $yearId <= 0) {
        echo json_encode(['error' => 'Invalid category or year selected.']);
        exit();
    }

    // 3) Fetch the academic year name from AcademicYear_Table
    $stmtYear = $pdo->prepare("
        SELECT YearID, Academic_year
        FROM AcademicYear_Table
        WHERE YearID = :yr
    ");
    $stmtYear->execute([':yr' => $yearId]);
    $yearRow = $stmtYear->fetch(PDO::FETCH_ASSOC);

    if (!$yearRow) {
        echo json_encode(['error' => 'Selected academic year does not exist.']);
        exit();
    }
    $academicYearName = $yearRow['Academic_year'];

    // 4) Fetch ALL candidates (any role, or only instructors if you wish) from Votes_Table
    //    Summing the Points, grouping by CandidateID for the selected CategoryID + YearID
    $stmt = $pdo->prepare("
        SELECT 
            v.CandidateID,
            c.Name AS candidate_name,
            c.Mail AS candidate_email,
            c.Role AS candidate_role,
            SUM(v.Points) AS total_points
        FROM Votes_Table v
        INNER JOIN Candidate_Table c ON v.CandidateID = c.id
        WHERE v.CategoryID = :catId
          AND v.AcademicYear = :yrId
        GROUP BY v.CandidateID
        ORDER BY total_points DESC
    ");
    $stmt->execute([
        ':catId' => $categoryId,
        ':yrId'  => $yearId
    ]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        // No votes found for this combination
        echo json_encode(['message' => 'No votes found for the selected category and year.']);
        exit();
    }

    // 5) Attach the academic year name to each row for clarity
    foreach ($rows as &$row) {
        $row['Academic_year'] = $academicYearName;
    }

    // 6) Return all candidate scores in JSON
    echo json_encode(['facultyScores' => $rows]);

} catch (Exception $e) {
    // In production, you might log the error rather than expose it
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
