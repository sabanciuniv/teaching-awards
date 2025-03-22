<?php
require_once '../database/dbConnection.php';
header('Content-Type: application/json');

// Enable PDO error mode
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$response = [
    'inserted' => 0,
    'existing' => 0,
    'deleted' => 0,
    'insertedRows' => [],
    'existingRows' => [],
    'deletedRows' => []
];

try {
    // Step 1: Fetch existing student-category relations
    $stmt = $pdo->query("SELECT scr.student_id, scr.categoryID, s.StudentID 
                         FROM Student_Category_Relation scr 
                         JOIN Student_Table s ON scr.student_id = s.id");
    
    $existingRelations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = (int)$row['student_id'] . '_' . (int)$row['categoryID']; // Normalize types
        $existingRelations[$key] = [
            'student_id' => (int)$row['student_id'],
            'StudentID' => $row['StudentID'],
            'CategoryID' => (int)$row['categoryID']
        ];
    }

    // Step 2: Get Category Map
    $stmt = $pdo->query("SELECT CategoryID, CategoryCode FROM Category_Table");
    $categoryMap = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categoryMap[(int)$row['CategoryID']] = $row['CategoryCode'];
    }

    // Step 3: Desired Relations
    $stmt = $pdo->query("
        SELECT DISTINCT
            s.id AS student_id,
            s.StudentID,
            s.Class,
            s.CGPA,
            CONCAT(c.Subject_Code, ' ', c.Course_Number) AS full_code
        FROM Student_Course_Relation scr
        JOIN Courses_Table c ON scr.CourseID = c.CourseID
        JOIN Student_Table s ON scr.`student.id` = s.id
    ");

    $desiredRelations = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $studentID = $row['StudentID'];
        $student_id = (int)$row['student_id'];
        $class = $row['Class'];
        $cgpa = (float)$row['CGPA'];
        $code = $row['full_code'];

        $categoryID = null;

        if (in_array($code, ['TLL 101', 'TLL 102', 'AL 102']) && $class === 'Freshman') {
            $categoryID = 1;
        } elseif (in_array($code, ['SPS 101', 'SPS 102', 'MATH 101', 'MATH 102', 'IF 100', 'NS 101', 'NS 102', 'HIST 191', 'HIST 192']) && $class === 'Freshman') {
            $categoryID = 2;
        } elseif (in_array($code, ['ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004'])) {
            $categoryID = 4;
        } elseif (in_array($code, ['CIP 101N', 'IF 100R', 'MATH 101R', 'MATH 102R', 'NS 101R', 'NS 102R', 'SPS 101D', 'SPS 102D'])&& $class === 'Freshman') {
            $categoryID = 5;
        } elseif (!in_array($code, [
            'TLL 101', 'TLL 102', 'AL 102',
            'SPS 101', 'SPS 102', 'MATH 101', 'MATH 102',
            'IF 100', 'NS 101', 'NS 102', 'HIST 191', 'HIST 192',
            'ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004']) && $cgpa >= 2 && $class === 'Senior') {
            $categoryID = 3;
        }

        if ($categoryID !== null) {
            $key = $student_id . '_' . $categoryID;
            $desiredRelations[$key] = [
                'student_id' => $student_id,
                'StudentID' => $studentID,
                'CategoryID' => $categoryID,
                'CategoryCode' => $categoryMap[$categoryID] ?? 'Unknown'
            ];
        }
    }

    // Log key comparison for debug
    file_put_contents('desiredKeys.json', json_encode(array_keys($desiredRelations), JSON_PRETTY_PRINT));
    file_put_contents('existingKeys.json', json_encode(array_keys($existingRelations), JSON_PRETTY_PRINT));

    // Step 4: Prepare insert and delete statements
    $insertStmt = $pdo->prepare("INSERT INTO Student_Category_Relation (student_id, categoryID) VALUES (:student_id, :categoryID)");
    $deleteStmt = $pdo->prepare("DELETE FROM Student_Category_Relation WHERE student_id = :student_id AND categoryID = :categoryID");

    // Step 5: Insert missing
    foreach ($desiredRelations as $key => $info) {
        if (!isset($existingRelations[$key])) {
            try {
                $insertStmt->execute([
                    ':student_id' => $info['student_id'],
                    ':categoryID' => $info['CategoryID']
                ]);

                if ($insertStmt->rowCount() > 0) {
                    $response['inserted']++;
                    $response['insertedRows'][] = [
                        'StudentID' => $info['StudentID'],
                        'CategoryID' => $info['CategoryID'],
                        'CategoryCode' => $info['CategoryCode']
                    ];
                } else {
                    error_log("Insert failed (no rows affected): student_id={$info['student_id']} CategoryID={$info['CategoryID']}");
                }
            } catch (PDOException $ex) {
                error_log("Insert error for student_id={$info['student_id']} | categoryID={$info['CategoryID']}: " . $ex->getMessage());
            }
        } else {
            $response['existing']++;
            $response['existingRows'][] = [
                'StudentID' => $info['StudentID'],
                'CategoryID' => $info['CategoryID'],
                'CategoryCode' => $info['CategoryCode']
            ];
        }
    }

    // Step 6: Delete outdated
    foreach ($existingRelations as $key => $relation) {
        if (!isset($desiredRelations[$key])) {
            $deleteStmt->execute([
                ':student_id' => $relation['student_id'],
                ':categoryID' => $relation['CategoryID']
            ]);
            $response['deleted']++;
            $response['deletedRows'][] = [
                'StudentID' => $relation['StudentID'],
                'CategoryID' => $relation['CategoryID'],
                'CategoryCode' => $categoryMap[$relation['CategoryID']] ?? 'Unknown'
            ];
        }
    }

    echo json_encode(array_merge(['status' => 'success'], $response));
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
