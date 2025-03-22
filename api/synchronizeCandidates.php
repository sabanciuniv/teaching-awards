<?php
session_start();
require_once '../database/dbConnection.php';
header('Content-Type: application/json');

try {
    // Mapping employment status to Candidate_Table status
    $statusMapping = [
        'Active' => 'Etkin',
        'Inactive' => 'İşten ayrıldı',
        'Terminated' => 'İşten ayrıldı',
        'İşten ayrıldı' => 'İşten ayrıldı' // In case of case sensitivity
    ];

    $candidates = [];

    // Fetch TA data from API_TAS
    $stmt = $pdo->query("SELECT TA_ID, TA_FIRST_NAME, TA_MI_NAME, TA_LAST_NAME, 
        TA_EMAIL, EMPL_STATUS, HOMEDEPT_CODE 
        FROM API_TAS");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fullName = trim($row['TA_FIRST_NAME'] . ' ' . ($row['TA_MI_NAME'] ?? '') . ' ' . $row['TA_LAST_NAME']);
        $status = $statusMapping[$row['EMPL_STATUS']] ?? 'Etkin';  // Default to 'Etkin' if status not found

        $candidates[$row['TA_ID']] = [
            'SU_ID' => $row['TA_ID'],
            'Name' => $fullName,
            'Mail' => $row['TA_EMAIL'] ?: null,
            'Role' => 'TA',
            'Status' => $status
        ];
    }

    // Fetch Instructor data from API_INSTRUCTORS
    $stmt = $pdo->query("SELECT INST_ID, INST_FIRST_NAME, INST_MI_NAME, INST_LAST_NAME, 
        INST_EMAIL, EMPL_STATUS, HOMEDEPT_CODE 
        FROM API_INSTRUCTORS");

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fullName = trim($row['INST_FIRST_NAME'] . ' ' . ($row['INST_MI_NAME'] ?? '') . ' ' . $row['INST_LAST_NAME']);
        $status = $statusMapping[$row['EMPL_STATUS']] ?? 'Etkin';

        $candidates[$row['INST_ID']] = [
            'SU_ID' => $row['INST_ID'],
            'Name' => $fullName,
            'Mail' => $row['INST_EMAIL'] ?: null,
            'Role' => 'Instructor',
            'Status' => $status
        ];
    }

    // Fetch existing candidates from Candidate_Table
    $stmt = $pdo->query("SELECT SU_ID, Name, Mail, Role, Status FROM Candidate_Table");
    $existingCandidates = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingCandidates[$row['SU_ID']] = $row;
    }

    // Prepare SQL statements for insertion and updates
    $insertStmt = $pdo->prepare("INSERT INTO Candidate_Table 
    (SU_ID, Name, Mail, Role, Status, Sync_Date) 
    VALUES (:SU_ID, :Name, :Mail, :Role, :Status, NOW())");

    $updateStmt = $pdo->prepare("UPDATE Candidate_Table SET 
        Name = :Name, 
        Mail = :Mail, 
        Role = :Role, 
        Status = :Status, 
        Sync_Date = NOW() 
        WHERE SU_ID = :SU_ID");

    $updated = 0;
    $inserted = 0;
    $updatedRows = [];
    $insertedRows = [];

    // Insert or update candidates
    foreach ($candidates as $su_id => $candidate) {
        if (isset($existingCandidates[$su_id])) {
            $existingCandidate = $existingCandidates[$su_id];

            // Track changes
            $changes = [];

            if ($existingCandidate['Name'] !== $candidate['Name']) {
                $changes['Name'] = ['old' => $existingCandidate['Name'], 'new' => $candidate['Name']];
            }
            if ($existingCandidate['Mail'] !== $candidate['Mail']) {
                $changes['Mail'] = ['old' => $existingCandidate['Mail'], 'new' => $candidate['Mail']];
            }
            if ($existingCandidate['Role'] !== $candidate['Role']) {
                $changes['Role'] = ['old' => $existingCandidate['Role'], 'new' => $candidate['Role']];
            }
            if ($existingCandidate['Status'] !== $candidate['Status']) {
                $changes['Status'] = ['old' => $existingCandidate['Status'], 'new' => $candidate['Status']];
            }

            // Update only if changes exist
            if (!empty($changes)) {
                $updateStmt->execute([
                    ':SU_ID' => $su_id,
                    ':Name' => $candidate['Name'],
                    ':Mail' => $candidate['Mail'],
                    ':Role' => $candidate['Role'],
                    ':Status' => $candidate['Status']
                ]);

                if ($updateStmt->rowCount() > 0) {
                    $updated++;
                    $updatedRows[] = [
                        'SU_ID' => $su_id,
                        'changes' => $changes
                    ];
                }
            }
        } else {
            // Insert new candidate
            $insertStmt->execute([
                ':SU_ID' => $su_id,
                ':Name' => $candidate['Name'],
                ':Mail' => $candidate['Mail'],
                ':Role' => $candidate['Role'],
                ':Status' => $candidate['Status']
            ]);

            if ($insertStmt->rowCount() > 0) {
                $inserted++;
                $insertedRows[] = [
                    'SU_ID' => $su_id,
                    'Name' => $candidate['Name'],
                    'Mail' => $candidate['Mail'],
                    'Role' => $candidate['Role'],
                    'Status' => $candidate['Status']
                ];
            }
        }
    }

    // Return response with detailed changes
    echo json_encode([
        'status' => 'success',
        'inserted' => $inserted,
        'updated' => $updated,
        'insertedRows' => $insertedRows,
        'updatedRows' => $updatedRows
    ]);
    exit();

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => "Error: " . $e->getMessage()
    ]);
}
?>
