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

    // Retrieve category from GET parameters
    $categoryId = isset($_GET['category']) ? intval($_GET['category']) : null;

    // Validate category
    if (!$categoryId) {
        echo json_encode(['error' => 'Invalid category selected.']);
        exit();
    }

    // Fetch the latest academic year dynamically
    $stmtAcademicYear = $pdo->prepare("
        SELECT YearID, Academic_year
        FROM AcademicYear_Table
        WHERE YearID = (
            SELECT MAX(AcademicYear) 
            FROM Votes_Table
            WHERE CategoryID = :category_id
        )
    ");
    $stmtAcademicYear->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
    $stmtAcademicYear->execute();
    $academicYearRow = $stmtAcademicYear->fetch(PDO::FETCH_ASSOC);

    if (!$academicYearRow || !isset($academicYearRow['YearID'])) {
        echo json_encode(['error' => 'Academic year not found.']);
        exit();
    }

    $academicYearID = $academicYearRow['YearID'];
    $academicYear = $academicYearRow['Academic_year'];

    // Check if winners already exist for the selected category and academic year
    $checkStmt = $pdo->prepare("
        SELECT COUNT(*) FROM WinnerList_Table 
        WHERE YearID = :year_id AND CategoryID = :category_id
    ");
    $checkStmt->execute([
        ':year_id' => $academicYearID,
        ':category_id' => $categoryId
    ]);

    $existingWinnersCount = $checkStmt->fetchColumn();

    if ($existingWinnersCount > 0) {
        // Winners already exist, fetch from WinnerList_Table
        $existingStmt = $pdo->prepare("
            SELECT w.Rank AS rank, 
                   c.Name AS candidate_name, 
                   c.Mail AS candidate_email, 
                   c.Role AS candidate_role, 
                   a.Academic_year
            FROM WinnerList_Table w
            INNER JOIN Candidate_Table c ON w.WinnerID = c.id
            INNER JOIN AcademicYear_Table a ON w.YearID = a.YearID
            WHERE w.YearID = :year_id AND w.CategoryID = :category_id
            ORDER BY w.Rank ASC
        ");
        $existingStmt->execute([
            ':year_id' => $academicYearID,
            ':category_id' => $categoryId
        ]);

        $existingWinners = $existingStmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['winners' => $existingWinners]);
        exit();
    }

    // Fetch votes and candidate details for the selected category and academic year
    $stmt = $pdo->prepare("
        SELECT 
            v.CandidateID, 
            c.Name AS candidate_name, 
            c.Mail AS candidate_email, 
            c.Role AS candidate_role,
            SUM(v.Points) AS total_points
        FROM Votes_Table v
        INNER JOIN Candidate_Table c ON v.CandidateID = c.id
        WHERE v.CategoryID = :category_id AND v.AcademicYear = :academic_year
        GROUP BY v.CandidateID
        ORDER BY total_points DESC
        LIMIT 3
    ");
    $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
    $stmt->bindParam(':academic_year', $academicYearID, PDO::PARAM_INT);
    $stmt->execute();
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($candidates)) {
        echo json_encode(['message' => 'No votes found for the selected category.']);
        exit();
    }

    // Insert winners into WinnerList_Table
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
            'rank' => $rank,
            'candidate_name' => $candidate['candidate_name'],
            'candidate_email' => $candidate['candidate_email'],
            'candidate_role' => $candidate['candidate_role'],
            'total_points' => $candidate['total_points'],
            'term' => $academicYear,
        ];

        $insertStmt->execute([
            ':year_id' => $academicYearID,
            ':winner_id' => $candidate['CandidateID'],
            ':rank' => $rank,
            ':category_id' => $categoryId,
        ]);

        $rank++;
    }

    $pdo->commit();
    echo json_encode(['winners' => $winners]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
