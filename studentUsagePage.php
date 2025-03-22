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
// 1) AJAX MODE: Return JSON for the selected year & category
// ---------------------------------------------------------------------
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json; charset=utf-8');

    // Helper: returns "Voted", "Not Voted", or "-" for a given student & category
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

    // Get the year and category from the query string
    $yearId = isset($_GET['year']) ? intval($_GET['year']) : 0;
    $categoryId = isset($_GET['category']) ? intval($_GET['category']) : 0;

    if ($yearId <= 0) {
        echo json_encode(['error' => 'Invalid year selected.']);
        exit;
    }

    try {
        // 1) Fetch all students for this YearID
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

        // 2) If categoryId > 0, filter out students NOT related to that category
        //    This ensures we only list students who have that category in Student_Category_Relation
        $filteredRows = [];
        if ($categoryId > 0) {
            $relCheck = $pdo->prepare("
                SELECT 1
                FROM Student_Category_Relation
                WHERE student_id = :sid
                  AND categoryID = :cid
                LIMIT 1
            ");
            foreach ($rows as $r) {
                $relCheck->execute([
                    ':sid' => $r['student_primary'],
                    ':cid' => $categoryId
                ]);
                if ($relCheck->fetchColumn()) {
                    // This student is in the chosen category
                    $filteredRows[] = $r;
                }
            }
        } else {
            // categoryId=0 => no category filter (All Categories)
            $filteredRows = $rows;
        }

        if (!$filteredRows) {
            // After filtering, if no students remain:
            echo json_encode(['message' => 'No students found for the selected year/category.']);
            exit;
        }

        // 3) Build final array with a SINGLE "VoteStatus" for each student
        //    If a specific category was chosen, check that one.
        //    If "All Categories" (categoryId=0), check categories 1..5.
        $final = [];
        foreach ($filteredRows as $r) {
            $studId = $r['student_primary'];

            if ($categoryId > 0) {
                // Check only the chosen category
                $voteStatus = getCategoryStatus($pdo, $studId, $yearId, $categoryId);
            } else {
                // "All Categories" => if ANY category=Voted => "Voted"
                // otherwise if ANY category=Not Voted => "Not Voted"
                // else "-"
                $catList = [1,2,3,4,5];
                $foundVoted = false;
                $foundNotVoted = false;

                foreach ($catList as $c) {
                    $status = getCategoryStatus($pdo, $studId, $yearId, $c);
                    if ($status === 'Voted') {
                        $foundVoted = true;
                    } elseif ($status === 'Not Voted') {
                        $foundNotVoted = true;
                    }
                }

                if ($foundVoted) {
                    $voteStatus = 'Voted';
                } elseif ($foundNotVoted) {
                    $voteStatus = 'Not Voted';
                } else {
                    $voteStatus = '-';
                }
            }

            $final[] = [
                'StudentID'       => $r['StudentID'],
                'StudentFullName' => $r['StudentFullName'],
                'Mail'            => $r['Mail'],
                'SuNET_Username'  => $r['SuNET_Username'],
                'CGPA'            => $r['CGPA'],
                'VoteStatus'      => $voteStatus
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

// Fetch categories for the dropdown (e.g. A1..D, or however many you have)
try {
    $stmtCats = $pdo->prepare("
        SELECT CategoryID, CategoryCode
        FROM Category_Table
        ORDER BY CategoryID
    ");
    $stmtCats->execute();
    $categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching categories: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Table by Year & Category (Voted / Not Voted)</title>
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

<h1 class="title">Student Table by Year & Category (Voted / Not Voted)</h1>

<!-- Dropdown form to select the academic year & category -->
<div class="mb-4 d-flex justify-content-center">
    <form id="filter-form" class="d-flex">
        <!-- Year Dropdown -->
        <select id="year" class="form-select me-2" required>
            <option value="" disabled selected>Select Academic Year</option>
            <?php foreach($academicYears as $y): ?>
                <option value="<?= htmlspecialchars($y['YearID']) ?>">
                    <?= htmlspecialchars($y['Academic_year']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Category Dropdown -->
        <select id="category" class="form-select me-2" required>
            <option value="0" selected>All Categories</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat['CategoryID']) ?>">
                    <?= htmlspecialchars($cat['CategoryCode']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn btn-primary">View Students</button>
    </form>
</div>

<div class="container">
    <!-- Card with two tabs: Not Voted / Voted -->
    <div class="card card-body" id="tab-section" style="display: none;">

        <ul class="nav nav-tabs nav-justified" id="studentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="not-voted-tab" data-bs-toggle="tab" data-bs-target="#not-voted" type="button" role="tab">
                    Not Voted
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="voted-tab" data-bs-toggle="tab" data-bs-target="#voted" type="button" role="tab">
                    Voted
                </button>
            </li>
        </ul>

        <div class="tab-content mt-4" id="studentTabsContent">
            <div class="tab-pane fade show active" id="not-voted" role="tabpanel">
                <button id="notify-button" class="btn btn-warning mb-3">Notify Students</button>
                <div id="grid-not-voted"></div>
            </div>
            <div class="tab-pane fade" id="voted" role="tabpanel">
                <div id="grid-voted"></div>
            </div>
        </div>
    </div>

    <!-- Error message container -->
    <div id="error-message" class="alert alert-danger mt-3"></div>
</div>

<!-- JS Dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gridjs/dist/gridjs.umd.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const filterForm = document.getElementById('filter-form');
    const errorMessage = document.getElementById('error-message');

    const tabSection = document.getElementById('tab-section');
    const notVotedTab = document.getElementById('not-voted-tab');
    const gridNotVotedDiv = document.getElementById('grid-not-voted');
    const gridVotedDiv = document.getElementById('grid-voted');

    // Show/hide error messages
    function showError(msg) {
        errorMessage.textContent = msg;
        errorMessage.style.display = 'block';
    }
    function hideError() {
        errorMessage.style.display = 'none';
        errorMessage.textContent = '';
    }

    // Grids for each tab
    let notVotedGridInstance, votedGridInstance;

    // Destroy old grids if they exist
    function destroyGrids() {
        if (notVotedGridInstance) notVotedGridInstance.destroy();
        if (votedGridInstance) votedGridInstance.destroy();
    }

    // Handle form submission -> fetch data
    filterForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();

        const yearId = document.getElementById('year').value;
        const categoryId = document.getElementById('category').value;

        if (!yearId) {
            showError('Please select an academic year.');
            return;
        }

        // Construct the AJAX URL
        const url = `studentUsagePage.php?ajax=1&year=${yearId}&category=${categoryId}`;

        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            // Check for errors / messages
            if (data.error) {
                showError(data.error);
                destroyGrids();
                tabSection.style.display = "none";
                return;
            }
            if (data.message) {
                showError(data.message);
                destroyGrids();
                tabSection.style.display = "none";
                return;
            }

            // We have valid data; show the tab section
            tabSection.style.display = "block";

            // Auto-switch to the "Not Voted" tab
            notVotedTab.click();

            // Separate "Voted" vs "Not Voted" by the single "VoteStatus" field
            const votedStudents = data.students.filter(s => s.VoteStatus === 'Voted');
            const notVotedStudents = data.students.filter(s => s.VoteStatus === 'Not Voted');

            destroyGrids();

            // Render Not Voted students
            notVotedGridInstance = new gridjs.Grid({
                columns: [
                    { id: 'StudentID',       name: 'Student ID' },
                    { id: 'StudentFullName', name: 'Student Name' },
                    { id: 'CGPA',            name: 'GPA' },
                    { id: 'Mail',            name: 'Email' },
                    { id: 'SuNET_Username',  name: 'SUNET Username' },
                    { id: 'VoteStatus',      name: 'Vote Status' }
                ],
                data: notVotedStudents,
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
            notVotedGridInstance.render(gridNotVotedDiv);

            // Render Voted students
            votedGridInstance = new gridjs.Grid({
                columns: [
                    { id: 'StudentID',       name: 'Student ID' },
                    { id: 'StudentFullName', name: 'Student Name' },
                    { id: 'CGPA',            name: 'GPA' },
                    { id: 'Mail',            name: 'Email' },
                    { id: 'SuNET_Username',  name: 'SUNET Username' },
                    { id: 'VoteStatus',      name: 'Vote Status' }
                ],
                data: votedStudents,
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
            votedGridInstance.render(gridVotedDiv);

        } catch (err) {
            console.error(err);
            showError('An error occurred while fetching data.');
        }
    });
});

document.addEventListener("DOMContentLoaded", () => {
    // ADD this notify button listener after the existing form submission logic
    document.getElementById("notify-button").addEventListener("click", async () => {
        const categorySelect = document.getElementById("category");
        const categoryText = categorySelect.options[categorySelect.selectedIndex].text;

        // Grid.js keeps data in .config.data
        const notVotedData = notVotedGridInstance?.config?.data || [];

        if (notVotedData.length === 0) {
            alert("No students to notify.");
            return;
        }

        // Convert Grid.js data back to object structure expected by PHP
        const students = notVotedData.map(row => ({
            StudentID: row[0],
            StudentFullName: row[1],
            CGPA: row[2],
            Mail: row[3],
            SuNET_Username: row[4],
            VoteStatus: row[5]
        }));

        try {
            const res = await fetch("notifyStudents.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    category: categoryText,
                    students: students
                })
            });

            const result = await res.json();

            if (result.error) {
                alert("Error: " + result.error);
            } else {
                alert(`Emails sent: ${result.sent}\nFailed: ${result.failed.length}`);
            }
        } catch (err) {
            console.error(err);
            alert("Failed to send emails.");
        }
    });
});

</script>

</body>
</html>
