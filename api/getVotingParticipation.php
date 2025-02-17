<?php
    require_once '../database/dbConnection.php';
    header('Content-Type: application/json');

    try {
        $stmt = $pdo->query("
            SELECT 
                ay.Academic_year AS AcademicYear,  -- Fetch actual academic year
                s.Class, 
                COUNT(DISTINCT v.VoterID) AS OyVeren,  -- Count unique voters
                COUNT(DISTINCT s.StudentID) AS ToplamKisi,  -- Count total students
                ROUND((COUNT(DISTINCT v.VoterID) / NULLIF(COUNT(DISTINCT s.StudentID), 0)) * 100, 2) AS OyKullanimOrani  -- Calculate percentage, avoid division by zero
            FROM Votes_Table v
            JOIN Student_Table s ON v.VoterID = s.StudentID
            JOIN AcademicYear_Table ay ON v.AcademicYear = ay.YearID  -- Fetch actual academic year
            WHERE v.AcademicYear IN (
                SELECT DISTINCT AcademicYear FROM Votes_Table
            ) 
            GROUP BY ay.Academic_year, s.Class
            ORDER BY ay.Academic_year DESC, OyKullanimOrani DESC;


        ");

        $participationData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($participationData);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error fetching participation data: ' . $e->getMessage()]);
    }
?>
