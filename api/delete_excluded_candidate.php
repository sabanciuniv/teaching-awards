<?php
require_once '../database/dbConnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateID = $_POST['candidateID'] ?? null;

    if (!$candidateID) {
        echo json_encode(['success' => false, 'error' => 'Candidate ID is missing']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM Exception_Table WHERE CandidateID = :candidateID");
        $stmt->execute(['candidateID' => $candidateID]);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
