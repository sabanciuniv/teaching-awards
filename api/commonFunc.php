<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: loginCAS.php");
    exit;
}
$config = require __DIR__ . '/../config.php';
require_once '../database/dbConnection.php';
header('Content-Type: application/json');
// commonFunc.php

function deleteExcludedCandidate(PDO $pdo, int $candidateID): array {
    try {
        $stmt = $pdo->prepare("DELETE FROM Exception_Table WHERE CandidateID = :candidateID");
        $stmt->execute(['candidateID' => $candidateID]);

        return ['success' => true];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}


//get the current academic year
function getCurrentAcademicYear(PDO $pdo): ?string {
    $stmt = $pdo->query("
        SELECT Academic_year 
        FROM AcademicYear_Table 
        ORDER BY Start_date_time DESC 
        LIMIT 1
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['Academic_year'] : null;
}


?>