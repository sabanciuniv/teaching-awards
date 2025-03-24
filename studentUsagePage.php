<?php
session_start();

// In dev, display errors (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include DB connection
require_once __DIR__ . '/database/dbConnection.php';

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
            echo json_encode(['message' => 'No students found for the selected year/category.']);
            exit;
        }

        // 3) Build final array with a SINGLE "VoteStatus" for each student
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

// Fetch categories for the dropdown
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

        /* Buttons for Download & Return */
        .action-container {
            position: fixed;
            bottom: 20px;    
            right: 20px;    
        }
        .action-button, .return-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 10px;
            width: 160px;
            text-align: center;
        }
        .action-button:hover, .return-button:hover {
            background-color: #0056b3;
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

<!-- FileSaver.js for CSV download -->
<script src="https://cdn.jsdelivr.net/npm/file-saver/dist/FileSaver.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const filterForm = document.getElementById('filter-form');
    const errorMessage = document.getElementById('error-message');

    const tabSection = document.getElementById('tab-section');
    const notVotedTab = document.getElementById('not-voted-tab');
    const gridNotVotedDiv = document.getElementById('grid-not-voted');
    const gridVotedDiv = document.getElementById('grid-voted');

    const notifyBtn = document.getElementById('notify-button');
    
    // Attach these arrays to the window object
    // so the "Download CSV" button can access them.
    window.globalNotVotedData = [];
    window.globalVotedData = [];

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

            // Separate "Voted" vs "Not Voted"
            const votedStudents = data.students.filter(s => s.VoteStatus === 'Voted');
            const notVotedStudents = data.students.filter(s => s.VoteStatus === 'Not Voted');

            // Save them to window for CSV download
            window.globalNotVotedData = notVotedStudents;
            window.globalVotedData = votedStudents;

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

            // Show the Download CSV button if we have any data
            if (data.students.length > 0) {
                document.getElementById('downloadBtn').style.display = 'block';
            } else {
                document.getElementById('downloadBtn').style.display = 'none';
            }

        } catch (err) {
            console.error(err);
            showError('An error occurred while fetching data.');
        }
    });

    // Notify Students click listener
    notifyBtn.addEventListener("click", async () => {
        const yearSelect = document.getElementById('year');
        const categorySelect = document.getElementById('category');
        const yearText = yearSelect.options[yearSelect.selectedIndex].text;
        const categoryText = categorySelect.options[categorySelect.selectedIndex].text;
        
        if (!notVotedGridInstance) {
            alert("No data available. Please select an academic year and category first.");
            return;
        }
        
        // Get data from the grid
        const notVotedData = notVotedGridInstance.config?.data || [];
        
        if (notVotedData.length === 0) {
            alert("Great news! All students have already voted.");
            return;
        }
        
        // Confirm before sending emails
        if (!confirm(`You are about to send notification emails to ${notVotedData.length} students who haven't voted in ${categoryText === "All Categories" ? "any category" : `the ${categoryText} category`}. Continue?`)) {
            return;
        }
        
        // Show loading indicator
        const originalBtnText = notifyBtn.textContent;
        notifyBtn.disabled = true;
        notifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending emails...';
        
        // Format the data for the server
        const students = notVotedData.map(student => ({
            StudentID: student.StudentID,
            StudentFullName: student.StudentFullName,
            CGPA: student.CGPA,
            Mail: student.Mail,
            SuNET_Username: student.SuNET_Username,
            VoteStatus: student.VoteStatus
        }));
        
        try {
            const response = await fetch("notifyStudents.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    year: yearText,
                    category: categoryText,
                    students: students
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            
            const result = await response.json();
            
            if (result.error) {
                alert(`Error: ${result.error}`);
            } else {
                // Show detailed results
                let message = `📧 Email Notification Results:\n\n`;
                message += `✅ Successfully sent: ${result.sent} of ${result.total} emails\n`;
                
                if (result.failed && result.failed.length > 0) {
                    message += `Failed: ${result.failed.length} emails\n\n`;
                    
                    if (result.failed.length <= 5) {
                        message += "Failed emails:\n";
                        result.failed.forEach(fail => {
                            message += `- ${fail.email}: ${fail.reason || 'Unknown error'}\n`;
                        });
                    } else {
                        message += "Too many failures to display. Check server logs for details.";
                    }
                } else {
                    message += `\nAll emails were sent successfully!`;
                }
                
                alert(message);
            }
        } catch (err) {
            console.error("Error sending notifications:", err);
            alert(`Failed to send emails: ${err.message}`);
        } finally {
            // Restore button state
            notifyBtn.disabled = false;
            notifyBtn.textContent = originalBtnText;
        }
    });
});
</script>

<!-- Fixed Action Container for "Download CSV" & "Return" -->
<div class="action-container">
    <button class="action-button" id="downloadBtn" style="display:none;">
        Download CSV
    </button>
    <button class="return-button" onclick="window.location.href='reportPage.php'">
        Return to Category Page
    </button>
</div>

<script>
// The "Download CSV" button merges globalNotVotedData + globalVotedData
document.getElementById('downloadBtn').addEventListener('click', () => {
    // Pull from window object
    const notVoted = window.globalNotVotedData || [];
    const voted = window.globalVotedData || [];
    const combinedData = notVoted.concat(voted);

    if (!combinedData.length) {
        alert('No data to download.');
        return;
    }

    // Create CSV headers
    const headers = [
        "StudentID",
        "StudentFullName",
        "CGPA",
        "Mail",
        "SuNET_Username",
        "VoteStatus"
    ];

    // Convert each row to semicolon-separated strings
    const rows = combinedData.map(item => [
        item.StudentID,
        item.StudentFullName,
        item.CGPA,
        item.Mail,
        item.SuNET_Username,
        item.VoteStatus
    ].join(';'));

    // Use UTF-8 BOM (\uFEFF) so Excel handles special characters
    const csvContent = "\uFEFF" + [headers.join(';'), ...rows].join("\n");
    const blob = new Blob([csvContent], {type: "text/csv;charset=utf-8;"});

    // Use FileSaver.js to save
    saveAs(blob, "student_usage_data.csv");
});
</script>

</body>
</html>
