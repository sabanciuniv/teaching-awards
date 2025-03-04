<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/database/dbConnection.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Handle UPDATE request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_academic_year'])) {
    $academicYearId = intval($_POST['academic_year_id']);
    $academicYear = $_POST['academic_year'];
    $startDate = date('Y-m-d H:i:s', strtotime($_POST['start_date']));
    $endDate = date('Y-m-d H:i:s', strtotime($_POST['end_date']));

    // Validate input
    if (empty($academicYear) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
        die("Error: All fields are required.");
    }

    if (strtotime($_POST['start_date']) >= strtotime($_POST['end_date'])) {
        die("Error: Start date must be before the end date.");
    }

    try {
        $stmt = $pdo->prepare("UPDATE AcademicYear_Table 
                               SET Academic_year = :year, 
                                   Start_date_time = :start, 
                                   End_date_time = :end 
                               WHERE YearID = :id");
        $stmt->execute([
            ':year' => $academicYear,
            ':start' => $startDate,
            ':end' => $endDate,
            ':id' => $academicYearId
        ]);
        echo "<script>alert('Academic Year Updated Successfully!'); window.location.href='manageAcademicYear.php';</script>";
        exit();
    } catch (PDOException $e) {
        die("SQL Error: " . $e->getMessage());
    }
}

// Handle ADD request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['academic_year'])) {
    $academicYear = intval($_POST['academic_year']);
    $startDate = date('Y-m-d H:i:s', strtotime($_POST['start_date']));
    $endDate = date('Y-m-d H:i:s', strtotime($_POST['end_date']));

    if (empty($academicYear) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
        die("Error: All fields are required.");
    }
    
    if (strtotime($_POST['start_date']) >= strtotime($_POST['end_date'])) {
        die("Error: Start date must be before the end date.");
    }
    
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

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <!-- Bootstrap JS Bundle (includes Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

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
        
    <?php $backLink = "adminDashboard.php"; include 'navbar.php'; ?>
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header text-white" style="background-color: #45748a;">
                <h4 class="mb-0"><i class="fa-solid fa-calendar-days"></i> Manage Academic Year</h4>
            </div>
            <div class="card-body">

                <!-- Current Academic Year -->
                <?php if ($currentAcademicYear): ?>
                    <?php 
                    // +1 logic for current academic year
                    $displayCurrentYear = $currentAcademicYear['Academic_year'] . '-' . ($currentAcademicYear['Academic_year'] + 1);
                    ?>
                    <div class="alert alert-success" style="margin-top: 15px;">
                        <h5>Current Academic Year: <strong><?= $displayCurrentYear; ?></strong></h5>
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
                    <!-- Select Academic Year -->
                    <div class="mb-3">
                        <label for="academic_year" class="form-label">Academic Year</label>
                        <select class="form-select" id="academic-year-select" name="academic_year" required>
                            <option value="" disabled selected>Select Academic Year</option>
                            <option value="2023-2024">2023-2024</option>
                            <option value="2024-2025">2024-2025</option>
                            <option value="2025-2026">2025-2026</option>
                            <option value="2026-2027">2026-2027</option>
                        </select>
                    </div>

                    <!-- Start Date & Time -->
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Start Date & Time</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ph-calendar"></i></span>
                            <input type="text" id="start-date-picker" name="start_date" class="form-control" placeholder="Select start date & time" required>
                        </div>
                    </div>

                    <!-- End Date & Time -->
                    <div class="mb-3">
                        <label for="end_date" class="form-label">End Date & Time</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ph-calendar"></i></span>
                            <input type="text" id="end-date-picker" name="end_date" class="form-control" placeholder="Select end date & time" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success" style="background-color: #45748a; border-color: #45748a; color: white;">
                        <i class="fa-solid fa-plus"></i> Add Academic Year
                    </button>
                </form>


                <hr>

                <!-- List of Academic Years -->
                <h5 class="mb-3">Academic Years</h5>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Academic Year</th>
                                <th>Start Date & Time</th>
                                <th>End Date & Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($academicYears as $year): ?>
                                <?php 
                                // +1 logic for each row
                                $displayYear = $year['Academic_year'] . '-' . ($year['Academic_year'] + 1);
                                ?>
                                <tr>
                                    <td><?= $displayYear; ?></td>
                                    <td><?= date("d-m-Y H:i", strtotime($year['Start_date_time'])); ?></td>
                                    <td><?= date("d-m-Y H:i", strtotime($year['End_date_time'])); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm edit-btn" 
                                            data-id="<?= htmlspecialchars($year['YearID']); ?>"  
                                            data-year="<?= htmlspecialchars($year['Academic_year']); ?>"
                                            data-start="<?= date('Y-m-d\TH:i', strtotime($year['Start_date_time'])); ?>"  
                                            data-end="<?= date('Y-m-d\TH:i', strtotime($year['End_date_time'])); ?>">  
                                            <i class="fa-solid fa-pen"></i> Edit
                                        </button>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Academic Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="academic_year_id" id="academic_year_id">

                        <!-- Academic Year -->
                        <div class="mb-3">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="academic_year" name="academic_year" required>
                        </div>

                        <!-- Start Date -->
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                        </div>

                        <!-- End Date -->
                        <div class="mb-3">
                            <label for="end_date" class="form-label">End Date & Time</label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_academic_year" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


    <!-- Include jQuery and Date Picker Scripts -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <!-- Initialize Date Picker -->
    <script>
        $(document).ready(function () {
            // Initialize date pickers with month selection enabled
            function initializeDatePicker(id) {
                $(id).daterangepicker({
                    singleDatePicker: true,
                    timePicker: true,
                    timePickerIncrement: 15,
                    timePicker24Hour: true,
                    showDropdowns: true,   // Enables dropdown for year selection
                    autoApply: true,       // Auto-applies the selected date
                    locale: {
                        format: 'YYYY-MM-DD HH:mm'
                    }
                }).on('show.daterangepicker', function (ev, picker) {
                    // Enables month selection when clicking on the month name
                    $('.daterangepicker select.monthselect').show();
                    $('.daterangepicker select.yearselect').show();
                });
            }

            initializeDatePicker('#start-date-picker');
            initializeDatePicker('#end-date-picker');
        });
    </script>


    <script>
        $(document).ready(function () {
            $(".edit-btn").click(function () {
                let id = $(this).data("id");
                let year = $(this).data("year");
                let start = $(this).data("start");
                let end = $(this).data("end");

                $("#academic_year_id").val(id);
                $("#academic_year").val(year);
                $("#start_date").val(start); 
                $("#end_date").val(end);      

                $("#editModal").modal("show");
            });
        });
    </script>

</body>
</html>
