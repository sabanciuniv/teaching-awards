<?php
session_start();
require_once 'database/dbConnection.php';

// Fetch candidates excluding those in the Exception_Table
$sql = "
    SELECT c.id, c.Name, c.Mail, c.Role
    FROM Candidate_Table c
    LEFT JOIN Exception_Table e ON c.id = e.CandidateID
    WHERE e.CandidateID IS NULL";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($candidates);
?>
