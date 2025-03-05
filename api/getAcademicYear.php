<?php
require_once __DIR__ . '/../database/dbConnection.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT YearID, academic_year, Start_date_time, End_date_time 
                         FROM AcademicYear_Table 
                         ORDER BY Start_date_time DESC 
                         LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'status' => 'success',  
            'academic_year' => $result['academic_year'], 
            'yearID' => $result['YearID'],
            'start_date' => $result['Start_date_time'],
            'end_date' => $result['End_date_time']
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No academic year found']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
