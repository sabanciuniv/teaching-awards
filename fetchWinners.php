<?php
session_start();
require_once __DIR__ . '/database/dbConnection.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not established.");
    }

    // Retrieve category and year from GET parameters
    $categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;
    $yearId = isset($_GET['year']) ? intval($_GET['year']) : null;

    // Validate inputs
    if (!$categoryId || !$yearId) {
        echo json_encode(['error' => 'Invalid category or year selected.']);
        exit();
    }

    // Fetch the academic year string from AcademicYear_Table
    $stmtYear = $pdo->prepare("
        SELECT YearID, Academic_year
        FROM AcademicYear_Table
        WHERE YearID = :year_id
    ");
    $stmtYear->execute([':year_id' => $yearId]);
    $yearRow = $stmtYear->fetch(PDO::FETCH_ASSOC);

    if (!$yearRow) {
        echo json_encode(['error' => 'Selected academic year does not exist.']);
        exit();
    }

    $academicYear = $yearRow['Academic_year'];

    // Check if winners already exist for the selected category and year
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM WinnerList_Table 
        WHERE YearID = :year_id 
          AND CategoryID = :category_id
    ");
    $checkStmt->execute([
        ':year_id' => $yearId,
        ':category_id' => $categoryId
    ]);
    $existingWinnersCount = $checkStmt->fetchColumn();

    if ($existingWinnersCount > 0) {
        // Winners already exist, fetch them from WinnerList_Table
        $existingStmt = $pdo->prepare("
            SELECT 
                w.Rank AS rank, 
                c.Name AS candidate_name, 
                c.Mail AS candidate_email, 
                c.Role AS candidate_role, 
                IFNULL(a.Academic_year, :fallback_year) AS Academic_year
            FROM WinnerList_Table w
            INNER JOIN Candidate_Table c 
                ON w.WinnerID = c.id
            LEFT JOIN AcademicYear_Table a 
                ON w.YearID = a.YearID
            WHERE w.YearID = :year_id
              AND w.CategoryID = :category_id
            ORDER BY w.Rank ASC
        ");
        $existingStmt->execute([
            ':year_id'       => $yearId,
            ':category_id'   => $categoryId,
            ':fallback_year' => $academicYear
        ]);
        $existingWinners = $existingStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['winners' => $existingWinners]);
        exit();
    }

    // If no existing winners, fetch from Votes_Table
    $stmt = $pdo->prepare("
        SELECT 
            v.CandidateID, 
            c.Name AS candidate_name, 
            c.Mail AS candidate_email, 
            c.Role AS candidate_role,
            SUM(v.Points) AS total_points
        FROM Votes_Table v
        INNER JOIN Candidate_Table c ON v.CandidateID = c.id
        WHERE v.CategoryID = :category_id 
          AND v.AcademicYear = :year_id
        GROUP BY v.CandidateID
        ORDER BY total_points DESC
        LIMIT 3
    ");
    $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->bindParam(':year_id', $yearId, PDO::PARAM_INT);
    $stmt->execute();
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        echo json_encode(['message' => 'No votes found for the selected category and year.']);
        exit();
    }

    // Insert these winners into WinnerList_Table
    $pdo->beginTransaction();
    $insertStmt = $pdo->prepare("
        INSERT INTO WinnerList_Table (YearID, WinnerID, Rank, CategoryID)
        VALUES (:year_id, :winner_id, :rank, :category_id)
        ON DUPLICATE KEY UPDATE Rank = VALUES(Rank)
    ");

    $winners = [];
    $rank = 1;

    foreach ($candidates as $candidate) {
        $winners[] = [
            'rank'            => $rank,
            'candidate_name'  => $candidate['candidate_name'],
            'candidate_email' => $candidate['candidate_email'],
            'candidate_role'  => $candidate['candidate_role'],
            'Academic_year'   => $academicYear,
        ];

        $insertStmt->execute([
            ':year_id'     => $yearId,
            ':winner_id'   => $candidate['CandidateID'],
            ':rank'        => $rank,
            ':category_id' => $categoryId,
        ]);

        $rank++;
    }

    $pdo->commit();

    // Return the winners we just inserted
    echo json_encode(['winners' => $winners]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
