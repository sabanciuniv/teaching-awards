<?php
require_once '../database/dbConnection.php';
header('Content-Type: application/json');

$response = [
    'inserted' => 0,
    'updated' => 0,
    'deleted' => 0,
    'insertedRows' => [],
    'updatedRows' => [],
    'deletedRows' => []
];

try {
    // Fetch all existing student-category relations
    $stmt = $pdo->query("SELECT scr.student_id, scr.categoryID, s.StudentID FROM Student_Category_Relation scr JOIN Student_Table s ON scr.student_id = s.id");
    $existingRelations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = $row['student_id'] . '_' . $row['categoryID'];
        $existingRelations[$key] = $row['StudentID'];
    }

    // CategoryID => CategoryCode map
    $stmt = $pdo->query("SELECT CategoryID, CategoryCode FROM Category_Table");
    $categoryMap = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categoryMap[$row['CategoryID']] = $row['CategoryCode'];
    }

    // Load eligible student-category candidates
    $stmt = $pdo->query("SELECT DISTINCT
            s.id AS student_id,
            s.StudentID,
            s.Class,
            s.CGPA,
            CONCAT(c.Subject_Code, ' ', c.Course_Number) AS full_code
        FROM Student_Course_Relation scr
        JOIN Courses_Table c ON scr.CourseID = c.CourseID
        JOIN Student_Table s ON scr.`student.id` = s.id");

    $desiredRelations = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $studentID = $row['StudentID'];
        $student_id = $row['student_id'];
        $class = $row['Class'];
        $cgpa = $row['CGPA'];
        $code = $row['full_code'];

        $categoryID = null;
        if (in_array($code, ['TLL 101', 'TLL 102', 'AL 102']) && $class === 'FR') {
            $categoryID = 1;
        } elseif (in_array($code, ['SPS 101', 'SPS 102', 'MATH 101', 'MATH 102', 'IF 100', 'NS 101', 'NS 102', 'HIST 191', 'HIST 192']) && $class === 'FR') {
            $categoryID = 2;
        } elseif (in_array($code, ['ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004'])) {
            $categoryID = 4;
        } elseif (in_array($code, ['CIP 101N', 'IF 100R', 'MATH 101R', 'MATH 102R', 'NS 101R', 'NS 102R', 'SPS 101D', 'SPS 102D'])) {
            $categoryID = 5;
        } elseif (!in_array($code, [
            'TLL 101', 'TLL 102', 'AL 102',
            'SPS 101', 'SPS 102', 'MATH 101', 'MATH 102',
            'IF 100', 'NS 101', 'NS 102', 'HIST 191', 'HIST 192',
            'ENG 0001', 'ENG 0002', 'ENG 0003', 'ENG 0004']) && $cgpa >= 2 && $class === 'SE') {
            $categoryID = 3;
        }

        if ($categoryID) {
            $key = $student_id . '_' . $categoryID;
            $desiredRelations[$key] = [
                'student_id' => $student_id,
                'StudentID' => $studentID,
                'CategoryID' => $categoryID,
                'CategoryCode' => $categoryMap[$categoryID] ?? 'Unknown'
            ];
        }
    }

    // Prepare insert/delete
    $insertStmt = $pdo->prepare("INSERT INTO Student_Category_Relation (student_id, categoryID) VALUES (:student_id, :categoryID)");
    $deleteStmt = $pdo->prepare("DELETE FROM Student_Category_Relation WHERE student_id = :student_id AND categoryID = :categoryID");

    // Insert or update desired
    foreach ($desiredRelations as $key => $info) {
        if (!isset($existingRelations[$key])) {
            $insertStmt->execute([
                ':student_id' => $info['student_id'],
                ':categoryID' => $info['CategoryID']
            ]);
            $response['inserted']++;
            $response['insertedRows'][] = [
                'StudentID' => $info['StudentID'],
                'CategoryID' => $info['CategoryID'],
                'CategoryCode' => $info['CategoryCode']
            ];
        } else {
            $response['updated']++;
            $response['updatedRows'][] = [
                'StudentID' => $info['StudentID'],
                'CategoryID' => $info['CategoryID'],
                'CategoryCode' => $info['CategoryCode']
            ];
        }
    }

    // Delete outdated
    foreach ($existingRelations as $key => $studentID) {
        if (!isset($desiredRelations[$key])) {
            [$student_id, $categoryID] = explode('_', $key);
            $deleteStmt->execute([
                ':student_id' => $student_id,
                ':categoryID' => $categoryID
            ]);
            $response['deleted']++;
            $response['deletedRows'][] = [
                'StudentID' => $studentID,
                'CategoryID' => $categoryID,
                'CategoryCode' => $categoryMap[$categoryID] ?? 'Unknown'
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