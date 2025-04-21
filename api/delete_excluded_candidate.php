<?php
require_once '../database/dbConnection.php';
require_once 'commonFunc.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidateID = $_POST['candidateID'] ?? null;

    if (!$candidateID) {
        echo json_encode(['success' => false, 'error' => 'Candidate ID is missing']);
        exit;
    }

    $result = deleteExcludedCandidate($pdo, (int)$candidateID);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
