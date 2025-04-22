<?php

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

//add to excluded candidate
function addExcludedCandidate(PDO $pdo, int $candidateID, string $excludedBy): array {
    try {
        // Check if candidate exists
        $checkStmt = $pdo->prepare("SELECT id FROM Candidate_Table WHERE id = :id");
        $checkStmt->execute([':id' => $candidateID]);

        if ($checkStmt->rowCount() === 0) {
            return ['success' => false, 'error' => "Candidate with ID $candidateID does not exist"];
        }

        // Check if candidate is already excluded
        $alreadyExcluded = $pdo->prepare("SELECT id FROM Exception_Table WHERE CandidateID = :cid");
        $alreadyExcluded->execute([':cid' => $candidateID]);

        if ($alreadyExcluded->rowCount() > 0) {
            return ['success' => false, 'error' => 'Candidate is already excluded.'];
        }

        // Insert into Exception_Table
        $insertStmt = $pdo->prepare("
            INSERT INTO Exception_Table (CandidateID, excluded_by)
            VALUES (:cid, :eby)
        ");
        $insertStmt->execute([
            ':cid' => $candidateID,
            ':eby' => $excludedBy
        ]);

        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
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

//get the current year's ID
function getCurrentAcademicYearID(PDO $pdo): ?int {
    $stmt = $pdo->query("
        SELECT YearID 
        FROM AcademicYear_Table 
        ORDER BY Start_date_time DESC 
        LIMIT 1
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['YearID'] : null;
}


?>