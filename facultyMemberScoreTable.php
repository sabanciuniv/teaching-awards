<?php
session_start();
require_once __DIR__ . '/database/dbConnection.php'; // Adjust if needed

// Fetch available academic years from DB
try {
    $stmtYears = $pdo->prepare("SELECT YearID, Academic_year FROM AcademicYear_Table ORDER BY YearID DESC");
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
    <title>All Faculty Scores by Category &amp; Year</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Include your CSS/JS as in your example -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet">
    <link href="assets/css/components.min.css" rel="stylesheet">
    <link href="assets/css/layout.min.css" rel="stylesheet">
    <link href="assets/css/all.min.css" rel="stylesheet">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">
    
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/global_assets/js/main/jquery.min.js"></script>
    <script src="assets/global_assets/js/main/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/custom.js"></script>

    <!-- Grid.js CSS -->
    <link href="https://cdn.jsdelivr.net/npm/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />

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
            position: fixed; /* Stick to the bottom */
            bottom: 20px;    
            right: 20px;     
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        /* Shared button style for BOTH Return & Download CSV */
        .action-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 10px;
            width: 130px;
            text-align: center;
        }
        .action-button:hover {
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
    </style>
</head>
<body>

<div class="container">
    <div class="title">All Faculty Scores by Category &amp; Year</div>

    <!-- Filter Form -->
    <div class="mb-4 d-flex justify-content-center">
        <form id="filter-form" class="d-flex">
            <!-- Year Dropdown -->
            <select id="year" class="form-select me-3" required>
                <option value="" disabled selected>Select Year</option>
                <?php foreach($academicYears as $y): ?>
                    <option value="<?= $y['YearID'] ?>">
                        <?= htmlspecialchars($y['Academic_year']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Category Dropdown (integer IDs) -->
            <select id="category" class="form-select me-3" required>
                <option value="" disabled selected>Select Category</option>
                <option value="1">A1</option>
                <option value="2">A2</option>
                <option value="3">B</option>
                <option value="4">C</option>
                <option value="5">D</option>
            </select>

            <button type="submit" class="btn btn-primary">View Scores</button>
        </form>
    </div>

    <!-- Grid.js Table Container -->
    <div class="table-container">
        <div class="gridjs-example" id="scores-grid"></div>
    </div>

    <!-- Error Message Display -->
    <div id="error-message" class="alert alert-danger d-none"></div>
</div>

<!-- Fixed Action Container (Return & Download Buttons) -->
<div class="action-container">
    <button class="action-button" onclick="window.location.href='reportPage.php'">
        Return
    </button>
    <button class="action-button" id="downloadBtn">
        Download CSV
    </button>
</div>

<!-- Grid.js and FileSaver for CSV -->
<script src="https://cdn.jsdelivr.net/npm/gridjs/dist/gridjs.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/file-saver/dist/FileSaver.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    let currentData = [];  // Will store the results for CSV export

    const filterForm = document.getElementById('filter-form');
    const errorMessage = document.getElementById('error-message');
    const scoresGrid = document.getElementById('scores-grid');
    let gridInstance; // We'll keep a reference to the Grid.js instance

    // Helper: Create or re-render the Grid.js table
    function renderGrid(dataArray) {
        // If grid already created, destroy it first
        if (gridInstance) {
            gridInstance.destroy();
        }

        // Build the new instance
        gridInstance = new gridjs.Grid({
            columns: [
                "CandidateID",
                "Name",
                "Email",
                "Role",
                "Total Points",
                "Academic Year"
            ],
            data: dataArray.map(item => [
                item.CandidateID,
                item.candidate_name,
                item.candidate_email,
                item.candidate_role,
                item.total_points,
                item.Academic_year
            ]),
            search: true,
            sort: true,
            pagination: {
                limit: 8,
                summary: true
            },
            className: {
                table: 'table table-bordered'
            },
            style: {
                table: {
                    'margin': '0 auto'
                }
            }
        });
        gridInstance.render(scoresGrid);
    }

    // Filter form submission
    filterForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const year = document.getElementById('year').value;
        const category = document.getElementById('category').value;

        if (!year || !category) {
            alert("Please select a year and a category.");
            return;
        }

        // Clear old error
        errorMessage.classList.add('d-none');
        errorMessage.textContent = "";

        try {
            const url = `getFacultyMemberScores.php?year=${year}&category=${category}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' }
            });
            const data = await response.json();

            // Check for error or message
            if (data.error) {
                errorMessage.textContent = data.error;
                errorMessage.classList.remove('d-none');
                if (gridInstance) gridInstance.destroy();
                return;
            }
            if (data.message) {
                errorMessage.textContent = data.message;
                errorMessage.classList.remove('d-none');
                if (gridInstance) gridInstance.destroy();
                return;
            }

            if (data.facultyScores && data.facultyScores.length > 0) {
                // Store data for CSV
                currentData = data.facultyScores;

                // Render table
                renderGrid(currentData);
            }
        } catch (error) {
            console.error("Error fetching data:", error);
            errorMessage.textContent = "An error occurred while fetching data. Please try again.";
            errorMessage.classList.remove('d-none');
        }
    });

    // Download CSV button
    const downloadButton = document.getElementById('downloadBtn');
    downloadButton.addEventListener('click', () => {
        if (!currentData.length) {
            alert("No data to download. Please fetch scores first.");
            return;
        }

        // Build CSV
        const headers = [
            "CandidateID",
            "Name",
            "Email",
            "Role",
            "Total Points",
            "Academic Year"
        ];

        // Convert each row to semicolon-delimited (change to commas if you prefer)
        const rows = currentData.map(item => [
            item.CandidateID,
            item.candidate_name,
            item.candidate_email,
            item.candidate_role,
            item.total_points,
            item.Academic_year
        ].join(';'));

        const csvContent = "\uFEFF" + [headers.join(';'), ...rows].join("\n");
        const encodedUri = "data:text/csv;charset=utf-8," + encodeURI(csvContent);

        // Create a hidden link and click it
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "faculty_scores.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});
</script>
</body>
</html>
