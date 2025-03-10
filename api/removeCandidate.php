<?php
session_start();
require_once 'database/dbConnection.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'Admin') {
    die("Unauthorized Access.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $candidateID = $_POST['candidateID'] ?? null;
    $excludedBy = $_SESSION['user']; // The admin who removed the candidate

    if (!$candidateID) {
        die("Error: Candidate ID is required.");
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO Exception_Table (CandidateID, excluded_by, excluded_at) 
            VALUES (?, ?, NOW())
        ");
        if ($stmt->execute([$candidateID, $excludedBy])) {
            echo "Success";
        } else {
            echo "Error: Failed to remove candidate.";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
