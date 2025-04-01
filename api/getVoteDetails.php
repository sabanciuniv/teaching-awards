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

    // 2) Check for category code in the query string, e.g. ?category=A1
    if (!isset($_GET['category'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "No category code provided."]);
        exit();
    }

    $categoryCode   = $_GET['category'];
    $sunet_username = $_SESSION['user'];

    // 3) Get the student record
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

    // 4) Convert CategoryCode (e.g. "A1") to numeric CategoryID
    $stmtCat = $pdo->prepare("
        SELECT CategoryID
        FROM Category_Table
        WHERE CategoryCode = :catCode
    ");
    $stmtCat->execute(['catCode' => $categoryCode]);
    $catRow = $stmtCat->fetch(PDO::FETCH_ASSOC);

    if (!$catRow) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Category not found."]);
        exit();
    }

    $categoryId = $catRow['CategoryID'];

    // 5) Fetch the user's votes for this category + academic year
    //    Adjust table/column names as needed (Votes_Table vs. Vote_Table, etc.).
    $stmtVotes = $pdo->prepare("
        SELECT 
            vt.id             AS VoteID,
            vt.Points         AS Points,
            vt.Rank           AS Rank,
            c.Name            AS CandidateName,
            c.Mail            AS CandidateMail,
            c.Role            AS CandidateRole
        FROM Votes_Table vt
        JOIN Candidate_Table c ON vt.CandidateID = c.id
        WHERE vt.VoterID      = :voterId
          AND vt.CategoryID   = :categoryId
          AND vt.AcademicYear = :yearId
    ");
    $stmtVotes->execute([
        'voterId'    => $student_id,
        'categoryId' => $categoryId,
        'yearId'     => $year_id
    ]);

    $votes = $stmtVotes->fetchAll(PDO::FETCH_ASSOC);

    // 6) If no votes found, return a simple message
    if (empty($votes)) {
        echo json_encode([
            "status"      => "success",
            "voteDetails" => "<p>No votes found for this category.</p>"
        ]);
        exit();
    }

    // 7) Build an HTML snippet listing each vote (Removed the duplicate "Your Vote Details" heading)
    $html = "<ul>";
    foreach ($votes as $vote) {
        $candidateName = htmlspecialchars($vote['CandidateName']);
        $candidateMail = htmlspecialchars($vote['CandidateMail']);
        $candidateRole = htmlspecialchars($vote['CandidateRole']);
        $points        = htmlspecialchars($vote['Points']);
        $rank          = htmlspecialchars($vote['Rank']);

        $html .= "<li style='margin-bottom:10px;'>";
        $html .= "Candidate: <strong>{$candidateName}</strong><br/>";
        $html .= "Mail: {$candidateMail}<br/>";
        $html .= "Role: {$candidateRole}<br/>";
        $html .= "Points: {$points}<br/>";
        $html .= "Rank: {$rank}<br/>";
        $html .= "</li>";
    }
    $html .= "</ul>";

    // 8) Return the HTML snippet in JSON
    echo json_encode([
        "status"      => "success",
        "voteDetails" => $html
    ]);

} catch (PDOException $e) {
    error_log("Database error in getVoteDetails: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database query failed."]);
}
