<?php
session_start();

// Integrated removal API: if action=removeCandidate is provided in the URL and a POST winnerID exists, process removal and exit.
if (isset($_GET['action']) && $_GET['action'] === 'removeCandidate' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['winnerID'])) {
    require_once __DIR__ . '/database/dbConnection.php';
    header('Content-Type: application/json');
    $winnerID = (int) $_POST['winnerID'];
    try {
        $stmt = $pdo->prepare("DELETE FROM Winners_Table WHERE WinnerID = :winnerID");
        $stmt->execute([':winnerID' => $winnerID]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Candidate removed successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Candidate not found or already removed.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Normal setWinners.php code starts here

// Include authentication middleware
require_once 'api/authMiddleware.php';

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/database/dbConnection.php';
$user = $_SESSION['user'];

$message = '';
$error   = '';

// -------------------------
// BEGIN: Admin Access Check
// -------------------------
try {
    $adminQuery = "SELECT 1 
                     FROM Admin_Table 
                    WHERE AdminSuUsername = :username 
                      AND checkRole <> 'Removed'
                      AND Role IN ('IT_Admin', 'Admin')
                    LIMIT 1";
    $adminStmt = $pdo->prepare($adminQuery);
    $adminStmt->execute([':username' => $user]);
    if (!$adminStmt->fetch()) {
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Admin check failed: " . $e->getMessage());
}
// -------------------------
// END: Admin Access Check
// -------------------------

// -------------------------
// A) Handle "Finish Winner List" Button
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finishWinnerList'])) {
    // Retrieve current academic year
    try {
        $stmtAcademicYear = $pdo->prepare("
            SELECT YearID, Academic_year, Start_date_time, End_date_time 
            FROM AcademicYear_Table 
            ORDER BY Start_date_time DESC
            LIMIT 1
        ");
        $stmtAcademicYear->execute();
        $academicYear = $stmtAcademicYear->fetch(PDO::FETCH_ASSOC);
        if (!$academicYear) {
            $error = "No academic year found.";
        } else {
            $yearID = $academicYear['YearID'];
        }
    } catch (PDOException $e) {
        $error = "Database error retrieving academic year: " . $e->getMessage();
    }
    
    if (empty($error)) {
        // Update winners for the current academic year only
        try {
            $stmtUpdate = $pdo->prepare("
                UPDATE Winners_Table 
                SET readyDisplay = 'yes', displayDate = NOW() 
                WHERE YearID = :yearID AND readyDisplay = 'no'
            ");
            $stmtUpdate->execute([':yearID' => $yearID]);
            $message = "Winner list finished successfully.";
        } catch (PDOException $e) {
            $error = "Error finishing winner list: " . $e->getMessage();
        }
    }
}

// -------------------------
// B) Handle Winner Submission (only if not finishing list)
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['finishWinnerList'])) {
    $categoryID = isset($_POST['categoryID']) ? (int) $_POST['categoryID'] : 0;
    $winnerName = trim($_POST['winnerName'] ?? '');
    $faculty    = trim($_POST['faculty'] ?? '');
    $rank       = trim($_POST['rank'] ?? '');
    
    if ($categoryID <= 0 || empty($winnerName) || empty($faculty) || empty($rank)) {
        $error = "All fields (category, winner name, faculty, and rank) are required.";
    } elseif (!isset($_FILES['winnerImage']) || $_FILES['winnerImage']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid winner image (error code: " . ($_FILES['winnerImage']['error'] ?? 'null') . ").";
    } else {
        try {
            $stmtAcademicYear = $pdo->prepare("
                SELECT YearID, Academic_year, Start_date_time, End_date_time 
                FROM AcademicYear_Table 
                ORDER BY Start_date_time DESC
                LIMIT 1
            ");
            $stmtAcademicYear->execute();
            $academicYear = $stmtAcademicYear->fetch(PDO::FETCH_ASSOC);
            if (!$academicYear) {
                $error = "No academic year found.";
            } else {
                $yearID = $academicYear['YearID'];
            }
        } catch (PDOException $e) {
            $error = "Database error retrieving academic year: " . $e->getMessage();
        }
    }
    
    if (empty($error)) {
        $fileTmpPath = $_FILES['winnerImage']['tmp_name'];
        if (!file_exists($fileTmpPath)) {
            $error = "Temporary upload file not found at: " . htmlspecialchars($fileTmpPath);
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $actualMime = mime_content_type($fileTmpPath);
            if (!in_array($actualMime, $allowedTypes)) {
                $error = "Only JPG, PNG, and GIF files are allowed. Detected: " . htmlspecialchars($actualMime);
            } else {
                $uploadDir = __DIR__ . '/winnerImages/';
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                        $error = "Could not create directory: " . htmlspecialchars($uploadDir);
                    }
                }
                if (empty($error)) {
                    $extension = pathinfo($_FILES['winnerImage']['name'], PATHINFO_EXTENSION);
                    $newFileName = uniqid("winner_", true) . '.' . $extension;
                    $destination = $uploadDir . $newFileName;
                    if (!move_uploaded_file($fileTmpPath, $destination)) {
                        $error = "Failed to move uploaded file to: " . htmlspecialchars($destination);
                    } else {
                        try {
                            $stmtInsert = $pdo->prepare("
                                INSERT INTO Winners_Table (CategoryID, YearID, WinnerName, Faculty, Rank, ImagePath)
                                VALUES (:categoryID, :yearID, :winnerName, :faculty, :rank, :imagePath)
                            ");
                            $stmtInsert->execute([
                                ':categoryID' => $categoryID,
                                ':yearID'     => $yearID,
                                ':winnerName' => $winnerName,
                                ':faculty'    => $faculty,
                                ':rank'       => $rank,
                                ':imagePath'  => 'winnerImages/' . $newFileName
                            ]);
                            $message = "Winner successfully set for the selected category.";
                        } catch (PDOException $e) {
                            $error = "Database error: " . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

// -------------------------
// 2) Fetch categories for the dropdown
// -------------------------
try {
    $stmtCats = $pdo->prepare("
        SELECT CategoryID, CategoryCode, CategoryDescription 
        FROM Category_Table 
        ORDER BY CategoryID ASC
    ");
    $stmtCats->execute();
    $categories = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}

// -------------------------
// 3) Fetch winners for the CURRENT ACADEMIC YEAR (only those with readyDisplay = 'yes') for preview
// -------------------------
$currentYearID = 0;
try {
    $stmtCurrentYear = $pdo->prepare("
        SELECT YearID 
        FROM AcademicYear_Table 
        ORDER BY Start_date_time DESC
        LIMIT 1
    ");
    $stmtCurrentYear->execute();
    $currentYearRow = $stmtCurrentYear->fetch(PDO::FETCH_ASSOC);
    if ($currentYearRow) {
        $currentYearID = $currentYearRow['YearID'];
    }
} catch (PDOException $e) {
    $currentYearID = 0;
}

$allWinners = [];
try {
    $stmtWinners = $pdo->prepare("
        SELECT w.WinnerID, w.WinnerName, w.Faculty, w.Rank, w.ImagePath, 
               c.CategoryCode, c.CategoryDescription
        FROM Winners_Table w
        JOIN Category_Table c ON w.CategoryID = c.CategoryID
        WHERE w.YearID = :yearID
        ORDER BY 
            CASE w.Rank
                WHEN 'Rank-1' THEN 1
                WHEN 'Rank-2' THEN 2
                WHEN 'Rank-3' THEN 3
                ELSE 4
            END ASC,
            w.CreatedAt DESC
    ");
    $stmtWinners->execute([':yearID' => $currentYearID]);
    $allWinners = $stmtWinners->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allWinners = [];
}

$winnersByCategory = [];
foreach ($allWinners as $row) {
    $catCode = $row['CategoryCode'];
    $winnersByCategory[$catCode][] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Set Winner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS & FontAwesome -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet">
    <link href="assets/css/components.min.css" rel="stylesheet">
    <link href="assets/css/layout.min.css" rel="stylesheet">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f9f9f9;
            padding-top: 40px;
            overflow-y: auto;
        }
        .container {
            max-width: 600px;
        }
        .alert {
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        /* Prelook container styling */
        .prelook-container {
            margin-top: 30px;
        }
        /* Category row styling for collapsible preview */
        .category-row {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #ffffff;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .category-row:hover {
            background: #f1f1f1;
        }
        .category-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        .category-info {
            flex: 1;
        }
        .category-info h4 {
            margin: 0;
            font-weight: bold;
        }
        .category-info small {
            color: #888;
        }
        .category-extra {
            text-align: right;
            margin-left: 15px;
        }
        .category-extra p {
            margin: 0;
            color: #666;
        }
        .category-extra a {
            color: #007bff;
            text-decoration: underline;
            cursor: pointer;
        }
        /* Collapsible winners list */
        .winners-collapse {
            display: none;
            padding-left: 95px;
            margin-bottom: 15px;
        }
        .winners-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        .winner-box {
            text-align: center;
            width: 120px;
            position: relative;
        }
        .winner-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 2px solid #fff;
            border-radius: 50%;
            margin-bottom: 8px;
            background-color: #ccc;
        }
        .winner-box p {
            margin: 0;
        }
        .winner-box .faculty {
            font-size: 0.9rem;
            color: #666;
        }
        .winner-box .rank {
            font-size: 0.8rem;
            color: #999;
        }
        /* Remove Candidate button */
        .remove-candidate {
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 0.7rem;
            padding: 2px 5px;
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 50%;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container">
    <h2 class="text-center">Set Winner</h2>
    
    <!-- Display success or error messages -->
    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <?= nl2br(htmlspecialchars($message)) ?>
        </div>
    <?php elseif (!empty($error)): ?>
        <div class="alert alert-danger" style="white-space: pre-line;">
            <?= nl2br(htmlspecialchars($error)) ?>
        </div>
    <?php endif; ?>

    <!-- Winner creation form -->
    <form action="setWinners.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="categoryID">Select Category</label>
            <select name="categoryID" id="categoryID" class="form-control" required>
                <option value="">-- Select Category --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['CategoryID'] ?>">
                        <?= htmlspecialchars($cat['CategoryCode']) ?> - <?= htmlspecialchars($cat['CategoryDescription']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="winnerName">Winner Name and Surname</label>
            <input type="text" class="form-control" id="winnerName" name="winnerName" placeholder="Enter name and surname" required>
        </div>
        <div class="form-group">
            <label for="faculty">Faculty</label>
            <select name="faculty" id="faculty" class="form-control" required>
                <option value="">Select Faculty</option>
                <option value="FENS">FENS</option>
                <option value="FASS">FASS</option>
                <option value="SBS">SBS</option>
            </select>
        </div>
        <div class="form-group">
            <label>Rank</label><br>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="rank" id="rank1" value="Rank-1" autocomplete="off" required>
                <label class="btn btn-outline-primary" for="rank1">Rank-1</label>
                <input type="radio" class="btn-check" name="rank" id="rank2" value="Rank-2" autocomplete="off" required>
                <label class="btn btn-outline-primary" for="rank2">Rank-2</label>
                <input type="radio" class="btn-check" name="rank" id="rank3" value="Rank-3" autocomplete="off" required>
                <label class="btn btn-outline-primary" for="rank3">Rank-3</label>
            </div>
        </div>
        <div class="form-group">
            <label for="winnerImage">Winner Image</label>
            <input type="file" class="form-control" id="winnerImage" name="winnerImage" accept="image/*" required>
        </div>
        <button type="submit" class="btn btn-custom btn-block" style="background-color: #45748a; color: #fff;">
            Submit Winner
        </button>
    </form>

    <!-- Separate form for finishing winner list -->
    <form action="setWinners.php" method="POST" style="margin-top: 20px;">
        <button type="submit" name="finishWinnerList" class="btn btn-custom btn-block" style="background-color: #28a745; color: #fff;">
            Finish Winner List
        </button>
    </form>

    <!-- Preview Section: Accordion-like collapsible category previews with Remove Candidate buttons -->
    <div class="prelook-container">
        <h3 class="mt-5 mb-4 text-center">Current Winners Preview</h3>
        <?php if (empty($winnersByCategory)): ?>
            <p class="text-center">No winners found for the current academic year.</p>
        <?php else: ?>
            <?php foreach ($winnersByCategory as $catCode => $winnerRows): ?>
                <?php
                    // Create a unique toggle ID based on category code
                    $toggleID = 'toggle-' . preg_replace('/[^A-Za-z0-9\-_]/', '-', $catCode);
                    $categoryDesc = $winnerRows[0]['CategoryDescription'] ?? '';
                ?>
                <!-- Category Row -->
                <div class="category-row" data-toggle="<?= $toggleID ?>">
                    <img src="assets/global_assets/images/placeholders/placeholder.jpg" alt="Category Image" class="category-img">
                    <div class="category-info">
                        <h4><?= htmlspecialchars($catCode) ?></h4>
                        <small><?= htmlspecialchars($categoryDesc) ?></small>
                    </div>
                    <div class="category-extra">
                        <p>31 December 2024</p>
                        <a>See Winners</a>
                    </div>
                </div>
                <!-- Collapsible Winner List for this Category -->
                <div id="<?= $toggleID ?>" class="winners-collapse">
                    <div class="winners-row">
                        <?php foreach ($winnerRows as $w): ?>
                            <div class="winner-box">
                                <img src="<?= htmlspecialchars($w['ImagePath']) ?>" 
                                     alt="<?= htmlspecialchars($w['WinnerName']) ?>" 
                                     class="winner-img">
                                <p><strong><?= htmlspecialchars($w['WinnerName']) ?></strong></p>
                                <p class="faculty"><?= htmlspecialchars($w['Faculty']) ?></p>
                                <p class="rank"><?= htmlspecialchars($w['Rank']) ?></p>
                                <button type="button" class="btn btn-danger btn-sm remove-candidate" data-id="<?= htmlspecialchars($w['WinnerID']) ?>">
                                    Remove Candidate
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Include jQuery and Bootstrap Bundle JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Toggle winners list when a category row is clicked
    $('.category-row').on('click', function() {
        const toggleID = $(this).data('toggle');
        $('#' + toggleID).slideToggle();
    });
    
    // AJAX removal of candidate
    $('.remove-candidate').on('click', function(e) {
        e.stopPropagation(); // Prevent toggling of the parent category row
        if (!confirm("Are you sure you want to remove this candidate?")) {
            return;
        }
        const winnerID = $(this).data('id');
        const $candidateBox = $(this).closest('.winner-box');
        $.ajax({
            url: 'setWinners.php?action=removeCandidate',
            type: 'POST',
            data: { winnerID: winnerID },
            dataType: 'json',
            success: function(response) {
                console.log("Remove response:", response);
                if (response.success) {
                    $candidateBox.fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert("Failed to remove candidate: " + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                alert("An error occurred while removing the candidate.");
            }
        });
    });
});
</script>
</body>
</html>
