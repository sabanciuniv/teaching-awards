<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/database/dbConnection.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_year_id'])) {
    $deleteYear = intval($_POST['delete_year_id']);

    try {
        $stmt = $pdo->prepare("DELETE FROM AcademicYear_Table WHERE Academic_year = :year");
        $stmt->execute([':year' => $deleteYear]);

        echo "<script>alert('Academic Year Deleted Successfully!'); window.location.href='manageAcademicYear.php';</script>";
        exit();
    } catch (PDOException $e) {
        die("<strong style='color:red;'>SQL Error:</strong> " . $e->getMessage());
    }
}

// Handle ADD request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['academic_year'])) {
    $academicYear = intval($_POST['academic_year']);
    $startDate = date('Y-m-d H:i:s', strtotime($_POST['start_date']));
    $endDate = date('Y-m-d H:i:s', strtotime($_POST['end_date']));

    try {
        $stmt = $pdo->prepare("INSERT INTO AcademicYear_Table (Academic_year, Start_date_time, End_date_time) VALUES (:year, :start, :end)");
        $stmt->execute([
            ':year' => $academicYear,
            ':start' => $startDate,
            ':end' => $endDate
        ]);

        echo "<script>alert('New Academic Year Added Successfully!'); window.location.href='adminDashboard.php';</script>"; 
        exit();
    } catch (PDOException $e) {
        die("SQL Error: " . $e->getMessage());
    }
}

// Fetch academic years
$academicYears = [];
try {
    $stmt = $pdo->query("SELECT * FROM AcademicYear_Table ORDER BY Academic_year DESC");
    $academicYears = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<strong style='color:red;'>SQL Error:</strong> " . $e->getMessage());
}

// Determine the current academic year (latest one)
$currentAcademicYear = !empty($academicYears) ? $academicYears[0] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Academic Year</title>

    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <!--FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom Styles -->
    <style>
        body {
            background-color: #f9f9f9;
            margin: 0;
            padding-top: 70px;
            overflow-y: auto;
            display: flex;
        }
    </style>
</head>
<body>
        
    <?php include 'navbar.php'; ?>
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header text-white" style="background-color: #45748a;">
                <h4 class="mb-0"><i class="fa-solid fa-calendar-days"></i> Manage Academic Year</h4>
            </div>
            <div class="card-body">

                <!-- Current Academic Year -->
                <?php if ($currentAcademicYear): ?>
                    <div class="alert alert-success" style="margin-top: 15px;">
                        <h5>Current Academic Year: <strong><?= $currentAcademicYear['Academic_year']; ?></strong></h5>
                        <p>Start Date: <strong><?= date("d-m-Y H:i", strtotime($currentAcademicYear['Start_date_time'])); ?></strong></p>
                        <p>End Date: <strong><?= date("d-m-Y H:i", strtotime($currentAcademicYear['End_date_time'])); ?></strong></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">No Academic Year Found</div>
                <?php endif; ?>

                <hr>

                <!-- Add New Academic Year Form -->
                <h5 class="mb-3">Add New Academic Year</h5>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="academic_year" class="form-label">Academic Year</label>
                        <input type="number" name="academic_year" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date & Time</label>
                        <input type="datetime-local" name="start_date" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date & Time</label>
                        <input type="datetime-local" name="end_date" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-success" style="background-color: #45748a;border-color: #45748a; color: white;">
                        <i class="fa-solid fa-plus"></i> Add Academic Year
                    </button>
                </form>

                <hr>

                <!-- List of Academic Years -->
                <h5 class="mb-3">Previous Academic Years</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead class="custom-thead">
                            <tr>
                                <th>Academic Year</th>
                                <th>Start Date & Time</th>
                                <th>End Date & Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($academicYears as $year): ?>
                                <tr>
                                    <td><?= $year['Academic_year']; ?></td>
                                    <td><?= date("d-m-Y H:i", strtotime($year['Start_date_time'])); ?></td>
                                    <td><?= date("d-m-Y H:i", strtotime($year['End_date_time'])); ?></td>
                                    <td>
                                        <form method="POST" action="">
                                            <input type="hidden" name="delete_year_id" value="<?= $year['Academic_year']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this academic year?');">
                                                <i class="fa-solid fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    </body>
    </html>


    <!-- JS: jQuery (Required for Bootstrap & Date Picker) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap & Required JS Libraries -->
    <script src="assets/js/main/bootstrap.bundle.min.js"></script>

    <!-- Moment.js (For Date Formatting) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>

    <!-- Date Picker JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

    <!-- Initialize Date Picker -->
    <script>
        $(document).ready(function () {
            $(".datepicker").datepicker({
                format: "dd-mm-yyyy",
                weekStart: 1, //  Monday as the first day of the week is set
                autoclose: true,
                todayHighlight: true
            });
        });
    </script>

</body>
</html>