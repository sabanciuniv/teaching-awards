<?php
require_once __DIR__ . '/../database/dbConnection.php';

try {
    $stmt = $pdo->query("SELECT YearID FROM AcademicYear_Table ORDER BY Start_date_time DESC LIMIT 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo json_encode(['year' => $result['YearID']]);  // Ensure YearID is returned
    } else {
        echo json_encode(['error' => 'No academic year found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
