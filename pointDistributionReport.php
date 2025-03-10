<?php
session_start();
require_once 'api/authMiddleware.php';

// Load DB config (adjust as needed)
$config = include('config.php');
$dbConfig = $config['database'];
$rows = [];
$academicYears = [];
$selectedYear = null;

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get list of academic years
    $stmtAY = $pdo->query("SELECT YearID, Academic_year, Start_date_time, End_date_time FROM AcademicYear_Table ORDER BY Academic_year DESC");
    $academicYears = $stmtAY->fetchAll(PDO::FETCH_ASSOC);

    // Check if an academic year has been selected via GET parameter
    if (isset($_GET['academicYear']) && is_numeric($_GET['academicYear'])) {
        $selectedYear = intval($_GET['academicYear']);

        // Query instructors and count the votes (only for the selected academic year)
        $sql = "
            SELECT 
                c.id AS candidate_id,
                c.Name AS instructor_name,
                SUM(CASE WHEN v.Points = 6 THEN 1 ELSE 0 END) AS count_6,
                SUM(CASE WHEN v.Points = 5 THEN 1 ELSE 0 END) AS count_5,
                SUM(CASE WHEN v.Points = 4 THEN 1 ELSE 0 END) AS count_4,
                SUM(CASE WHEN v.Points = 3 THEN 1 ELSE 0 END) AS count_3,
                SUM(CASE WHEN v.Points = 2 THEN 1 ELSE 0 END) AS count_2,
                SUM(CASE WHEN v.Points = 1 THEN 1 ELSE 0 END) AS count_1
            FROM Candidate_Table c
            LEFT JOIN Votes_Table v ON c.id = v.CandidateID AND v.AcademicYear = :selectedYear
            WHERE c.Role = 'Instructor'
            GROUP BY c.id, c.Name
            ORDER BY c.Name
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':selectedYear' => $selectedYear]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <title>Point Distribution Report</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Limitless Theme Styles (adjust paths if needed) -->
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Grid.js CSS -->
    <link href="https://cdn.jsdelivr.net/npm/gridjs/dist/theme/mermaid.min.css" rel="stylesheet" />

    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow-y: auto;
            background-color: #f9f9f9;
        }
        .title {
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
        }
        .filter-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .filter-container select {
            width: 300px;
            padding: 8px;
            font-size: 16px;
        }
        .action-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
        }
        .return-button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            font-size: 14px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .return-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <?php
        // If you have a navbar, include it here. Do not change navbar.php.
        $backLink = "reportPage.php";
        // include 'navbar.php'; // Uncomment if you have a navbar file
    ?>

    <div class="title">Point Distribution Report</div>

    <!-- Academic Year Selection Form -->
    <div class="filter-container">
        <form method="GET" action="">
            <label for="academicYear">Select Academic Year:</label>
            <select name="academicYear" id="academicYear" onchange="this.form.submit()">
                <option value="" <?php echo ($selectedYear === null) ? 'selected' : ''; ?>>-- Choose Academic Year --</option>
                <?php foreach ($academicYears as $ay): 
                    // Option label: for example "2023-2024"
                    $displayYear = $ay['Academic_year'] . '-' . ($ay['Academic_year'] + 1);
                ?>
                    <option value="<?php echo $ay['YearID']; ?>" <?php echo ($selectedYear == $ay['YearID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($displayYear); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if ($selectedYear === null): ?>
        <div class="text-center">Please select an academic year to view the point distribution report.</div>
    <?php else: ?>
        <!-- Table Container -->
        <div class="gridjs-example-basic" style="margin: 20px;"></div>
    <?php endif; ?>

    <!-- "Return" Button -->
    <div class="action-container">
        <button class="return-button" onclick="window.location.href='reportPage.php'">
            Return to Category Page
        </button>
    </div>

    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Grid.js JS -->
    <script src="https://cdn.jsdelivr.net/npm/gridjs/dist/gridjs.umd.js"></script>
    <!-- FileSaver (for CSV download) -->
    <script src="https://cdn.jsdelivr.net/npm/file-saver/dist/FileSaver.min.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        <?php if ($selectedYear !== null): ?>
            // Our PHP array of instructors with point counts for the selected academic year
            const instructorsData = <?php echo json_encode($rows, JSON_UNESCAPED_UNICODE); ?>;
            console.log("Instructors Data:", instructorsData);

            // Transform into an array-of-arrays for Grid.js
            // Columns: Instructor Name, Points (6), Points (5), Points (4), Points (3), Points (2), Points (1)
            const transformedData = instructorsData.map(row => [
                row.instructor_name,
                row.count_6 || 0,
                row.count_5 || 0,
                row.count_4 || 0,
                row.count_3 || 0,
                row.count_2 || 0,
                row.count_1 || 0
            ]);

            // Render Grid.js table
            const gridContainer = document.querySelector(".gridjs-example-basic");
            if (gridContainer) {
                const grid = new gridjs.Grid({
                    className: {
                        table: 'table'
                    },
                    columns: [
                        "Instructor Name",
                        "Points (6)",
                        "Points (5)",
                        "Points (4)",
                        "Points (3)",
                        "Points (2)",
                        "Points (1)"
                    ],
                    data: transformedData,
                    pagination: true,
                    sort: true,
                    search: true,
                    resizable: true,
                    style: {
                        table: {
                            borderCollapse: 'collapse',
                            margin: '0 auto'
                        }
                    },
                    downloadCSV: true,
                    downloadButton: {
                        text: 'Download Data'
                    }
                });
                grid.render(gridContainer);

                // Create a "Download CSV" button (if you prefer to have it separately)
                const downloadBtn = document.createElement("button");
                downloadBtn.textContent = "Download CSV";
                downloadBtn.style.margin = "20px auto";
                downloadBtn.style.display = "block";

                downloadBtn.addEventListener("click", () => {
                    const headers = [
                        "Instructor Name",
                        "Points (6)",
                        "Points (5)",
                        "Points (4)",
                        "Points (3)",
                        "Points (2)",
                        "Points (1)"
                    ];

                    const rows = transformedData.map(row => row.join(";"));
                    const csvContent = "\uFEFF" + [headers.join(";"), ...rows].join("\n");

                    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
                    saveAs(blob, "point_distribution_report.csv");
                });

                // Insert the CSV download button above the grid
                document.body.insertBefore(downloadBtn, gridContainer);
            }
        <?php endif; ?>
    });
    </script>
</body>
</html>
