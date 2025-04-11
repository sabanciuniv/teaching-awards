<?php
session_start();

// Include authentication middleware
require_once 'api/authMiddleware.php';

// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

require_once __DIR__ . '/database/dbConnection.php';
$user = $_SESSION['user'];

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

$message = '';
$error   = '';

// -------------------------
// 1) Handle form submission
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $categoryID = isset($_POST['categoryID']) ? (int) $_POST['categoryID'] : 0;
    $winnerName = trim($_POST['winnerName'] ?? '');
    $faculty    = trim($_POST['faculty'] ?? '');
    $rank       = trim($_POST['rank'] ?? '');

    // Validate required fields
    if ($categoryID <= 0 || empty($winnerName) || empty($faculty) || empty($rank)) {
        $error = "All fields (category, winner name, faculty, and rank) are required.";
    } elseif (!isset($_FILES['winnerImage']) || $_FILES['winnerImage']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid winner image (error code: " . ($_FILES['winnerImage']['error'] ?? 'null') . ").";
    } else {
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
    }

    // If no error so far, proceed with file upload and DB insert
    if (empty($error)) {
        $fileTmpPath = $_FILES['winnerImage']['tmp_name'];
        if (!file_exists($fileTmpPath)) {
            $error = "Temporary upload file not found at: " . htmlspecialchars($fileTmpPath);
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $actualMime   = mime_content_type($fileTmpPath);

            if (!in_array($actualMime, $allowedTypes)) {
                $error = "Only JPG, PNG, and GIF files are allowed. Detected: " . htmlspecialchars($actualMime);
            } else {
                $uploadDir = __DIR__ . '/winnerImages/';
                // Create folder if not exists
                if (!is_dir($uploadDir)) {
                    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
                        $error = "Could not create directory: " . htmlspecialchars($uploadDir);
                    }
                }

                if (empty($error)) {
                    // Unique file name
                    $extension   = pathinfo($_FILES['winnerImage']['name'], PATHINFO_EXTENSION);
                    $newFileName = uniqid("winner_", true) . '.' . $extension;
                    $destination = $uploadDir . $newFileName;

                    if (!move_uploaded_file($fileTmpPath, $destination)) {
                        $error = "Failed to move uploaded file to: " . htmlspecialchars($destination);
                    } else {
                        // Insert record into Winners_Table
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
// 3) Fetch winners to display "preview" for each category
// -------------------------
$allWinners = [];
try {
    $stmtWinners = $pdo->query("
        SELECT w.WinnerID, w.WinnerName, w.Faculty, w.Rank, w.ImagePath, 
               c.CategoryCode, c.CategoryDescription
        FROM Winners_Table w
        JOIN Category_Table c ON w.CategoryID = c.CategoryID
        ORDER BY w.CreatedAt DESC
    ");
    $allWinners = $stmtWinners->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allWinners = [];
}

// Group winners by category code
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
    <title>Set Winners</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
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

        /* Make the "winners preview" area scrollable if it gets large */
        .prelook-container {
            max-height: 700px; /* adjust as you like */
            overflow-y: auto;
            margin-top: 30px;
        }

        /* The "category row" styling - each category is a clickable row to toggle winners */
        .category-row {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer; /* so user sees it's clickable */
            transition: background 0.3s ease;
        }
        .category-row:hover {
            background: #f1f1f1;
        }
        /* Category image on the left */
        .category-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            margin-right: 15px;
        }
        /* Category info in the middle */
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
        /* Date & "See winners" on the right */
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

        /* The collapsible winners area */
        .winners-collapse {
            display: none;
            margin-left: 95px; /* indentation to align with text after the category image */
            margin-bottom: 15px;
        }
        .winners-row {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
        .winner-box {
            text-align: center;
            width: 120px;
        }
        .winner-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 2px solid #fff;
            border-radius: 50%;
            margin-bottom: 8px;
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

    </style>
</head>
<body>
<div class="container">
    <h2 class="text-center">Set Winner</h2>
    
    <!-- Display success/error messages -->
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

    <!-- Prelook/Preview Section: Scrollable container of categories & winners -->
    <div class="prelook-container">
        <h3 class="mt-5 mb-4">Current Winners Preview</h3>
        <?php if (count($winnersByCategory) === 0): ?>
            <p>No winners yet. Once you add winners, they will appear here.</p>
        <?php else: ?>
            <?php foreach ($winnersByCategory as $catCode => $winnerRows): ?>
                <?php
                  // Show up to 3 winners for each category
                  $topWinners = array_slice($winnerRows, 0, 3);
                  $categoryDesc = $topWinners[0]['CategoryDescription'] ?? '';
                  // Generate a safe ID for toggling
                  $toggleID = 'toggle-' . preg_replace('/[^A-Za-z0-9\-_]/', '-', $catCode);
                ?>
                <!-- Category Row -->
                <div class="category-row" data-toggle="<?= $toggleID ?>">
                    <!-- Left: placeholder category image -->
                    <img src="assets/global_assets/images/placeholders/placeholder.jpg" alt="Category Image" class="category-img">
                    
                    <!-- Middle: category info -->
                    <div class="category-info">
                        <h4><?= htmlspecialchars($catCode) ?></h4>
                        <small><?= htmlspecialchars($categoryDesc) ?></small>
                    </div>
                    
                    <!-- Right: date + "See Winners" link (placeholder date here) -->
                    <div class="category-extra">
                        <a>See Winners</a>
                    </div>
                </div>
                <!-- Collapsible winner list -->
                <div id="<?= $toggleID ?>" class="winners-collapse">
                    <div class="winners-row">
                        <?php foreach ($topWinners as $w): ?>
                            <div class="winner-box">
                                <img src="<?= htmlspecialchars($w['ImagePath']) ?>" 
                                     alt="<?= htmlspecialchars($w['WinnerName']) ?>" 
                                     class="winner-img">
                                <p><strong><?= htmlspecialchars($w['WinnerName']) ?></strong></p>
                                <p class="faculty"><?= htmlspecialchars($w['Faculty']) ?></p>
                                <p class="rank"><?= htmlspecialchars($w['Rank']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div> <!-- End prelook-container -->
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Toggle winners on category-row click
    $('.category-row').on('click', function() {
        // get the toggling ID from data-toggle attribute
        const toggleID = $(this).data('toggle');
        // find the collapsible div
        const $collapseDiv = $('#' + toggleID);

        // If it's visible, hide it; if hidden, show it
        if ($collapseDiv.is(':visible')) {
            $collapseDiv.slideUp(); // animate collapse
        } else {
            $collapseDiv.slideDown(); // animate expand
        }
    });
});
</script>
</body>
</html>
