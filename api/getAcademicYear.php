<?php
require_once 'commonFunc.php';
init_session();

require_once '../database/dbConnection.php';

header('Content-Type: application/json');

try {
    $stmtAcademicYear = $pdo->prepare("
        SELECT YearID, Academic_year, Start_date_time, End_date_time 
        FROM AcademicYear_Table 
        ORDER BY Start_date_time DESC
        LIMIT 1
    ");
    $stmtAcademicYear->execute();
    $academicYear = $stmtAcademicYear->fetch(PDO::FETCH_ASSOC);

    if ($academicYear) {
        $_SESSION['academic_year'] = $academicYear['Academic_year'];  // Store in session
        
        echo json_encode([
        'status' => 'success', 
        'academicYear' => $academicYear['Academic_year'],
        'yearID' => $academicYear['YearID'],
        'start_date' => $academicYear['Start_date_time'],
        'end_date' => $academicYear['End_date_time']
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No academic year found']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
