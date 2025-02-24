<?php
session_start();
require_once 'api/authMiddleware.php';
// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
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

    <!-- CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet">
    <link href="assets/css/components.min.css" rel="stylesheet">
    <link href="assets/css/layout.min.css" rel="stylesheet">
    <link href="assets/css/all.min.css" rel="stylesheet">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">

    <!-- JavaScript -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/global_assets/js/main/jquery.min.js"></script>
    <script src="assets/global_assets/js/main/bootstrap.bundle.min.js"></script>
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

        /* Shared styles for both buttons */
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

            <!-- Category Dropdown -->
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
        <div id="scores-grid" class="gridjs-example"></div>
    </div>

    <!-- Error Message Display -->
    <div id="error-message" class="alert alert-danger d-none"></div>
</div>

<!-- Fixed Action Container (Download CSV, then Return Buttons) -->
<div class="action-container">
    <!-- Hide the Download CSV button by default -->
    <button class="action-button" id="downloadBtn" style="display:none;">
        Download CSV
    </button>

    <!-- Return to Category Page Button -->
    <button class="return-button" onclick="window.location.href='reportPage.php'">
        Return to Category Page
    </button>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    let currentData = [];  // Store fetched results for CSV export
    const downloadButton = document.getElementById('downloadBtn');
    const filterForm = document.getElementById('filter-form');
    const errorMessage = document.getElementById('error-message');
    let gridInstance; // We'll keep a reference to the Grid.js instance

    // Helper function: Create or re-render the Grid.js table
    function renderGrid(dataArray) {
        // If grid already created, destroy it first
        if (gridInstance) {
            gridInstance.destroy();
        }

        // Initialize a new Grid.js instance
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

        gridInstance.render(document.getElementById('scores-grid'));
    }

    // Handle filter form submission
    filterForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Hide the download button each time we fetch new data
        downloadButton.style.display = 'none';

        // Clear any old error
        errorMessage.classList.add('d-none');
        errorMessage.textContent = '';

        const year = document.getElementById('year').value;
        const category = document.getElementById('category').value;

        // Validate dropdowns
        if (!year || !category) {
            alert('Please select a year and a category.');
            return;
        }

        // Attempt to fetch data from the backend
        try {
            const url = `getFacultyMemberScores.php?year=${year}&category=${category}`;
            const response = await fetch(url, { method: 'GET' });
            const data = await response.json();

            // Check for server-side errors
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

            // If we have scores, render them in the table
            if (data.facultyScores && data.facultyScores.length > 0) {
                currentData = data.facultyScores;
                renderGrid(currentData);

                // Show download button now that we have valid data
                downloadButton.style.display = 'block';
            } else {
                // No data
                if (gridInstance) gridInstance.destroy();
            }
        } catch (error) {
            console.error('Error fetching data:', error);
            errorMessage.textContent = 'An error occurred while fetching data. Please try again.';
            errorMessage.classList.remove('d-none');
        }
    });

    // Handle CSV Download
    downloadButton.addEventListener('click', () => {
        // If no data, do nothing
        if (!currentData.length) {
            alert('No data to download. Please fetch scores first.');
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

        // Convert each row to semicolon-delimited CSV (use ',' if you prefer)
        const rows = currentData.map(item => [
            item.CandidateID,
            item.candidate_name,
            item.candidate_email,
            item.candidate_role,
            item.total_points,
            item.Academic_year
        ].join(';'));

        // Add BOM (\uFEFF) for correct encoding in Excel, etc.
        const csvContent = "\uFEFF" + [headers.join(';'), ...rows].join("\n");
        const encodedUri = "data:text/csv;charset=utf-8," + encodeURI(csvContent);

        // Create a hidden link and auto-click to download
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
