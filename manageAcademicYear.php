<?php
require_once 'api/errorInit.php';

require_once __DIR__ . '/database/dbConnection.php';

require_once 'api/commonFunc.php';
$pageTitle= "Manage Academic Year";
require_once 'api/header.php';
init_session();
enforceAdminAccess($pdo); // Yetki kontrolü öncelikli olarak yapılmalı 

$user = $_SESSION['user'];

// Handle UPDATE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_academic_year'])) {
    $academicYearId = intval($_POST['academic_year_id']);

    $rawAcademicYear = $_POST['academic_year'] ?? '';

    // Ensure the year is exactly 4 digits and starts with "20"
    if (!preg_match('/^20\d{2}$/', $rawAcademicYear)) {
        echo "<script>
            alert('Error: Academic year must be a 4-digit number starting with 20 (e.g., 2023, 2025).');
            window.location.href='manageAcademicYear.php';
        </script>";
        exit();
    }

    $academicYear = intval($rawAcademicYear); 


    $academicYearInt = (int) $rawAcademicYear;
    $currentYearInt  = (int) date('Y');

    // 3) Disallow if < current calendar year
    //if ($academicYearInt < $currentYearInt) {
      //  echo "<script>
        //    alert('Error: Academic year cannot be lower than the current calendar year.');
          //  window.location.href='manageAcademicYear.php';
        //</script>";
        //exit();
  //  }

    $startDate = date('Y-m-d H:i:s', strtotime($_POST['start_date']));
    $endDate   = date('Y-m-d H:i:s', strtotime($_POST['end_date']));

    // Validate input
    if (empty($academicYear) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
        die("Error: All fields are required.");
    }
    if (strtotime($_POST['start_date']) >= strtotime($_POST['end_date'])) {
        die("Error: Start date must be before the end date.");
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE AcademicYear_Table
               SET Academic_year  = :year,
                   Start_date_time = :start,
                   End_date_time   = :end
             WHERE YearID = :id
        ");
        $stmt->execute([
            ':year'  => $academicYear,
            ':start' => $startDate,
            ':end'   => $endDate,
            ':id'    => $academicYearId
        ]);
        echo "<script>alert('Academic Year Updated Successfully!'); window.location.href='manageAcademicYear.php';</script>";
        exit();
    } catch (PDOException $e) {
        die("SQL Error: " . $e->getMessage());
    }
}

// Handle ADD request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['academic_year'])) {
    // === NEW LINES (checker) ===
    $rawAcademicYear = $_POST['academic_year'];
    if (!preg_match('/^20\d{2}$/', $rawAcademicYear)) {
        echo "<script>alert('Error: Academic year must be exactly 4 digits starting with 20 (e.g. 2023).');
              window.location.href='manageAcademicYear.php';</script>";
        exit();
    }
    
    // ===========================

    $academicYear = intval($rawAcademicYear);

    $academicYearInt = (int) $rawAcademicYear;
    $currentYearInt  = (int) date('Y');

    // 3) Disallow if < current calendar year
    if ($academicYearInt < $currentYearInt) {
        echo "<script>
            alert('Error: Academic year cannot be lower than the current calendar year.');
            window.location.href='manageAcademicYear.php';
        </script>";
        exit();
    }

    // 2) Convert from "DD-MM-YYYY HH:mm" to "YYYY-MM-DD HH:mm:ss"
    $startDate = date('Y-m-d H:i:s', strtotime($_POST['start_date']));
    $endDate   = date('Y-m-d H:i:s', strtotime($_POST['end_date']));

    if (empty($academicYear) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
        die("Error: All fields are required.");
    }
    if (strtotime($_POST['start_date']) >= strtotime($_POST['end_date'])) {
        die("Error: Start date must be before the end date.");
    }

    // Check if this academic year already exists
    try {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM AcademicYear_Table WHERE Academic_year = :year");
        $checkStmt->execute([':year' => $academicYear]);
        $exists = $checkStmt->fetchColumn();
        if ($exists > 0) {
            die("Error: This academic year ($academicYear) already exists!");
        }

        $stmt = $pdo->prepare("
            INSERT INTO AcademicYear_Table (Academic_year, Start_date_time, End_date_time)
            VALUES (:year, :start, :end)
        ");
        $stmt->execute([
            ':year'  => $academicYear,
            ':start' => $startDate,
            ':end'   => $endDate
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

// -------------------------
// BEGIN: Admin Access Check
// -------------------------
try {
    // This query ensures the user exists in Admin_Table, is not marked as 'Removed',
    // and that their Role is exactly 'IT_Admin'
    $adminQuery = "SELECT 1 
                     FROM Admin_Table 
                    WHERE AdminSuUsername = :username 
                      AND checkRole <> 'Removed'
                      AND Role IN ('IT_Admin', 'Admin')
                    LIMIT 1";
    $adminStmt = $pdo->prepare($adminQuery);
    $adminStmt->execute([':username' => $user]);
    
    // If no record is found, redirect to index.php
    if (!$adminStmt->fetch()) {
        header("Location: accessDenied.php");
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

                    // The DB column is stored as "YYYY-MM-DD HH:mm:ss", so we convert it for display
                    $displayStart = date("d-m-Y H:i", strtotime($currentAcademicYear['Start_date_time']));
                    $displayEnd   = date("d-m-Y H:i", strtotime($currentAcademicYear['End_date_time']));
                    ?>
                    <div class="alert alert-success" style="margin-top: 15px;">
                        <h5>Current Academic Year: <strong><?= $displayCurrentYear; ?></strong></h5>
                        <p>Start Date: <strong><?= $displayStart; ?></strong></p>
                        <p>End Date: <strong><?= $displayEnd; ?></strong></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">No Academic Year Found</div>
                <?php endif; ?>

                <hr>

                <!-- Add New Academic Year Form -->
                <h5 class="mb-3">Add New Academic Year</h5>
                <form method="POST" action="">
                    <!-- Academic Year -->
                    <div class="mb-3">
                        <label for="academic_year" class="form-label">Academic Year</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ph-calendar"></i></span>
                            <input type="text" 
                                id="academic_year" 
                                name="academic_year" 
                                class="form-control" 
                                placeholder="Enter academic year (e.g if the academic year is 2024-2025, type 2024)" 
                                pattern="20\d{2}"
                                maxlength="4"
                                title="Academic year must be 4 digits starting with 20 (e.g. 2023)"
                                required>
                        </div>
                    </div>

                    <!-- Start Date & Time -->
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Voting Start Date & Time</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ph-calendar"></i></span>
                            <!-- We'll show user "DD-MM-YYYY HH:mm" in the date picker -->
                            <input type="text" id="start-date-picker" name="start_date" class="form-control" placeholder="Select start date & time" required>
                        </div>
                    </div>

                    <!-- End Date & Time -->
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Voting End Date & Time</label>
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
                                <th>Voting Start Date & Time</th>
                                <th>Voting End Date & Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($academicYears as $index => $year): ?>
                                <?php 
                                    // +1 logic for each row
                                    $displayYear = $year['Academic_year'] . '-' . ($year['Academic_year'] + 1);
                                    $displayStart = date("d-m-Y H:i", strtotime($year['Start_date_time']));
                                    $displayEnd   = date("d-m-Y H:i", strtotime($year['End_date_time']));
                                    $editStart = date("Y-m-d\TH:i", strtotime($year['Start_date_time']));
                                    $editEnd   = date("Y-m-d\TH:i", strtotime($year['End_date_time']));
                                ?>
                                <tr>
                                    <td><?= $displayYear; ?></td>
                                    <td><?= $displayStart; ?></td>
                                    <td><?= $displayEnd; ?></td>
                                    <td>
                                        <?php if ($index === 0): ?>
                                            <!-- Show the edit button only for the first (most recent) row -->
                                            <button class="btn btn-primary btn-sm edit-btn" 
                                                    data-id="<?= htmlspecialchars($year['YearID']); ?>"  
                                                    data-year="<?= htmlspecialchars($year['Academic_year']); ?>"
                                                    data-start="<?= $editStart; ?>"
                                                    data-end="<?= $editEnd; ?>">
                                                <i class="fa-solid fa-pen"></i> Edit
                                            </button>
                                        <?php else: ?>
                                            <!-- No edit button for older academic years -->
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>

            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Academic Year</h5>
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" aria-label="Close" style="border:none; background:none; font-size:24px;">
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="academic_year_id" id="academic_year_id">

                        <!-- Academic Year -->
                        <div class="mb-3">
                            <label for="academic_year" class="form-label">Academic Year</label>
                            <input type="text" class="form-control" id="academic_year" name="academic_year" 
                            pattern="20\d{2}" maxlength="4" 
                            title="Academic year must be 4 digits starting with 20 (e.g. 2023)" required>

                        </div>

                        <!-- Start Date & Time -->
                        <div class="mb-3">
                            <label for="start_date" class="form-label">Voting Start Date & Time</label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required>
                        </div>

                        <!-- End Date & Time -->
                        <div class="mb-3">
                            <label for="end_date" class="form-label">Voting End Date & Time</label>
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
            // Let the user see "DD-MM-YYYY HH:mm" in the pickers
            function initializeDatePicker(id) {
                $(id).daterangepicker({
                    singleDatePicker: true,
                    timePicker: true,
                    timePickerIncrement: 15,
                    timePicker24Hour: true,
                    showDropdowns: true,
                    autoApply: true,
                    locale: {
                        format: 'DD-MM-YYYY HH:mm'
                    }
                }).on('show.daterangepicker', function (ev, picker) {
                    // Show month/year dropdown
                    $('.daterangepicker select.monthselect').show();
                    $('.daterangepicker select.yearselect').show();
                });
            }

            initializeDatePicker('#start-date-picker');
            initializeDatePicker('#end-date-picker');

            // IMPORTANT: scope all form-field updates to #editModal
            $(".edit-btn").click(function () {
                let id    = $(this).data("id");
                let year  = $(this).data("year");
                let start = $(this).data("start");
                let end   = $(this).data("end");

                // Update fields inside the edit modal only
                $("#editModal #academic_year_id").val(id);
                $("#editModal #academic_year").val(year);
                $("#editModal #start_date").val(start);
                $("#editModal #end_date").val(end);

                $("#editModal").modal("show");
            });
        });
        

        document.getElementById("editModal").addEventListener("input", function(event) {
            const input = event.target;
            if (input.id === "academic_year") {
                let value = input.value.replace(/\D/g, ""); // Remove non-digit characters
                if (value.length >= 2 && value.substring(0, 2) !== "20") {
                    value = "20" + value.substring(2); // Ensure it starts with "20"
                }
                if (value.length > 4) {
                    value = value.substring(0, 4); // Limit to 4 digits
                }
                input.value = value;
            }
        });

    </script>

</body>
</html>
