<?php
// Include configuration
require_once 'api/authMiddleware.php';
$config = include('config.php');

// Fetch data from the database
$dbConfig = $config['database'];
$data = [];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL query
    $stmt = $pdo->query("
        SELECT 
            StudentID, 
            AcademicYear, 
            StudentNameSurname, 
            SuNET_Username, 
            Class, 
            Mail, 
            Department, 
            A1_Vote, 
            A2_Vote, 
            B_Vote, 
            C_Vote, 
            D_Vote
        FROM Student_Table
    ");
    $data = $stmt->fetchAll(PDO::FETCH_NUM);

    // Debug: Print data to confirm fetching works
    //echo '<pre>';
    //print_r($data);
    //echo '</pre>';
    //die();

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Vote Usage Data Table</title>
    <!-- Include Grid.js CSS -->
    	<!-- Global stylesheets -->
	<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
	<link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
	<!-- /global stylesheets -->


	<!-- Core JS files -->
	<script src="assets/js/jquery.min.js"></script>
	<script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/global_assets/js/main/jquery.min.js"></script>
	<script src="assets/global_assets/js/main/bootstrap.bundle.min.js"></script>
	<!-- /core JS files -->

	<!-- Theme JS files -->
	<script src="assets/js/app.js"></script>
	<script src="assets/js/custom.js"></script>
	<!-- /theme JS files -->
    
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

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            padding: 20px;
        }

        .form-title {
            text-align: center;
            margin-top: 40px; 
            margin-bottom: 30px; 
            font-size: 1.8rem;
            font-weight: bold;
            color: #3f51b5; 
        }

        .form-group label {
            font-weight: bold;
            color: #333;
        }

        .form-control {
            border-radius: 6px;
            background-color: #f7f7f9;
            border: 1px solid #ddd;
            padding: 10px;
        }

        .form-control:focus {
            border-color: #3f51b5; 
            box-shadow: 0 0 3px rgba(63, 81, 181, 0.5);
        }

        .btn-indigo {
            background-color: #3f51b5;
            color: white;
            font-weight: bold;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-indigo:hover {
            background-color: #303f9f; 
        }

        .icon-paperplane {
            margin-left: 8px;
        }
        
        .action-container {
            position: fixed; /* Stick to the bottom */
            bottom: 20px;    /* Distance from the bottom of the page */
            right: 20px;     /* Full width to center the button */
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
    <div class="title">Student Vote Usage Status</div>
    

    <div class="action-container">
        <button 
            class="return-button" 
            onclick="window.location.href='reportPage.php'">
            Return to Category Page
        </button>
    </div>
    <div class="gridjs-example-basic" style="margin: 20px;"></div>

    <!-- Include Grid.js JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/gridjs/dist/gridjs.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/file-saver/dist/FileSaver.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            // Fetch PHP-encoded data
            const StudentData = <?php echo json_encode($data); ?>;

            // Debug: Log data to console
            console.log("Student Data: ", StudentData);

            // Transform vote columns to "Voted" or "Not Voted"
            const transformedData = StudentData.map(row =>
                row.map((cell, index) => {
                    // Vote columns are at indices 7 to 11
                    if (index >= 7 && index <= 11) {
                        return cell === "yes" ? "Voted" : cell === "no" ? "Not Voted" : "-";
                    }
                    return cell;
                })
            );

            // Render Grid.js table
            const gridjsBasicElement = document.querySelector(".gridjs-example-basic");
            if (gridjsBasicElement) {
                const gridjsBasic = new gridjs.Grid({
                    className: {
                        table: 'table'
                    },
                    columns: [
                        "Student ID", 
                        "Academic Year", 
                        "Name", 
                        "SUNET Username", 
                        "Class", 
                        "Email", 
                        "Department", 
                        "A1 Vote", 
                        "A2 Vote", 
                        "B Vote", 
                        "C Vote", 
                        "D Vote"
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
                gridjsBasic.render(gridjsBasicElement);
            }

            // Export to Excel functionality
            const exportToExcel = () => {
                const headers = [
                    "Student ID", 
                    "Academic Year", 
                    "Name", 
                    "SUNET Username", 
                    "Class", 
                    "Email", 
                    "Department", 
                    "A1 Vote", 
                    "A2 Vote", 
                    "B Vote", 
                    "C Vote", 
                    "D Vote"
                ];

                const rows = transformedData.map(row => row.join(","));
                const csvContent = [headers.join(","), ...rows].join("\n");

                const encodedUri = "data:text/csv;charset=utf-8," + encodeURI(csvContent);
                const link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "student_data.csv");
                document.body.appendChild(link); // Required for Firefox
                link.click();
                document.body.removeChild(link); // Clean up
            };

            // Create the download button
            const downloadButton = document.createElement("button");
            downloadButton.textContent = "Download CSV";
            downloadButton.style.margin = "20px auto";
            downloadButton.style.display = "block";
            downloadButton.addEventListener("click", exportToExcel);
            document.body.insertBefore(downloadButton, gridjsBasicElement);
        });

    </script>

</body>
</html>
