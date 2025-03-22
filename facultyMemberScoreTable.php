<?php
session_start();
require_once 'api/authMiddleware.php';
// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/database/dbConnection.php';

// Initialize variables to store results
$facultyScores = [];
$errorMessage = null;
$successMessage = null;

// Process the form submission if year and category are provided
if (isset($_GET['year']) && isset($_GET['category'])) {
    $yearId = intval($_GET['year']);
    $categoryId = intval($_GET['category']);
    
    try {
        // Validate the academic year
        $stmtYear = $pdo->prepare("
            SELECT YearID, Academic_year
            FROM AcademicYear_Table
            WHERE YearID = ?
        ");
        $stmtYear->execute([$yearId]);
        $yearRow = $stmtYear->fetch(PDO::FETCH_ASSOC);

        if (!$yearRow) {
            $errorMessage = 'Selected academic year does not exist.';
        } else {
            $academicYearName = $yearRow['Academic_year'];

            // Fetch candidates' scores and vote breakdown
            $stmt = $pdo->prepare("
                SELECT
                    v.CandidateID,
                    c.Name AS candidate_name,
                    c.Mail AS candidate_email,
                    c.Role AS candidate_role,
                    COALESCE(SUM(v.Points), 0) AS total_points,
                    COALESCE(SUM(CASE WHEN v.Points = 1 THEN 1 ELSE 0 END), 0) AS points_1_count,
                    COALESCE(SUM(CASE WHEN v.Points = 2 THEN 1 ELSE 0 END), 0) AS points_2_count,
                    COALESCE(SUM(CASE WHEN v.Points = 3 THEN 1 ELSE 0 END), 0) AS points_3_count,
                    COALESCE(SUM(CASE WHEN v.Points = 4 THEN 1 ELSE 0 END), 0) AS points_4_count,
                    COALESCE(SUM(CASE WHEN v.Points = 5 THEN 1 ELSE 0 END), 0) AS points_5_count,
                    COALESCE(SUM(CASE WHEN v.Points = 6 THEN 1 ELSE 0 END), 0) AS points_6_count
                FROM Votes_Table v
                INNER JOIN Candidate_Table c ON v.CandidateID = c.id
                WHERE v.CategoryID = ?
                  AND v.AcademicYear = ?
                GROUP BY v.CandidateID, c.Name, c.Mail, c.Role
                ORDER BY total_points DESC
            ");

            $stmt->execute([$categoryId, $yearId]);
            $facultyScores = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($facultyScores)) {
                $errorMessage = 'No votes found for the selected category and year.';
            } else {
                // Ensure all vote count fields are included and add academic year
                foreach ($facultyScores as &$row) {
                    $row['points_1_count'] = isset($row['points_1_count']) ? (int)$row['points_1_count'] : 0;
                    $row['points_2_count'] = isset($row['points_2_count']) ? (int)$row['points_2_count'] : 0;
                    $row['points_3_count'] = isset($row['points_3_count']) ? (int)$row['points_3_count'] : 0;
                    $row['points_4_count'] = isset($row['points_4_count']) ? (int)$row['points_4_count'] : 0;
                    $row['points_5_count'] = isset($row['points_5_count']) ? (int)$row['points_5_count'] : 0;
                    $row['points_6_count'] = isset($row['points_6_count']) ? (int)$row['points_6_count'] : 0;
                    $row['total_points'] = isset($row['total_points']) ? (int)$row['total_points'] : 0;
                    $row['Academic_year'] = $academicYearName;
                }
                $successMessage = 'Data loaded successfully.';
            }
        }
    } catch (Exception $e) {
        $errorMessage = 'Database error: ' . $e->getMessage();
    }
}

// Fetch available academic years & categories from the database
try {
    // 1) Academic Years
    $stmtYears = $pdo->prepare("SELECT YearID, Academic_year FROM AcademicYear_Table ORDER BY YearID DESC");
    $stmtYears->execute();
    $academicYears = $stmtYears->fetchAll(PDO::FETCH_ASSOC);

    // 2) Categories
    $stmtCats = $pdo->prepare("SELECT CategoryID, CategoryCode FROM Category_Table ORDER BY CategoryID ASC");
    $stmtCats->execute();
    $categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching data: " . $e->getMessage());
}

// Convert faculty scores to JSON for JavaScript
$facultyScoresJson = json_encode(['facultyScores' => $facultyScores]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Faculty Scores by Category &amp; Year</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet">
    <link href="assets/css/components.min.css" rel="stylesheet">
    <link href="assets/css/layout.min.css" rel="stylesheet">
    <link href="assets/css/all.min.css" rel="stylesheet">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">

    <!-- JavaScript - Use CDN versions to avoid 404 errors -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/custom.js"></script>

    <!-- Grid.js CSS/JS -->
    <link href="https://cdn.jsdelivr.net/npm/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/gridjs/dist/gridjs.umd.js"></script>
   
    <!-- For CSV file saving -->
    <script src="https://cdn.jsdelivr.net/npm/file-saver/dist/FileSaver.min.js"></script>
   
    <style>
        body {
            overflow: auto;
        }
        .title {
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
        }
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
        .container .form-select {
            width: 200px;
        }
        .mb-4, .my-4 {
            margin-bottom: 1.5rem !important;
        }
        .table-container {
            margin: 20px;
        }
        /* Add styles for point distribution cells */
        .point-cell {
            text-align: center;
            font-weight: bold;
        }
        /* Visualization for point distribution */
        .point-bar {
            height: 20px;
            background-color: #4a90e2;
            margin: 2px 0;
            border-radius: 2px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="title">All Faculty Scores by Category &amp; Year</div>

    <!-- Filter Form -->
    <div class="mb-4 d-flex justify-content-center">
        <form id="filter-form" class="d-flex" method="get">
            <!-- Year Dropdown (label: e.g. 2024, value: e.g. 1) -->
            <select id="year" name="year" class="form-select me-3" required>
                <option value="" disabled <?php echo !isset($_GET['year']) ? 'selected' : ''; ?>>Select Year</option>
                <?php foreach($academicYears as $y): ?>
                    <option value="<?= $y['YearID'] ?>" <?php echo (isset($_GET['year']) && $_GET['year'] == $y['YearID']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($y['Academic_year']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Category Dropdown (label: e.g. B, value: e.g. 3) -->
            <select id="category" name="category" class="form-select me-3" required>
                <option value="" disabled <?php echo !isset($_GET['category']) ? 'selected' : ''; ?>>Select Category</option>
                <?php foreach($categories as $c): ?>
                    <option value="<?= $c['CategoryID'] ?>" <?php echo (isset($_GET['category']) && $_GET['category'] == $c['CategoryID']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($c['CategoryCode']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary">View Scores</button>
        </form>
    </div>

    <!-- Error Message Display -->
    <?php if ($errorMessage): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
    <?php endif; ?>

    <!-- Grid.js Table Container -->
    <div class="table-container">
        <div id="scores-grid" class="gridjs-example"></div>
    </div>
</div>

<!-- Fixed Action Container (Download CSV, then Return Buttons) -->
<div class="action-container">
    <button class="action-button" id="downloadBtn" style="<?= empty($facultyScores) ? 'display:none;' : '' ?>">
        Download CSV
    </button>
    <button class="return-button" onclick="window.location.href='reportPage.php'">
        Return to Category Page
    </button>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // Load the scores data from PHP
    const facultyScoresData = <?= !empty($facultyScores) ? $facultyScoresJson : '{"facultyScores":[]}' ?>;
    let currentData = facultyScoresData.facultyScores || [];
    const downloadButton = document.getElementById('downloadBtn');
    
    // Render Grid.js table if we have data
    if (currentData.length > 0) {
        renderGrid(currentData);
        downloadButton.style.display = 'block';
    }

    // Render Grid.js table with additional columns for vote counts
    function renderGrid(dataArray) {
        new gridjs.Grid({
            columns: [
                { name: "ID", id: "CandidateID" },
                { name: "Name", id: "candidate_name" },
                { name: "Email", id: "candidate_email" },
                { name: "Role", id: "candidate_role" },
                { name: "Total Points", id: "total_points" },
                {
                    name: "6-Point Votes",
                    id: "points_6_count",
                    formatter: (cell) => gridjs.html(`<div class="point-cell">${cell || 0}</div>`)
                },
                {
                    name: "5-Point Votes",
                    id: "points_5_count",
                    formatter: (cell) => gridjs.html(`<div class="point-cell">${cell || 0}</div>`)
                },
                {
                    name: "4-Point Votes",
                    id: "points_4_count",
                    formatter: (cell) => gridjs.html(`<div class="point-cell">${cell || 0}</div>`)
                },
                {
                    name: "3-Point Votes",
                    id: "points_3_count",
                    formatter: (cell) => gridjs.html(`<div class="point-cell">${cell || 0}</div>`)
                },
                {
                    name: "2-Point Votes",
                    id: "points_2_count",
                    formatter: (cell) => gridjs.html(`<div class="point-cell">${cell || 0}</div>`)
                },
                {
                    name: "1-Point Votes",
                    id: "points_1_count",
                    formatter: (cell) => gridjs.html(`<div class="point-cell">${cell || 0}</div>`)
                },
                { name: "Academic Year", id: "Academic_year" }
            ],
            data: dataArray,
            search: true,
            sort: true,
            pagination: {
                limit: 8,
                summary: true
            },
            className: {
                table: 'table table-bordered table-striped'
            },
            style: {
                table: {
                    'margin': '0 auto'
                }
            }
        }).render(document.getElementById('scores-grid'));
    }

    // CSV download functionality with all columns included
    downloadButton.addEventListener('click', () => {
        if (!currentData.length) {
            alert('No data to download.');
            return;
        }

        const headers = [
            "CandidateID",
            "Name",
            "Email", 
            "Role",
            "Total Points",
            "6-Point Votes",
            "5-Point Votes", 
            "4-Point Votes",
            "3-Point Votes",
            "2-Point Votes",
            "1-Point Votes",
            "Academic Year"
        ];

        const rows = currentData.map(item => [
            item.CandidateID,
            item.candidate_name,
            item.candidate_email,
            item.candidate_role,
            item.total_points,
            item.points_6_count,
            item.points_5_count,
            item.points_4_count,
            item.points_3_count,
            item.points_2_count,
            item.points_1_count,
            item.Academic_year
        ].join(';'));

        // Use UTF-8 BOM (\uFEFF) so Excel handles special characters
        const csvContent = "\uFEFF" + [headers.join(';'), ...rows].join("\n");
        const blob = new Blob([csvContent], {type: "text/csv;charset=utf-8;"});
        saveAs(blob, "faculty_scores.csv");
    });
});
</script>
</body>
</html>