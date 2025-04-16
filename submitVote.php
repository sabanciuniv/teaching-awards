<?php
session_start();
require_once __DIR__ . '/database/dbConnection.php'; // Ensure database connection
require_once 'api/impersonationLogger.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not established.");
    }

    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }

    // Check if impersonation is active
    if (isset($_SESSION['impersonating']) && $_SESSION['impersonating'] === true && isset($_SESSION['impersonated_user'])) {
        $impersonatedUsername = $_SESSION['impersonated_user'];
    } else {
        $impersonatedUsername = $_SESSION['user'];
    }

    $stmt = $pdo->prepare("SELECT id FROM Student_Table WHERE SuNET_Username = ?");
    $stmt->execute([$impersonatedUsername]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student || !isset($student['id'])) {
        error_log("User not found in Student_Table. Username: $impersonatedUsername");
        echo json_encode(["status" => "error", "message" => "User not found in Student_Table."]);
        exit();
    }

    $voterID = $student['id'];


    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);
    error_log("Received Data: " . print_r($data, true));

    if (!isset($data['categoryID'], $data['academicYear'], $data['votes'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
        exit();
    }

    $categoryCode = $data['categoryID']; 
    $academicYear = $data['academicYear'];
    $votes = $data['votes'];

    if (empty($votes) || !is_array($votes)) {
        echo json_encode(["status" => "error", "message" => "No valid votes received."]);
        exit();
    }

    $pdo->beginTransaction();

    $yearStmt = $pdo->prepare("SELECT YearID FROM AcademicYear_Table WHERE Academic_year = ?");
    $yearStmt->execute([$academicYear]);
    $yearRow = $yearStmt->fetch(PDO::FETCH_ASSOC);

    if (!$yearRow) {
        echo json_encode(["status" => "error", "message" => "Invalid academic year."]);
        exit();
    }
    $academicYearID = $yearRow['YearID'];

    $categoryStmt = $pdo->prepare("SELECT CategoryID FROM Category_Table WHERE CategoryCode = ?");
    $categoryStmt->execute([$categoryCode]);
    $categoryRow = $categoryStmt->fetch(PDO::FETCH_ASSOC);

    if (!$categoryRow) {
        echo json_encode(["status" => "error", "message" => "Invalid category code."]);
        exit();
    }
    $categoryID = $categoryRow['CategoryID'];

    // Assign points based on the number of instructors ranked
    $numRanks = count($votes);
    $pointsDistribution = [];

    if ($numRanks === 1) {
        $pointsDistribution = [6]; // All points go to 1st place
    } elseif ($numRanks === 2) {
        $pointsDistribution = [4, 2]; // 4 points for Rank 1, 2 points for Rank 2
    } elseif ($numRanks === 3) {
        $pointsDistribution = [3, 2, 1]; // 3 points for Rank 1, 2 for Rank 2, 1 for Rank 3
    }

    // Insert votes with calculated points
    $insertStmt = $pdo->prepare("INSERT INTO Votes_Table (AcademicYear, VoterID, CandidateID, CategoryID, Points, `Rank`)
                                 VALUES (?, ?, ?, ?, ?, ?)");

    foreach ($votes as $index => $vote) {
        if (!isset($vote['candidateID'], $vote['rank'])) {
            throw new Exception("Invalid vote format: " . json_encode($vote));
        }

        $rank = $vote['rank'];
        $points = isset($pointsDistribution[$rank - 1]) ? $pointsDistribution[$rank - 1] : 0;
        
        $result = $insertStmt->execute([$academicYearID, $voterID, $vote['candidateID'], $categoryID, $points, $rank]);

        if (!$result) {
            $errorInfo = $insertStmt->errorInfo();
            error_log("Vote insert failed: " . json_encode($errorInfo));
            echo json_encode(["status" => "error", "message" => "Vote insert failed: " . json_encode($errorInfo)]);
            $pdo->rollBack(); // Ensure rollback on failure
            exit();
        } else {
            error_log("Vote inserted successfully: VoterID=$voterID, CandidateID={$vote['candidateID']}, Rank=$rank, Points=$points");
        }
    
    }

    $pdo->commit();

    echo json_encode(["status" => "success", "message" => "Vote submitted successfully."]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Internal Server Error: " . $e->getMessage()]);
}

logImpersonationAction(
    $pdo,
    'Voted',
    [
        'category_id' => $categoryID,
        'category_name' => $categoryName,
        'voted_for' => [
            'candidate_id' => $vote['candidateID'],
            'candidate_name' => $candidateName,
            'rank' => $rank,
            'points' => $points
        ],
        'student_id' => $_SESSION['student_id'] ?? $voterID
    ]
);
?>
