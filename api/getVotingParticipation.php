<?php
require_once 'commonFunc.php';
init_session();

require_once '../database/dbConnection.php';

enforceAdminAccess($pdo);

header('Content-Type: application/json');

$selectedYear = $_GET['yearID'] ?? null;

// Validate `yearID`
if (!$selectedYear || !is_numeric($selectedYear)) {
    echo json_encode(["error" => "Invalid Year Selected"]);
    exit();
}

try {
    error_log("Debug: Selected Year from Frontend: " . json_encode($selectedYear));

    // Ensure correct YearID
    $stmtYearID = $pdo->prepare("SELECT YearID FROM AcademicYear_Table WHERE YearID = :selectedYear LIMIT 1");
    $stmtYearID->execute([':selectedYear' => $selectedYear]);
    $result = $stmtYearID->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        error_log("No matching Year ID found for YearID: " . json_encode($selectedYear));
        echo json_encode(["error" => "No matching Year ID found for YearID: $selectedYear"]);
        exit();
    }

    $actualYearID = $result['YearID'];
    error_log("Found Year ID: $actualYearID");

    // Check if any votes exist for this year
    $stmtVoteCheck = $pdo->prepare("SELECT COUNT(*) AS VoteCount FROM Votes_Table WHERE AcademicYear = :actualYearID");
    $stmtVoteCheck->execute([':actualYearID' => $actualYearID]);
    $voteCheck = $stmtVoteCheck->fetch(PDO::FETCH_ASSOC);

    if ($voteCheck['VoteCount'] == 0) {
        error_log("No votes recorded for the selected year ($actualYearID)");
        echo json_encode(["error" => "No votes recorded for the selected year"]);
        exit();
    }

    // Fetch category-wise participation (Optimized Query)
    $stmt = $pdo->prepare("
        SELECT 
            c.CategoryCode AS CategoryName, 
            COUNT(DISTINCT v.VoterID) AS Students_Voted,
            COUNT(DISTINCT s.id) AS Total_Students,
            ROUND(
                (COUNT(DISTINCT v.VoterID) / NULLIF(COUNT(DISTINCT s.id), 0)) * 100, 2
            ) AS Participation_Percentage
        FROM Category_Table c
        LEFT JOIN Candidate_Course_Relation cc ON c.CategoryID = cc.CategoryID
        LEFT JOIN Votes_Table v ON v.CandidateID = cc.CandidateID AND v.AcademicYear = :actualYearID
        LEFT JOIN Student_Category_Relation scr ON scr.categoryID = c.CategoryID
        LEFT JOIN Student_Table s ON s.id = scr.student_id AND s.YearID = :actualYearID
        WHERE c.CategoryID IS NOT NULL
        AND c.CategoryCode != 'E'
        GROUP BY c.CategoryCode
        ORDER BY c.CategoryCode ASC;
    ");

    if (!$stmt->execute([':actualYearID' => $actualYearID])) {
        error_log("SQL Execution Failed: " . json_encode($stmt->errorInfo()));
        echo json_encode(["error" => "Database error: Failed to execute query"]);
        exit();
    }

    $report = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$report) {
        error_log("No participation data found for Year ID: $actualYearID");
        echo json_encode(["error" => "No participation data found for the selected year"]);
    } else {
        error_log("Data fetched successfully");
        echo json_encode($report);
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
