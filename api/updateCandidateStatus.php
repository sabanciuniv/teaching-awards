<?php
die("***DISABLED***".__FILE__);
session_start();
require_once '../database/dbConnection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

if (!isset($_POST['candidateID']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit();
}

$candidateID = $_POST['candidateID'];
$newStatus = $_POST['status'];

try {
    $stmt = $pdo->prepare("UPDATE Candidate_Table SET Status = :status WHERE id = :id");
    $stmt->bindParam(':status', $newStatus, PDO::PARAM_STR);
    $stmt->bindParam(':id', $candidateID, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
