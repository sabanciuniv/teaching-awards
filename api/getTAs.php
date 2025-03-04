<?php
// Include database connection
require_once __DIR__ . '/../database/dbConnection.php';

// Set content type to JSON
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, Name, Mail, Role, Status FROM Candidate_Table WHERE Role = 'TA'");
    $TAs  = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => 'No TA found',
        'data' => $instructors
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to fetch instructors: ' . $e->getMessage()
    ]);
}
?>
