<?php
session_start();

// Display errors in development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/database/dbConnection.php';

// Fetch academic years and categories
$stmtYears = $pdo->query("SELECT YearID, Academic_year FROM AcademicYear_Table ORDER BY YearID DESC");
$academicYears = $stmtYears->fetchAll(PDO::FETCH_ASSOC);

$stmtCats = $pdo->query("SELECT CategoryID, CategoryCode FROM Category_Table ORDER BY CategoryID ASC");
$categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);

// Fetch students and separate by vote status
$students = [];
$votedStudents = [];
$notVotedStudents = [];

if (isset($_GET['year']) && isset($_GET['category'])) {
    $yearId = (int)$_GET['year'];
    $categoryId = (int)$_GET['category'];

    $stmt = $pdo->prepare("SELECT * FROM Student_Table WHERE YearID = ? ORDER BY StudentID ASC");
    $stmt->execute([$yearId]);
    $allStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allStudents as $s) {
        $studentId = $s['id'];
        $voteStatus = "-";

        $rel = $pdo->prepare("SELECT 1 FROM Student_Category_Relation WHERE student_id = ? AND categoryID = ? LIMIT 1");
        $rel->execute([$studentId, $categoryId]);

        if ($rel->fetchColumn()) {
            $vote = $pdo->prepare("SELECT 1 FROM Votes_Table WHERE VoterID = ? AND CategoryID = ? AND AcademicYear = ? LIMIT 1");
            $vote->execute([$studentId, $categoryId, $yearId]);
            $voteStatus = $vote->fetchColumn() ? "Voted" : "Not Voted";
        }

        $entry = array_merge($s, ['VoteStatus' => $voteStatus]);
        $students[] = $entry;

        if ($voteStatus === 'Voted') {
            $votedStudents[] = $entry;
        } elseif ($voteStatus === 'Not Voted') {
            $notVotedStudents[] = $entry;
        }
    }
}

// -------------------------
// BEGIN: Admin Access Check
// -------------------------

// Make sure to assign the session user to a variable
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
$user = $_SESSION['user'];

try {
    // This query ensures the user exists in Admin_Table, is not marked as 'Removed',
    // and that their Role is either 'IT_Admin' or 'Admin'
    $adminQuery = "SELECT 1 
                     FROM Admin_Table 
                    WHERE AdminSuUsername = :username 
                      AND checkRole <> 'Removed'
                      AND Role IN ('IT_Admin', 'Admin')
                    LIMIT 1";
    $adminStmt = $pdo->prepare($adminQuery);
    $adminStmt->execute([':username' => $user]);
    
    // If no record is found, redirect to index.php
    if (!$adminStmt->fetch()) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Admin check failed: " . $e->getMessage());
}

// -----------------------
// END: Admin Access Check
// -----------------------
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Voting Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" rel="stylesheet">
    <style>
        .action-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
        }
        .return-button, .btn-custom {
            background-color: #45748a !important;
            color: white !important;
            border: none !important;
            padding: 10px 20px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            width: 200px;
            text-align: center;
            transition: 0.3s ease;
        }

        .return-button:hover, .btn-custom:hover {
            background-color: #365a6b !important;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h3 class="text-center mb-4">Student Voting Status</h3>
    <form class="mb-4 d-flex justify-content-center" method="GET">
        <select name="year" class="form-select w-auto me-2" required>
            <option value="" disabled selected>Select Year</option>
            <?php foreach ($academicYears as $y): ?>
                <option value="<?= $y['YearID'] ?>" <?= ($_GET['year'] ?? '') == $y['YearID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($y['Academic_year']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="category" class="form-select w-auto me-2" required>
            <option value="" disabled selected>Select Category</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c['CategoryID'] ?>" <?= ($_GET['category'] ?? '') == $c['CategoryID'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['CategoryCode']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-custom">View Students</button>
    </form>

    <?php if (!empty($students)): ?>
    <ul class="nav nav-tabs" id="voteTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="voted-tab" data-bs-toggle="tab" data-bs-target="#voted">Voted Students</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="not-voted-tab" data-bs-toggle="tab" data-bs-target="#notVoted">Not Voted Students</button>
        </li>
    </ul>

    <div class="tab-content mt-3">
        <div class="tab-pane fade show active" id="voted">
            <table id="votedTable" class="table table-striped" style="width:100%"></table>
        </div>
        <div class="tab-pane fade" id="notVoted">
            <table id="notVotedTable" class="table table-striped" style="width:100%"></table>
        </div>
    </div>

    <div class="mt-3 text-end">
        <button id="notifyBtn" class="btn btn-custom d-none">Notify Students</button>
    </div>
    <?php endif; ?>
</div>

<div class="action-container">
    <button class="return-button" onclick="window.location.href='reportPage.php'">
        <i class="fa fa-arrow-left"></i> Return to Reports Page
    </button>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
const votedData = <?= json_encode($votedStudents) ?>;
const notVotedData = <?= json_encode($notVotedStudents) ?>;
const category = <?= json_encode($_GET['category'] ?? '') ?>;

$(document).ready(function() {
    $('#votedTable').DataTable({
        data: votedData,
        columns: [
            { title: "Student ID", data: "StudentID" },
            { title: "Name", data: "StudentFullName" },
            { title: "Email", data: "Mail" },
            { title: "Username", data: "SuNET_Username" },
            { title: "GPA", data: "CGPA" }
        ],
        dom: 'Bfrtip',
        buttons: ['excel'],
    });

    $('#notVotedTable').DataTable({
        data: notVotedData,
        columns: [
            { title: "Student ID", data: "StudentID" },
            { title: "Name", data: "StudentFullName" },
            { title: "Email", data: "Mail" },
            { title: "Username", data: "SuNET_Username" },
            { title: "GPA", data: "CGPA" }
        ],
        dom: 'Bfrtip',
        buttons: ['excel'],
    });

    $('#voteTabs button').on('shown.bs.tab', function (event) {
        $('#notifyBtn').toggleClass('d-none', event.target.id !== 'not-voted-tab');
    });

    $('#notifyBtn').click(async function () {
        if (notVotedData.length === 0) return alert('No students to notify.');

        const confirmed = confirm(`Notify ${notVotedData.length} students who have not voted?`);
        if (!confirmed) return;

        const response = await fetch('notifyStudents.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ students: notVotedData, category: category })
        });

        const result = await response.json();
        alert(`Notification result: ${result.sent} sent, ${result.failed.length} failed.`);
    });
});
</script>
</body>
</html>
