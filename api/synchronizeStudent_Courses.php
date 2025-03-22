<?php
session_start();
require_once '../database/dbConnection.php';
header('Content-Type: application/json');

try {
    // Fetch students
    $stmt = $pdo->query("SELECT id, StudentID FROM Student_Table");
    $students = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $students[$row['StudentID']] = $row['id'];
    }

    // Fetch courses
    $stmt = $pdo->query("SELECT CourseID, CRN FROM Courses_Table");
    $courses = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $courses[$row['CRN']] = $row['CourseID'];
    }

    // Fetch current API data
    $stmt = $pdo->query("SELECT STU_ID, CRN FROM API_STUDENT_COURSES WHERE STU_ID IS NOT NULL");
    $apiRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Track active enrollments from API
    $apiEnrollments = [];

    foreach ($apiRows as $row) {
        $stuId = $row['STU_ID'];
        $crn = $row['CRN'];

        if (isset($students[$stuId]) && isset($courses[$crn])) {
            $studentId = $students[$stuId];
            $courseId = $courses[$crn];
            $key = "{$studentId}_{$courseId}";
            $apiEnrollments[$key] = [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'Stu_ID' => $stuId,
                'CRN' => $crn
            ];
        }
    }

    // Fetch existing student-course relations
    $stmt = $pdo->query("SELECT `student.id`, CourseID, EnrollmentStatus FROM Student_Course_Relation");
    $existingRelations = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $key = "{$row['student.id']}_{$row['CourseID']}";
        $existingRelations[$key] = $row['EnrollmentStatus'];
    }

    // Prepare SQL statements
    $insertStmt = $pdo->prepare("INSERT INTO Student_Course_Relation (`student.id`, CourseID, EnrollmentStatus) VALUES (:student_id, :course_id, 'enrolled')");
    $updateToEnrolledStmt = $pdo->prepare("UPDATE Student_Course_Relation SET EnrollmentStatus = 'enrolled' WHERE `student.id` = :student_id AND CourseID = :course_id");
    $updateToDroppedStmt = $pdo->prepare("UPDATE Student_Course_Relation SET EnrollmentStatus = 'dropped' WHERE `student.id` = :student_id AND CourseID = :course_id");

    // Counters & trackers
    $inserted = 0;
    $updatedToEnrolled = 0;
    $updatedToDropped = 0;

    $insertedRows = [];
    $updatedToEnrolledRows = [];
    $updatedToDroppedRows = [];

    // Insert or update to enrolled
    foreach ($apiEnrollments as $key => $data) {
        if (!isset($existingRelations[$key])) {
            $insertStmt->execute([
                ':student_id' => $data['student_id'],
                ':course_id' => $data['course_id']
            ]);
            $inserted++;
            $insertedRows[] = $data;
        } elseif ($existingRelations[$key] === 'dropped') {
            $updateToEnrolledStmt->execute([
                ':student_id' => $data['student_id'],
                ':course_id' => $data['course_id']
            ]);
            $updatedToEnrolled++;
            $updatedToEnrolledRows[] = $data;
        }
    }

    // Update to dropped
    foreach ($existingRelations as $key => $status) {
        if (!isset($apiEnrollments[$key]) && $status !== 'dropped') {
            [$studentId, $courseId] = explode('_', $key);
            $updateToDroppedStmt->execute([
                ':student_id' => $studentId,
                ':course_id' => $courseId
            ]);
            $updatedToDropped++;
            $updatedToDroppedRows[] = [
                'student_id' => $studentId,
                'course_id' => $courseId
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'inserted' => $inserted,
        'updated_to_enrolled' => $updatedToEnrolled,
        'updated_to_dropped' => $updatedToDropped,
        'insertedRows' => $insertedRows,
        'updatedToEnrolledRows' => $updatedToEnrolledRows,
        'updatedToDroppedRows' => $updatedToDroppedRows
    ]);
    exit();

} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'message' => "Error: " . $e->getMessage()
    ]);
}
