<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../database/dbConnection.php';  // Adjust path if needed

if (!isset($_SESSION['user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateID = isset($_POST['candidateID']) ? intval($_POST['candidateID']) : 0;
    $excluded_by = $_SESSION['user'];

    if ($candidateID <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid Candidate ID']);
        exit();
    }

    try {
        // Check if candidate exists
        $checkStmt = $pdo->prepare("SELECT id FROM Candidate_Table WHERE id = :id");
        $checkStmt->execute([':id' => $candidateID]);
        if ($checkStmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'error' => "Candidate with ID $candidateID does not exist"]);
            exit();
        }


        // Check if candidate is already excluded
        $alreadyExcluded = $pdo->prepare("SELECT id FROM Exception_Table WHERE CandidateID = :cid");
        $alreadyExcluded->execute([':cid' => $candidateID]);

        if ($alreadyExcluded->rowCount() > 0) {
            echo json_encode(['success' => false, 'error' => 'Candidate is already excluded.']);
            exit();
        }

        // Insert into Exception_Table
        $insertStmt = $pdo->prepare("
            INSERT INTO Exception_Table (CandidateID, excluded_by)
            VALUES (:cid, :eby)
        ");
        $insertStmt->execute([
            ':cid' => $candidateID,
            ':eby' => $excluded_by
        ]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
