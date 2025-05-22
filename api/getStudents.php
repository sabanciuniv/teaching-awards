<?php
die("***DISABLED***".__FILE__);

require_once __DIR__ . '/../database/dbConnection.php';

try {
    // db connection
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Query to fetch student data
    $query = "
        SELECT 
            StudentID, 
            YearID AS AcademicYear, 
            StudentFullName, 
            SuNET_Username, 
            Class, 
            Mail, 
            Department, 
            A1_Vote, 
            A2_Vote, 
            B_Vote, 
            C_Vote, 
            D_Vote
        FROM Student_Table
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //return json format
    echo json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode(["error" => "Database query failed: " . $e->getMessage()]);
}
?>