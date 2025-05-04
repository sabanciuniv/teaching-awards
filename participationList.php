<?php
require_once 'api/authMiddleware.php';
require_once 'api/commonFunc.php';

init_session();
require_once __DIR__ . '/database/dbConnection.php';

$user = $_SESSION['user'];

// Fetch available academic years from DB
try {
    $academicYears = getAllAcademicYears($pdo);
} catch (PDOException $e) {
    die("Error fetching academic years: " . $e->getMessage());
}

// -------------------------
// BEGIN: Admin Access Check
// -------------------------
if (!checkIfUserIsAdmin($pdo, $user)) {
    header("Location: accessDenied.php");
    exit();
}
// -----------------------
// END: Admin Access Check
// -----------------------
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

    <!-- DataTables & Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <style>
        body {
            background-color: #f9f9f9;
            padding-top: 70px;
        }

        .title {
            text-align: center;
            margin: 40px 0 20px;
            font-size: 24px;
            font-weight: bold;
            color: black;
        }

        .form-section {
            margin-top: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .dropdown-select {
            background: #fff !important;
            color: #333 !important;
            border: 1px solid #ccc !important;
            border-radius: 6px !important;
            padding: 10px 20px !important;
            min-width: 200px;
        }

        .dropdown-menu {
            background-color: white !important;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .dropdown-item {
            color: #333;
            padding: 10px 20px;
            font-size: 14px;
            background-color: white !important;
            transition: background-color 0.2s ease;
        }

        .dropdown-item:hover {
            background-color: #f1f1f1 !important;
            color: #000;
        }

        .btn-custom, .return-button {
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

        .btn-custom:hover, .return-button:hover {
            background-color: #365a6b !important;
        }

        .table-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.05);
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
        }

        #participationTable {
            border-collapse: separate !important;
            border-spacing: 0 !important;
            background-color: white !important;
            border-radius: 8px;
            overflow: hidden;
        }

        #participationTable th,
        #participationTable td {
            font-size: 13px !important;
            font-weight: normal !important;
            padding: 10px 12px !important;
            border: none !important;
            border-bottom: 1px solid #eee !important;
            color: #333 !important;
            background-color: white !important;
            text-align: center;
        }

        /* Header look - subtle, not bold */
        #participationTable thead th {
            background-color: #f5f5f5 !important;
            color: #333 !important;
        }
        
        .datatable-header,
        .datatable-footer {
            padding: 0 10px;
            font-size: 13px;
            justify-content: center;
            gap: 10px;
        }

        .dataTables_wrapper {
            padding: 10px;
        }

        .dataTables_filter input {
            max-width: 250px;
        }
        /* Remove default DataTables styles (e.g., borders) */
        table.dataTable.no-footer {
            border-bottom: none !important;
        }

        .dataTables_info,
        .dataTables_paginate {
        font-size: 13px;
        color: #555;
        }
    </style>

</head>
<body>
<?php $backLink = "reportPage.php"; include 'navbar.php'; ?>

<div class="container">
    <div class="title">Voting Participation by Year</h2>

    <!-- Year Selection -->
    <div class="form-section">
        <div class="btn-group year-dropdown">
        <button id="yearSelectBtn" class="btn dropdown-toggle dropdown-select" data-bs-toggle="dropdown">
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
    <!-- Table (initially hidden) -->
    <div class="table-container" id="table-section" style="display: none;">
        <table id="participationTable" class="table datatable-excel-background table-bordered table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Students Voted</th>
                    <th>Total Students</th>
                    <th>Participation Percentage</th>
                </tr>
            </thead>
            <tbody id="participation-body">
                <!-- Rows will be inserted dynamically -->
            </tbody>
        </table>
    </div>

</div>

<!-- Action Container -->
<div class="action-container">
    <button class="return-button" onclick="window.location.href='reportPage.php'">
        <i class="fa fa-arrow-left"></i> Return to Reports Page
    </button>
</div>
<!-- JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables + Buttons -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    let gridInstance;
    let currentData = [];
    let selectedYear = null;

    const yearSelectBtn = document.getElementById("yearSelectBtn");
    const yearOptions = document.querySelectorAll(".year-option");
    const viewReportBtn = document.getElementById("viewReportBtn");
    const participationGrid = document.getElementById("participation-grid");
    const errorMessage = document.getElementById("error-message");

    // Handle Year Selection from Dropdown
    yearOptions.forEach(option => {
        option.addEventListener("click", function() {
            selectedYear = this.getAttribute("data-value");
            yearSelectBtn.textContent = this.textContent;

            if (gridInstance) gridInstance.destroy();
            errorMessage.style.display = "none";
            document.getElementById("table-section").style.display = "block";
        });
    });

    viewReportBtn.addEventListener("click", async () => {
        if (!selectedYear) {
            alert("Please select a year.");
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

            // Clear existing table body
            const tbody = document.getElementById("participation-body");
            tbody.innerHTML = "";

            data.forEach(row => {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>${row.CategoryName}</td>
                    <td>${row.Students_Voted}</td>
                    <td>${row.Total_Students}</td>
                    <td>${row.Participation_Percentage}%</td>
                `;
                tbody.appendChild(tr);
            });

            // Re-initialize DataTable
            if ($.fn.DataTable.isDataTable('#participationTable')) {
                $('#participationTable').DataTable().clear().destroy();
            }

            $('#participationTable').DataTable({
                autoWidth: false,
                scrollX: false,
                dom: '<"datatable-header d-flex justify-content-between align-items-center mb-2"fB>t<"datatable-footer"ip>',
                buttons: [
                    {
                    extend: 'excelHtml5',
                    text: 'Export to Excel',
                    className: 'btn-custom',
                    title: 'Voting Participation Report'
                    }
                ],
                pageLength: 8
            });




        } catch (error) {
            console.error("Fetch Error:", error);
        }
    });
});
</script>

</body>
</html>
