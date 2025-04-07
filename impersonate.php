<?php
session_start();
require_once 'database/dbConnection.php';

// Only allow admin
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    die("Access denied. Only admins can impersonate.");
}


if (!isset($_GET['id'])) {
    die("Missing student ID.");
}

$studentID = $_GET['id'];

// Store original admin identity - store ALL session variables that should be preserved
$_SESSION['admin_user'] = $_SESSION['user'];
$_SESSION['admin_role'] = $_SESSION['role'];
$_SESSION['admin_firstname'] = $_SESSION['firstname'] ?? '';
$_SESSION['admin_lastname'] = $_SESSION['lastname'] ?? '';
// Store any other important session variables

// Fetch student info
$stmt = $pdo->prepare("SELECT * FROM Student_Table WHERE StudentID = ?");
$stmt->execute([$studentID]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Right after fetching student info
$stmt = $pdo->prepare("SELECT * FROM Student_Table WHERE StudentID = ?");
$stmt->execute([$studentID]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Debug - print the student info
echo "<pre>";
print_r($student);
echo "</pre>";
exit;

if (!$student) {
    die("Student not found.");
}

// Set impersonated identity
$_SESSION['impersonated_user'] = $student['SuNET_Username'];
$_SESSION['user'] = $student['SuNET_Username'];
$_SESSION['role'] = 'student';
$_SESSION['firstname'] = $student['StudentFullName']; // Set firstname for display purposes
$_SESSION['lastname'] = ''; // Clear lastname since full name is in firstname
$_SESSION['impersonating'] = true;
$_SESSION['impersonated_full_name'] = $student['StudentFullName'];
$_SESSION['student_id'] = $student['id']; // Store student ID for reference
$_SESSION['year_id'] = $student['YearID']; // ✅ used for filtering in voting APIs


// Force fresh load of session
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Location: index.php");
exit;
?>
