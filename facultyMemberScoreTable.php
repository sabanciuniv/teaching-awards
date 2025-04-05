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

$user = $_SESSION['user'];

// Process the form submission if year and category are provided
/*if (isset($_GET['year']) && isset($_GET['category'])) {
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
}*/

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

// -------------------------
// BEGIN: Admin Access Check
// -------------------------
try {
    // Check if the username exists in Admin_Table and is not marked as 'Removed'
    $adminQuery = "SELECT 1 
                     FROM Admin_Table 
                    WHERE AdminSuUsername = :username 
                      AND checkRole <> 'Removed'
                    LIMIT 1";
    $adminStmt = $pdo->prepare($adminQuery);
    $adminStmt->execute([':username' => $user]);
    
    // If no active record is found, redirect to index.php
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
    <title>All Faculty Scores by Category &amp; Year</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

     <!-- Theme Styles -->
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
            overflow: auto;
            background-color: #f9f9f9;
        }

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

        .form-select.custom-select {
            width: 200px;
            padding: 10px;
            border-radius: 6px;
        }

        .table-container {
            margin: 20px auto;
            max-width: 95%;
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

<div class="container">
    <div class="title">All Faculty Scores by Category &amp; Year</div>

    <!-- Filter Form -->
    <form method="GET" class="form-section">
        <input type="hidden" id="selectedYearInput" name="year">
        <input type="hidden" id="selectedCategoryInput" name="category">
        <div class="btn-group year-dropdown">
            <button id="yearSelectBtn" class="btn btn-custom dropdown-toggle" data-bs-toggle="dropdown">
            <?= isset($_GET['year']) ? htmlspecialchars($academicYears[array_search($_GET['year'], array_column($academicYears, 'YearID'))]['Academic_year']) : 'Select Year' ?>
            </button>
            <!-- Year Dropdown (label: e.g. 2024, value: e.g. 1) -->
                <div class="dropdown-menu">
                    <?php foreach ($academicYears as $y): ?>
                        <a href="#" class="dropdown-item year-option" data-value="<?= $y['YearID'] ?>">
                            <?= htmlspecialchars($y['Academic_year']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="btn-group year-dropdown">
                <button id="categorySelectBtn" class="btn btn-custom dropdown-toggle" data-bs-toggle="dropdown">
                <?= isset($_GET['category']) ? htmlspecialchars($categories[array_search($_GET['category'], array_column($categories, 'CategoryID'))]['CategoryCode']) : 'Select Category' ?>
                </button>
                <div class="dropdown-menu">
                    <?php foreach ($categories as $c): ?>
                        <a href="#" class="dropdown-item category-option" data-value="<?= $c['CategoryID'] ?>">
                            <?= htmlspecialchars($c['CategoryCode']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <button type="submit" class="btn btn-custom"><i class="fa fa-eye"></i> View Scores</button>
        </form>
    </div>

    <?php
    if (isset($_GET['year']) && isset($_GET['category'])):
        $yearId = intval($_GET['year']);
        $categoryId = intval($_GET['category']);

        try {
            $stmtYear = $pdo->prepare("SELECT Academic_year FROM AcademicYear_Table WHERE YearID = ?");
            $stmtYear->execute([$yearId]);
            $yearRow = $stmtYear->fetch(PDO::FETCH_ASSOC);
            $academicYearName = $yearRow['Academic_year'] ?? '';

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
                WHERE v.CategoryID = ? AND v.AcademicYear = ?
                GROUP BY v.CandidateID, c.Name, c.Mail, c.Role
                ORDER BY total_points DESC
            ");
            $stmt->execute([$categoryId, $yearId]);
            $facultyScores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $errorMessage = 'Database error: ' . $e->getMessage();
        }
    ?>

    <?php if (!empty($facultyScores)): ?>
    <div class="table-container">
        <table id="facultyScoresTable" class="table table-bordered table-striped" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Total Points</th>
                    <th>6-Point</th><th>5-Point</th><th>4-Point</th>
                    <th>3-Point</th><th>2-Point</th><th>1-Point</th><th>Academic Year</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($facultyScores as $row): ?>
                <tr>
                    <td><?= $row['CandidateID'] ?></td>
                    <td><?= htmlspecialchars($row['candidate_name']) ?></td>
                    <td><?= htmlspecialchars($row['candidate_email']) ?></td>
                    <td><?= htmlspecialchars($row['candidate_role']) ?></td>
                    <td><?= $row['total_points'] ?></td>
                    <td><?= $row['points_6_count'] ?></td>
                    <td><?= $row['points_5_count'] ?></td>
                    <td><?= $row['points_4_count'] ?></td>
                    <td><?= $row['points_3_count'] ?></td>
                    <td><?= $row['points_2_count'] ?></td>
                    <td><?= $row['points_1_count'] ?></td>
                    <td><?= htmlspecialchars($academicYearName) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php elseif (isset($_GET['year'])): ?>
        <div class="error-message" style="display:block;">No votes found for the selected category and year.</div>
    <?php endif; ?>
    <?php endif; ?>


<div class="action-container">
    <button class="return-button" onclick="window.location.href='reportPage.php'">
        <i class="fa fa-arrow-left"></i> Return to Reports Page
    </button>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const yearOptions = document.querySelectorAll(".year-option");
    const categoryOptions = document.querySelectorAll(".category-option");
    const yearSelectBtn = document.getElementById("yearSelectBtn");
    const categorySelectBtn = document.getElementById("categorySelectBtn");
    const yearInput = document.getElementById("selectedYearInput");
    const categoryInput = document.getElementById("selectedCategoryInput");

    yearOptions.forEach(option => {
        option.addEventListener("click", function () {
            yearSelectBtn.textContent = this.textContent;
            yearInput.value = this.getAttribute("data-value");
        });
    });

    categoryOptions.forEach(option => {
        option.addEventListener("click", function () {
            categorySelectBtn.textContent = this.textContent;
            categoryInput.value = this.getAttribute("data-value");
        });
    });
    if ($('#facultyScoresTable').length) {
        $('#facultyScoresTable').DataTable({
            dom: '<"datatable-header d-flex justify-content-between align-items-center mb-2"fB>t<"datatable-footer"ip>',
            buttons: [
                {
                    extend: 'excelHtml5',
                    title: 'Faculty Scores',
                    text: 'Export to Excel',
                    className: 'btn btn-custom'
                }
            ],
            pageLength: 10
        });
    }
});
</script>

</body>
</html>