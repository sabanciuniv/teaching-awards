<?php
session_start();
require_once '../database/dbConnection.php';

header('Content-Type: application/json');

try {
    // Simulated synchronization process (You should replace this with real data update logic)
    $stmt = $pdo->prepare("UPDATE Candidate_Table SET Sync_Date = NOW()");
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Synchronization completed successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
