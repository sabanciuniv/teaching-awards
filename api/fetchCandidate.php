<?php
die("***DISABLED***".__FILE__);
session_start();
require_once '../database/dbConnection.php';  // Adjust path if needed

// Return only excluded candidates, last excluded on top
$sql = "
    SELECT 
        c.id,
        c.Name,
        c.SU_ID AS Mail,
        c.Role,
        e.excluded_by,
        e.excluded_at
    FROM Candidate_Table c
    INNER JOIN Exception_Table e ON c.id = e.CandidateID
    ORDER BY e.excluded_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($candidates);
?>
