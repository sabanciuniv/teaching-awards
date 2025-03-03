<?php
require_once 'database/dbConnection.php';

// Detect where the user came from
$from = $_GET['from'] ?? null;
$yearID = $_GET['yearID'] ?? null;
$categoryID = $_GET['category'] ?? null;

// Fetch available academic years
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
    <title>Voting Participation by Category</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap & Grid.js -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />

    <style>
        body { overflow: auto; }
        .title { text-align: center; margin: 20px 0; font-size: 24px; font-weight: bold; }
        .action-container { position: fixed; bottom: 20px; right: 20px; display: flex; flex-direction: column; align-items: flex-start; }
        .action-button { background-color: #007bff; color: white; border: none; padding: 10px 15px; font-size: 14px; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease; margin-bottom: 10px; width: 200px; text-align: center; }
        .action-button:hover { background-color: #0056b3; }
        .table-container { margin: 20px; }
    </style>
</head>
<body>

<div class="container">
    <div class="title">Voting Participation by Category</div>

    <!-- Year Selection -->
    <div class="mb-4 d-flex justify-content-center">
        <form id="year-form" class="d-flex">
            <select id="yearSelect" class="form-select me-3" required>
                <option value="" disabled selected>Select Year</option>
                <?php foreach ($academicYears as $y): ?>
                    <option value="<?= $y['YearID'] ?>"><?= htmlspecialchars($y['Academic_year']) ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Category Dropdown (Only visible if coming from Faculty Scores) -->
            <div id="categoryDropdown" style="display: none;">
                <label for="categorySelect">Select Category:</label>
                <select id="categorySelect" class="form-select">
                    <option value="" disabled selected>Select Category</option>
                    <option value="1">A1</option>
                    <option value="2">A2</option>
                    <option value="3">B</option>
                    <option value="4">C</option>
                    <option value="5">D</option>
                </select>
            </div>


            <button type="submit" class="btn btn-primary">View Report</button>
        </form>
    </div>


    <!-- Table to display category-wise participation -->
    <div class="table-container">
        <div class="gridjs-example" id="participation-grid"></div>
    </div>

    <!-- Error Message Display -->
    <div id="error-message" class="alert alert-danger d-none"></div>
</div>

<!-- Return Button -->
<div class="action-container">
    <button class="action-button" onclick="window.location.href='reportPage.php'">Return To Category Page</button>
</div>

<!-- Load JS Libraries -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gridjs/dist/gridjs.umd.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const urlParams = new URLSearchParams(window.location.search);
        const fromPage = urlParams.get('from');  // Get source page

        // If the request is from faculty scores, show category selection
        if (fromPage === 'facultyScores') {
            document.getElementById('categoryDropdown').style.display = 'block'; // Show category selector
        }

        document.getElementById('year-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const yearID = document.getElementById('yearSelect').value;
            const categoryID = document.getElementById('categorySelect')?.value || null; // Category is optional

            if (!yearID) {
                alert("⚠️ Please select a year.");
                return;
            }

            if (fromPage === 'facultyScores' && !categoryID) {
                alert("⚠️ Please select a category.");
                return;
            }

            let targetUrl = `facultyMemberScoreTable.php?yearID=${yearID}`;
            if (fromPage === 'facultyScores') {
                targetUrl += `&category=${categoryID}`;
            }

            console.log("🚀 Redirecting to:", targetUrl);
            window.location.href = targetUrl;
        });
    });
</script>

