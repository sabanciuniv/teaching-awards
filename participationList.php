<?php
session_start();
require_once 'api/authMiddleware.php';
// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/database/dbConnection.php';

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
    <title>Voting Participation by Year</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Limitless Theme Styles -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet">
    <link href="assets/css/components.min.css" rel="stylesheet">
    <link href="assets/css/layout.min.css" rel="stylesheet">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Grid.js -->
    <link href="https://cdn.jsdelivr.net/npm/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />

    <style>
        body {
            overflow: auto;
            background-color: #f9f9f9;
        }

        /* Title Styling */
        .title {
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
            color: black;
        }

        .form-section {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .table-container {
            margin: 20px auto;
            max-width: 90%;
        }

        .error-message {
            display: none;
            text-align: center;
            color: #dc3545;
            font-size: 16px;
            font-weight: bold;
            margin-top: 15px;
        }

        .action-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }

        /* Match button colors */
        .return-button, 
        .btn-custom {
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

        .return-button:hover, 
        .btn-custom:hover {
            background-color: #365a6b !important;
        }

        /* Custom Download Button */
        .download-button {
            border: 2px solid #dc3545;
            color: #dc3545;
            background: transparent;
            padding: 15px 10px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            width: 200px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: 0.3s ease;
        }

        .download-button i {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .download-button:hover {
            background-color: #dc3545;
            color: white;
        }

        /* Custom Year Dropdown */
        .year-dropdown .btn {
            width: 200px;
            text-align: left;
        }

        .year-dropdown .dropdown-menu {
            width: 100%;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="title">Voting Participation by Year</h2>

    <!-- Year Selection -->
    <div class="form-section">
        <div class="btn-group year-dropdown">
            <button id="yearSelectBtn" class="btn btn-custom dropdown-toggle" data-bs-toggle="dropdown">
                Select Year
            </button>
            <div class="dropdown-menu">
                <?php foreach ($academicYears as $y): ?>
                    <a href="#" class="dropdown-item year-option" data-value="<?= $y['YearID'] ?>">
                        <?= htmlspecialchars($y['Academic_year']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <button id="viewReportBtn" class="btn btn-custom">
            <i class="fa fa-eye"></i> View Report
        </button>
    </div>

    <!-- Error Message -->
    <div id="error-message" class="error-message"></div>

    <!-- Table -->
    <div class="table-container">
        <div class="gridjs-example" id="participation-grid"></div>
    </div>
</div>

<!-- Action Container -->
<div class="action-container">
    <button class="return-button" onclick="window.location.href='reportPage.php'">
        <i class="fa fa-arrow-left"></i> Return to Category Page
    </button>

    <button id="downloadBtn" class="download-button d-none">
        <i class="fa fa-download"></i>
        Download
    </button>
</div>

<!-- Load JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gridjs/dist/gridjs.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    let gridInstance;
    let currentData = [];
    let selectedYear = null;

    const yearSelectBtn = document.getElementById("yearSelectBtn");
    const yearOptions = document.querySelectorAll(".year-option");
    const viewReportBtn = document.getElementById("viewReportBtn");
    const downloadBtn = document.getElementById("downloadBtn");
    const participationGrid = document.getElementById("participation-grid");
    const errorMessage = document.getElementById("error-message");

    // Handle Year Selection from Dropdown
    yearOptions.forEach(option => {
        option.addEventListener("click", function() {
            selectedYear = this.getAttribute("data-value");
            yearSelectBtn.textContent = this.textContent;

            if (gridInstance) gridInstance.destroy();
            errorMessage.style.display = "none";
            downloadBtn.classList.add("d-none");
        });
    });

    viewReportBtn.addEventListener("click", async () => {
        if (!selectedYear) {
            alert("⚠️ Please select a year.");
            return;
        }

        try {
            const apiUrl = `api/getVotingParticipation.php?yearID=${selectedYear}`;
            const response = await fetch(apiUrl);
            const data = await response.json();

            if (data.error) {
                errorMessage.textContent = data.error;
                errorMessage.style.display = "block";
                return;
            }

            errorMessage.style.display = "none";
            currentData = data;
            downloadBtn.classList.remove("d-none");

            if (gridInstance) gridInstance.destroy();

            new gridjs.Grid({
                columns: ["Category", "Students Voted", "Total Students", "Participation Percentage"],
                data: data.map(row => [
                    row.CategoryName, row.Students_Voted, row.Total_Students, `${row.Participation_Percentage}%`
                ]),
                pagination: { limit: 8, summary: true },
                search: true,
                sort: true
            }).render(participationGrid);
        } catch (error) {
            console.error("❌ Fetch Error:", error);
        }
    });

    downloadBtn.addEventListener("click", () => {
        const csvContent = ["Category,Students Voted,Total Students,Participation Percentage"]
            .concat(currentData.map(row => 
                `${row.CategoryName};${row.Students_Voted};${row.Total_Students};${row.Participation_Percentage}%`
            ))
            .join("\n");

        const blob = new Blob(["\uFEFF" + csvContent], { type: "text/csv;charset=utf-8;" });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(blob);
        link.download = "Voting_Participation_Report.csv";
        link.click();
    });
});
</script>

</body>
</html>
