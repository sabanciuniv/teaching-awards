<?php
session_start();

// In dev, display errors (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include DB connection
require_once __DIR__ . '/database/dbConnection.php';

/*
  This file handles two modes:
  1) ?ajax=1 => Returns JSON (the student data)
  2) Normal => Renders the HTML page
*/

// ---------------------------------------------------------------------
// 1) AJAX MODE: Return JSON for the selected year
// ---------------------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');

    // A helper function that returns "Voted", "Not Voted", or "-" for a given student & category
    function getCategoryStatus($pdo, $studentId, $yearId, $catId) {
        // 1) Check if student is in Student_Category_Relation for that category
        $relStmt = $pdo->prepare("
            SELECT 1
            FROM Student_Category_Relation
            WHERE student_id = :sid
              AND categoryID  = :cid
            LIMIT 1
        ");
        $relStmt->execute([
            ':sid' => $studentId,
            ':cid' => $catId
        ]);
        $isRelated = $relStmt->fetchColumn();

        if (!$isRelated) {
            // If no relation => show "-"
            return "-";
        }

        // 2) If related, check if there's a row in Votes_Table => "Voted" or "Not Voted"
        //    We look for (AcademicYear = :yid, VoterID = :sid, CategoryID = :cid)
        $voteStmt = $pdo->prepare("
            SELECT 1
            FROM Votes_Table
            WHERE AcademicYear = :yid
              AND VoterID      = :sid
              AND CategoryID   = :cid
            LIMIT 1
        ");
        $voteStmt->execute([
            ':yid' => $yearId,
            ':sid' => $studentId,
            ':cid' => $catId
        ]);
        $hasVote = $voteStmt->fetchColumn();

        return $hasVote ? "Voted" : "Not Voted";
    }

    // Get the year ID
    $yearId = isset($_GET['year']) ? intval($_GET['year']) : 0;
    if ($yearId <= 0) {
        echo json_encode(['error' => 'Invalid year selected.']);
        exit;
    }

    try {
        // Fetch all students for this YearID
        $stmt = $pdo->prepare("
            SELECT 
                s.id AS student_primary,
                s.StudentID,
                s.StudentFullName,
                s.Mail,
                s.SuNET_Username,
                s.CGPA
            FROM Student_Table s
            WHERE s.YearID = :yid
            ORDER BY s.StudentID
        ");
        $stmt->execute([':yid' => $yearId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            echo json_encode(['message' => 'No students found for the selected year.']);
            exit;
        }

        // Build final array
        $final = [];
        foreach ($rows as $r) {
            $studId = $r['student_primary'];

            // Check categories 1..5 => A1, A2, B, C, D
            $voteA1 = getCategoryStatus($pdo, $studId, $yearId, 1); // CategoryID=1 => A1
            $voteA2 = getCategoryStatus($pdo, $studId, $yearId, 2); // CategoryID=2 => A2
            $voteB  = getCategoryStatus($pdo, $studId, $yearId, 3); // CategoryID=3 => B
            $voteC  = getCategoryStatus($pdo, $studId, $yearId, 4); // CategoryID=4 => C
            $voteD  = getCategoryStatus($pdo, $studId, $yearId, 5); // CategoryID=5 => D

            $final[] = [
                'StudentID'       => $r['StudentID'],
                'StudentFullName' => $r['StudentFullName'],
                'Mail'            => $r['Mail'],
                'SuNET_Username'  => $r['SuNET_Username'],
                'CGPA'            => $r['CGPA'],

                'A1' => $voteA1,
                'A2' => $voteA2,
                'B'  => $voteB,
                'C'  => $voteC,
                'D'  => $voteD
            ];
        }

        echo json_encode(['students' => $final]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// ---------------------------------------------------------------------
// 2) NORMAL MODE: Render the HTML page with the form and Grid.js
// ---------------------------------------------------------------------

// Fetch academic years for the dropdown
try {
    $stmtYears = $pdo->prepare("
        SELECT YearID, Academic_year
        FROM AcademicYear_Table
        ORDER BY YearID DESC
    ");
    $stmtYears->execute();
    $academicYears = $stmtYears->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching academic years: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Table by Year (Voted / Not Voted)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap CSS (CDN for demo; adjust as needed) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Grid.js CSS -->
    <link href="https://cdn.jsdelivr.net/npm/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />

    <style>
        body {
            margin: 20px;
        }
        .title {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
        }
        #error-message {
            display: none; /* hidden by default; shown if an error occurs */
        }
    </style>
</head>
<body>

<h1 class="title">Student Table by Year (Voted / Not Voted)</h1>

<!-- Dropdown form to select the academic year -->
<div class="mb-4 d-flex justify-content-center">
    <form id="filter-form" class="d-flex">
        <select id="year" class="form-select me-2" required>
            <option value="" disabled selected>Select Academic Year</option>
            <?php foreach($academicYears as $y): ?>
                <option value="<?= htmlspecialchars($y['YearID']) ?>">
                    <?= htmlspecialchars($y['Academic_year']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">View Students</button>
    </form>
</div>

<div class="container">
    <!-- Grid.js will render the table here -->
    <div id="students-grid"></div>

    <!-- Error message container -->
    <div id="error-message" class="alert alert-danger mt-3"></div>
</div>

<!-- JS Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gridjs/dist/gridjs.umd.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    let gridInstance;
    const filterForm = document.getElementById('filter-form');
    const errorMessage = document.getElementById('error-message');
    const studentsGrid = document.getElementById('students-grid');

    // Helper to show/hide error messages
    function showError(msg) {
        errorMessage.textContent = msg;
        errorMessage.style.display = 'block';
    }
    function hideError() {
        errorMessage.style.display = 'none';
        errorMessage.textContent = '';
    }

    // Render the data table using Grid.js
    function renderGrid(dataArray) {
        // Destroy existing grid if present
        if (gridInstance) {
            gridInstance.destroy();
        }

        gridInstance = new gridjs.Grid({
            columns: [
                { id: 'StudentID',       name: 'Student ID' },
                { id: 'StudentFullName', name: 'Student Name' },
                { id: 'CGPA',            name: 'GPA' },
                { id: 'Mail',            name: 'Email' },
                { id: 'SuNET_Username',  name: 'SUNET Username' },

                // 5 columns for categories (A1, A2, B, C, D)
                { id: 'A1', name: 'A1' },
                { id: 'A2', name: 'A2' },
                { id: 'B',  name: 'B' },
                { id: 'C',  name: 'C' },
                { id: 'D',  name: 'D' }
            ],
            data: dataArray,
            search: true,
            sort: true,
            pagination: {
                limit: 10,
                summary: true
            },
            className: {
                table: 'table table-bordered'
            }
        });

        gridInstance.render(studentsGrid);
    }

    // On form submit, fetch student data for the selected year
    filterForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError(); // Hide any old errors

        const yearId = document.getElementById('year').value;
        if (!yearId) {
            showError('Please select an academic year.');
            return;
        }

        // We call the same page with ?ajax=1 to return JSON
        const url = `studentUsagePage.php?ajax=1&year=${yearId}`;

        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Handle errors/messages from server
            if (data.error) {
                showError(data.error);
                if (gridInstance) gridInstance.destroy();
                return;
            }
            if (data.message) {
                showError(data.message);
                if (gridInstance) gridInstance.destroy();
                return;
            }

            // If we have students, render them; otherwise show a "no data" message
            if (data.students && data.students.length > 0) {
                renderGrid(data.students);
            } else {
                showError('No data found.');
            }

        } catch (err) {
            console.error(err);
            showError('An error occurred while fetching data.');
        }
    });
});
</script>

</body>
</html>
