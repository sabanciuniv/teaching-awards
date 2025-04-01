<?php
require_once '../database/dbConnection.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT filename, user, academicYear, sync_date FROM Sync_Logs ORDER BY sync_date DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'logs' => $logs
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load logs: ' . $e->getMessage()
    ]);
}
