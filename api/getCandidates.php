<?php
session_start();
require_once __DIR__ . '/../database/dbConnection.php'; 

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../commonFunc.php';


try {
    // Get the current academic year ID from the common Func
    if (!isset($_SESSION['academic_year_id'])) {
        $yearID = fetchCurrentAcademicYear($pdo);
        if ($yearID) {
            $_SESSION['academic_year_id'] = $year['YearID'];  // Store only the ID
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No academic year found']);
            exit();
        }
    }
    
    $currentYearID = $_SESSION['academic_year_id'];

    // Fetch all candidates for the current academic year
    $stmtCandidates = $pdo->prepare("
        SELECT id, SU_ID, Name, Mail, Role, YearID, Status, Status_description, Sync_Date 
        FROM Candidate_Table 
        WHERE YearID = :yearID
    ");
    $stmtCandidates->bindParam(':yearID', $currentYearID, PDO::PARAM_INT);
    $stmtCandidates->execute();

    $candidates = $stmtCandidates->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'candidates' => $candidates
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>