<?php
require_once __DIR__ . '/database/dbConnection.php'; // Ensure database connection
require_once 'api/impersonationLogger.php';
require_once 'api/commonFunc.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not established.");
    }
    
    init_session();
    
    // Determine voter username (impersonated or real)
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // Ajax request, proceed
    }
    
    if (isset($_SESSION['impersonating']) && $_SESSION['impersonating'] === true && isset($_SESSION['impersonated_user'])) {
        $impersonatedUsername = $_SESSION['impersonated_user'];
    } else {
        $impersonatedUsername = $_SESSION['user'] ?? null;
    }

    if (!$impersonatedUsername) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Not logged in."]);
        exit();
    }

    $stmt = $pdo->prepare("SELECT id FROM Student_Table WHERE SuNET_Username = ?");
    $stmt->execute([$impersonatedUsername]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student || !isset($student['id'])) {
        echo json_encode(["status" => "error", "message" => "User not found in Student_Table."]);
        exit();
    }
    $voterID = $student['id'];

    // Parse JSON input
    $jsonInput = file_get_contents('php://input');
    $data = json_decode($jsonInput, true);

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

    // -------------------------
    // RELATION CHECK: ensure student enrolled in a course with each candidate
    // -------------------------
    $relationStmt = $pdo->prepare(
        "SELECT COUNT(*)
           FROM Student_Course_Relation scr
           JOIN Candidate_Course_Relation ccr ON scr.CourseID = ccr.CourseID
          WHERE scr.`student.id` = :student
            AND ccr.CandidateID   = :candidate
            AND scr.EnrollmentStatus = 'enrolled'"
    );

    foreach ($votes as $vote) {
        $candID = (int)$vote['candidateID'];
        $relationStmt->execute([':student' => $voterID, ':candidate' => $candID]);
        if ($relationStmt->fetchColumn() == 0) {
            http_response_code(400);
            echo json_encode([
                "status"  => "error",
                "message" => "You can’t vote for candidate #{$candID}—no enrolled course relation found."
            ]);
            exit();
        }
    }
    // -------------------------

    $pdo->beginTransaction();

    // Lookup YearID
    $yearStmt = $pdo->prepare("SELECT YearID FROM AcademicYear_Table WHERE Academic_year = ?");
    $yearStmt->execute([$academicYear]);
    $yearRow = $yearStmt->fetch(PDO::FETCH_ASSOC);
    if (!$yearRow) {
        echo json_encode(["status" => "error", "message" => "Invalid academic year."]);
        exit();
    }
    $academicYearID = $yearRow['YearID'];

    // Lookup CategoryID
    $categoryStmt = $pdo->prepare("SELECT CategoryID, CategoryDescription FROM Category_Table WHERE CategoryCode = ?");
    $categoryStmt->execute([$categoryCode]);
    $categoryRow = $categoryStmt->fetch(PDO::FETCH_ASSOC);
    if (!$categoryRow) {
        echo json_encode(["status" => "error", "message" => "Invalid category code."]);
        exit();
    }
    $categoryID = $categoryRow['CategoryID'];
    $categoryDescription = $categoryRow['CategoryDescription'];

    // Determine points distribution
    $numRanks = count($votes);
    if ($numRanks === 1)       { $pointsDist = [6]; }
    elseif ($numRanks === 2)   { $pointsDist = [4,2]; }
    elseif ($numRanks === 3)   { $pointsDist = [3,2,1]; }
    else                        { $pointsDist = []; }

    // Prepare inserts
    $insertStmt = $pdo->prepare(
        "INSERT INTO Votes_Table
         (AcademicYear, VoterID, CandidateID, CategoryID, Points, `Rank`)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $candidateStmt = $pdo->prepare("SELECT Name, SU_ID FROM Candidate_Table WHERE id = ?");

    foreach ($votes as $vote) {
        if (!isset($vote['candidateID'], $vote['rank'])) {
            throw new Exception("Invalid vote format: " . json_encode($vote));
        }
        $rk     = (int)$vote['rank'];
        $candID = (int)$vote['candidateID'];
        $pts    = $pointsDist[$rk-1] ?? 0;

        if (!$insertStmt->execute([$academicYearID, $voterID, $candID, $categoryID, $pts, $rk])) {
            $errorInfo = $insertStmt->errorInfo();
            throw new Exception("Vote insert failed: " . json_encode($errorInfo));
        }

        // Log impersonation if needed
        $candidateStmt->execute([$candID]);
        $cData = $candidateStmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($_SESSION['impersonating'])) {
            logImpersonationAction(
                $pdo, 'Voted', [
                    'category_id'   => $categoryID,
                    'category_code' => $categoryCode,
                    'category_name' => $categoryDescription,
                    'voted_for'     => [
                        'candidate_name'  => $cData['Name'] ?? 'Unknown',
                        'candidate_su_id' => $cData['SU_ID'] ?? 'Unknown',
                        'rank'            => $rk,
                        'points'          => $pts
                    ],
                    'student_id'    => $voterID
                ]
            );
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
?>
