<?php
require_once __DIR__ . '/../database/dbConnection.php';

try {
    $stmt = $pdo->query("SELECT YearID, Start_date_time, End_date_time FROM AcademicYear_Table ORDER BY Start_date_time DESC LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode([
            'yearID' => $result['YearID'],
            'start_date'=> $result['Start_date_time'],
            'end_date'=> $result['End_date_time']
        ]);  
    } else {
        echo json_encode(['error' => 'No academic year found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
