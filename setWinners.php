<?php
require_once 'api/commonFunc.php';
require_once 'api/authMiddleware.php';
require_once __DIR__ . '/database/dbConnection.php';
$pageTitle= "Set Winner";
require_once 'api/header.php';

init_session();
$user = $_SESSION['user'];

// Admin access check
$role = getUserAdminRole($pdo, $user);
if (!in_array($role, ['Admin', 'IT_Admin'])) {
    header("Location: accessDenied.php");
    exit();
}

$error   = '';

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

$message = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $message = "Winner successfully set for the selected category.";
}

$categories = getAllCategories($pdo);
$academicYearsStmt = $pdo->prepare(
    "SELECT YearID, Academic_year
      FROM AcademicYear_Table
  ORDER BY Academic_year DESC"
);
$academicYearsStmt->execute();
$academicYears = $academicYearsStmt->fetchAll(PDO::FETCH_ASSOC);

// *** FIX: determine preview year before use ***
$academicYearData = fetchCurrentAcademicYear($pdo);
$selectedYearID = isset($_GET['previewYearID'])
    ? (int) $_GET['previewYearID']
    : ($academicYearData['YearID'] ?? 0);

// Then use $selectedYearID when fetching winners:
try {
    $stmtWinners = $pdo->prepare(
        "SELECT w.WinnerID, w.WinnerName, w.Faculty, w.`Rank`, w.ImagePath,
               w.candidate_points, c.CategoryCode, c.CategoryDescription
          FROM Winners_Table w
          JOIN Category_Table  c ON w.CategoryID = c.CategoryID
         WHERE w.YearID = :yearID
      ORDER BY
           CASE w.`Rank`
               WHEN 'Rank-1' THEN 1
               WHEN 'Rank-2' THEN 2
               WHEN 'Rank-3' THEN 3
               ELSE 4
           END,
           w.CreatedAt DESC"
    );
    $stmtWinners->execute([':yearID' => $selectedYearID]);
    $allWinners = $stmtWinners->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $allWinners = [];
}

// -------------------------
// A) Handle "Finish Winner List"
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finishWinnerList'])) {
    // take the year the admin selected:
    $yearID = isset($_POST['yearID']) 
        ? (int) $_POST['yearID'] 
        : ($academicYearData['YearID'] ?? 0);

    try {
        $stmtUpdate = $pdo->prepare("
            UPDATE Winners_Table
               SET readyDisplay = 'yes',
                   displayDate  = NOW()
             WHERE YearID      = :yearID
               AND readyDisplay = 'no'
        ");
        $stmtUpdate->execute([':yearID' => $yearID]);
        header("Location: setWinners.php?success=1&previewYearID={$yearID}");
        exit;
    } catch (PDOException $e) {
        $error = "Error finishing winner list: " . $e->getMessage();
    }
}

// -------------------------
// A.2) Handle "Unpublish Winner List"
// -------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unpublishWinnerList'])) {
    $yearID = isset($_POST['yearID'])
        ? (int) $_POST['yearID']
        : ($academicYearData['YearID'] ?? 0);

    try {
        $stmtUnpublish = $pdo->prepare("
            UPDATE Winners_Table
               SET readyDisplay = 'no',
                   displayDate  = NULL
             WHERE YearID      = :yearID
               AND readyDisplay = 'yes'
        ");
        $stmtUnpublish->execute([':yearID' => $yearID]);
        header("Location: setWinners.php?previewYearID={$yearID}");
        exit;
    } catch (PDOException $e) {
        $error = "Error unpublishing winner list: " . $e->getMessage();
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
    $points     = isset($_POST['candidate_points']) ? (int)$_POST['candidate_points'] : 0;
    
    if ($categoryID <= 0 || empty($winnerName) || empty($faculty) || empty($rank)) {
        $error = "All fields (category, winner name, faculty, and rank) are required.";
    } elseif (!isset($_FILES['winnerImage']) || $_FILES['winnerImage']['error'] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid winner image (error code: " . ($_FILES['winnerImage']['error'] ?? 'null') . ").";
    } else {
        if (isset($_POST['YearID']) && (int)$_POST['YearID'] > 0) {
            $yearID = (int)$_POST['YearID'];
        } else {
            $error = "Please select an academic year.";
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
                            $stmtInsert = $pdo->prepare(
                                "INSERT INTO Winners_Table (CategoryID, YearID, WinnerName, Faculty, `Rank`, ImagePath, SuNET_Username, SU_ID, Email, candidate_points)
                                VALUES (:categoryID, :yearID, :winnerName, :faculty, :rank, :imagePath, :suNET_Username, :su_ID, :email, :candidate_points)"
                            );
                            $stmtInsert->execute([
                                ':categoryID' => $categoryID,
                                ':yearID'     => $yearID,
                                ':winnerName' => $winnerName,
                                ':faculty'    => $faculty,
                                ':rank'       => $rank,
                                ':imagePath'  => 'winnerImages/' . $newFileName,
                                ':suNET_Username' => trim($_POST['username']),
                                ':su_ID' => ($_POST['suid']),
                                ':email' => trim($_POST['email']),
                                ':candidate_points' => $points,
                            ]);
                            header("Location: setWinners.php?success=1");
                            exit;
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
// 3) Fetch winners for the CURRENT ACADEMIC YEAR (only those with readyDisplay = 'yes') for preview
// -------------------------
$currentYearID = 0;

$academicYearData = fetchCurrentAcademicYear($pdo);
$currentYearID = $academicYearData['YearID'] ?? 0;


$allWinners = [];

try {
    $stmtWinners = $pdo->prepare(
        "SELECT w.WinnerID, w.WinnerName, w.Faculty, w.`Rank`, w.ImagePath,
               w.candidate_points, c.CategoryCode, c.CategoryDescription
          FROM Winners_Table w
          JOIN Category_Table  c ON w.CategoryID = c.CategoryID
         WHERE w.YearID = :yearID
      ORDER BY
           CASE w.`Rank`
               WHEN 'Rank-1' THEN 1
               WHEN 'Rank-2' THEN 2
               WHEN 'Rank-3' THEN 3
               ELSE 4
           END,
           w.CreatedAt DESC"
    );
    $stmtWinners->execute([':yearID' => $selectedYearID]);
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
<style>
body {
    background-color: #f9f9f9;
    padding-top: 80px;
    overflow-y: auto;
}
.container {
    max-width: 900px;
    margin: auto;
}

    /* make both buttons the same width */
.action-container form button {
    min-width: 140px;
    width: 100%;
}
/* wrap the two forms in a row and align to the right */
.action-container {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 1rem;  /* give some breathing room under the preview */
}
.alert {
    margin-top: 20px;
}
.form-group {
    margin-bottom: 15px;
}
.unified-section {
    max-width: 900px;
    margin: 0 auto;
}

#multiStepFormWrapper,
.prelook-container {
    width: 100%;
}

#multiStepForm {
    margin-bottom: 20px;
    background: #ffffff;
    border: 1px solid #dee2e6;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 40px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
    overflow: visible;
    position: relative;
}

.prelook-container {
    margin-top: 20px; /* reduce spacing */
}
/* Category row styling for collapsible preview */
.category-row {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #ffffff;
    border-radius: 12px; /* make the corners more round */
    margin-bottom: 15px;
    cursor: pointer;
    transition: background 0.3s ease;
    border: 1px solid #dee2e6; /* light gray border like the form */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05); /* subtle shadow for depth */
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
.remove-candidate {
    position: absolute;
    top: -5px;
    right: -5px;
    width: 22px;
    height: 22px;
    background: #dc3545;
    color: #fff;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.wizard-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    margin-bottom: 40px;
}

.step {
    text-align: center;
    position: relative;
    flex: 1;
}

.step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 20px;
    right: -50%;
    width: 100%;
    height: 2px;
    background-color: #dee2e6;
    z-index: 0;
}

.step-icon {
    background-color: #fff;
    border: 2px solid #ccc;
    color: #ccc;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    margin: 0 auto 10px;
    line-height: 36px;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 1;
}

.step.active .step-icon,
.step.completed .step-icon {
    border-color: #3b82f6;
    background-color: #3b82f6;
    color: #fff;
}

.step-title {
    font-size: 14px;
    color: #333;
}

.form-step {
    display: none;
}

.form-step.active {
    display: block;
}

.btn-nav {
    min-width: 120px;
}
.dropdown-select {
    background-color: #fff !important;
    color: #495057 !important;
    border: 1px solid #ced4da !important;
    border-radius: 6px !important;
    padding: 10px 12px;
    font-size: 1rem;
}
.dropdown-menu {
    transform: translate(0, 0) !important;
    top: 100% !important;
    left: 0;
    width: 100%;
    max-height: 250px;
    overflow-y: auto;
}
.dropdown-menu a {
    font-size: 0.95rem;
}
.dropdown-menu .dropdown-item {
    white-space: normal;
    word-break: break-word;
    line-height: 1.3;
    padding: 10px 15px;
}
.dropdown-menu-up {
    top: auto !important;
    bottom: 100% !important;
    transform: translateY(-0.5rem) !important;
}

.dropdown-menu-down {
    top: 100% !important;
    bottom: auto !important;
    transform: translateY(0.5rem) !important;
}
.dropdown-toggle {
    border-radius: 6px;
    padding: 0.5rem 1rem;
}
.title {
    font-size: 1.5rem;
    font-weight: bold;
    margin-top: 10px;
    margin-bottom: 20px;
    color: #000;
    text-align: center;
}
</style>
<body>
<?php $backLink = "adminDashboard.php"; include 'navbar.php'; ?>
<div class="container unified-section">
    <div class="title">Set Winners</div>  
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
    <div class="row mb-5">
        <div class= "col-12">
            <div id="multiStepFormWrapper">
                <form id="multiStepForm" method="POST" enctype="multipart/form-data">
                    <div class="wizard-steps mb-4">
                        <div class="step active" data-step="1">
                            <div class="step-icon"><i class="fas fa-user"></i></div>
                            <div class="step-title">Winner's Personal Information</div>
                        </div>
                        <div class="step" data-step="2">
                            <div class="step-icon"><i class="fas fa-image"></i></div>
                            <div class="step-title">Rank & Photo</div>
                        </div>
                    </div>
                    <!-- Step 1 -->
                    <div class="form-step active" data-step="1">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="YearID">Academic Year:</label>
                            <select name="YearID" id="YearID" class="form-control" required>
                                <option value="" disabled selected>Choose year…</option>
                                <?php foreach ($academicYears as $ay): ?>
                                <option value="<?= $ay['YearID'] ?>">
                                    <?= htmlspecialchars($ay['Academic_year'] . ' – ' . ($ay['Academic_year'] + 1)) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="categoryDropdown" class="form-label">Category:</label>
                            <div class="dropdown">
                                <button type="button" class="form-control text-start dropdown-toggle" id="categoryDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    Select Category
                                </button>
                                <ul class="dropdown-menu w-100" aria-labelledby="categoryDropdown">
                                    <?php foreach ($categories as $cat): ?>
                                        <li>
                                            <a class="dropdown-item category-option" href="#" data-id="<?= $cat['CategoryID'] ?>">
                                                <?= htmlspecialchars($cat['CategoryCode'] . ' - ' . $cat['CategoryDescription']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <input type="hidden" name="categoryID" id="categoryID" required>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Email address:</label>
                            <input type="email" name="email" class="form-control" placeholder="your@sabanciuniv.edu" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Name and Surname:</label>
                            <input type="text" name="winnerName" class="form-control" placeholder="Hüsnü Yenigün" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>SuNET Username:</label>
                            <input type="text" name="username" class="form-control" placeholder="husnu.yenigun" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Faculty:</label>
                            <input type="text" name="faculty" class="form-control" placeholder="FENS" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>SU ID:</label>
                            <input type="text" name= "suid" class="form-control" placeholder="e.g. 00031099" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="candidatePoints" class="form-label">Points:</label>
                            <input type="number" name="candidate_points" id="candidatePoints" class="form-control" placeholder="e.g. 85" required>
                        </div>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="form-step" data-step="2">
                <h5 class="mb-3">Upload Winner Photo</h5>
                <input type="file" class="form-control mb-4" name="winnerImage" accept="image/*" required>

                <h5 class="mb-3">Select Rank</h5>
                <div class="list-group">
                    <label class="list-group-item list-group-item-action">
                    <input class="form-check-input me-1" type="radio" name="rank" value="Rank-1"> Rank 1
                    </label>
                    <label class="list-group-item list-group-item-action">
                    <input class="form-check-input me-1" type="radio" name="rank" value="Rank-2"> Rank 2
                    </label>
                    <label class="list-group-item list-group-item-action">
                    <input class="form-check-input me-1" type="radio" name="rank" value="Rank-3"> Rank 3
                    </label>
                </div>
                </div>

                <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-secondary btn-nav" id="prevBtn">Previous</button>
                <button type="button" class="btn btn-secondary btn-nav" id="nextBtn">Next</button>
                </div>
            </form>
        </div>

        <!-- Preview Section: Accordion-like collapsible category previews with Remove Candidate buttons -->
        <div class="prelook-container mt-4">
            <h4 class="mt-4 mb-4 text-center">Current Winners Preview</h4>
              <!-- Year selector -->
              <form method="GET" class="text-center mb-3">
  <div class="dropdown d-inline-block">
    <button class="btn btn-outline-secondary dropdown-toggle" type="button"
            id="previewYearDropdown" data-bs-toggle="dropdown" aria-expanded="false">
      <?= htmlspecialchars(
           array_reduce(
             $academicYears,
             fn($carry, $ay) => $ay['YearID']===$selectedYearID
               ? ($ay['Academic_year'].' – '.($ay['Academic_year']+1))
               : $carry,
             'Select Year…'
           )
         ) ?>
    </button>
    <ul class="dropdown-menu" aria-labelledby="previewYearDropdown">
      <?php foreach ($academicYears as $ay): ?>
        <li>
          <button class="dropdown-item <?= $ay['YearID']===$selectedYearID?'active':''?>"
                  type="submit"
                  name="previewYearID"
                  value="<?= $ay['YearID'] ?>">
            <?= htmlspecialchars($ay['Academic_year'] . ' – ' . ($ay['Academic_year'] + 1)) ?>
          </button>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
</form>
            <?php if (empty($winnersByCategory)): ?>
                <p class="text-center">No winners found for the current academic year.</p>
            <?php else: ?>   
                <?php
                    ksort($winnersByCategory);
                    foreach ($winnersByCategory as $catCode => $winnerRows): ?>
                    <?php
                        // Create a unique toggle ID based on category code
                        $toggleID = 'toggle-' . preg_replace('/[^A-Za-z0-9\-_]/', '-', $catCode);
                        $categoryDesc = $winnerRows[0]['CategoryDescription'] ?? '';
                    ?>
                    <!-- Category Row -->
                    <div class="category-row" data-toggle="<?= $toggleID ?>">
                        <div class="category-info">
                            <h4><?= htmlspecialchars($catCode) ?></h4>
                            <small><?= htmlspecialchars($categoryDesc) ?></small>
                        </div>
                        <div class="category-extra">
                            <a>View Winners</a>
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
                                    <p class="points"><strong>Points:</strong> <?= htmlspecialchars($w['candidate_points']) ?></p>
                                    <button type="button" class="remove-candidate" data-id="<?= htmlspecialchars($w['WinnerID']) ?>" title="Remove Candidate">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <!-- immediately after the preview div -->
    <div class="action-container">
  <form method="POST">
    <!-- carry the chosen preview year into POST -->
    <input type="hidden" name="yearID" value="<?= $selectedYearID ?>">
    <button name="finishWinnerList" class="btn btn-secondary">
      Publish
    </button>
  </form>
  <form method="POST">
    <input type="hidden" name="yearID" value="<?= $selectedYearID ?>">
    <button name="unpublishWinnerList" class="btn btn-secondary">
      Unpublish
    </button>
  </form>
</div>

<!-- Include jQuery and Bootstrap Bundle JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const dropdownToggle = document.getElementById('categoryDropdown');
        const dropdownMenu = document.querySelector('#categoryDropdown + .dropdown-menu');

        dropdownToggle.addEventListener('click', function () {
            // Wait for dropdown to render
            setTimeout(() => {
                const toggleRect = dropdownToggle.getBoundingClientRect();
                const menuHeight = dropdownMenu.offsetHeight;
                const spaceBelow = window.innerHeight - toggleRect.bottom;
                const spaceAbove = toggleRect.top;

                // Reset classes
                dropdownMenu.classList.remove('dropdown-menu-up');
                dropdownMenu.classList.remove('dropdown-menu-down');

                if (spaceBelow < menuHeight && spaceAbove > menuHeight) {
                    dropdownMenu.classList.add('dropdown-menu-up'); // Add custom upward class
                } else {
                    dropdownMenu.classList.add('dropdown-menu-down');
                }
            }, 10); // Let Bootstrap inject the dropdown first
        });
    });


    let currentStep = 1;

    function showStep(step) {
        document.querySelectorAll('.form-step').forEach(stepDiv => {
            stepDiv.classList.remove('active');
        });
        document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');

        document.querySelectorAll('.wizard-steps .step').forEach(stepDiv => {
            stepDiv.classList.remove('active', 'completed');
            const stepNumber = parseInt(stepDiv.getAttribute('data-step'));
            if (stepNumber < step) stepDiv.classList.add('completed');
            if (stepNumber === step) stepDiv.classList.add('active');
        });

        document.getElementById('prevBtn').style.visibility = step === 1 ? 'hidden' : 'visible';
        document.getElementById('nextBtn').innerText = step === 2 ? 'Submit' : 'Next';
    }

    document.getElementById('nextBtn').addEventListener('click', () => {
        if (currentStep < 2) {
            currentStep++;
            showStep(currentStep);
        } else {
            document.getElementById('multiStepForm').submit();
        }
    });

    document.getElementById('prevBtn').addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    showStep(currentStep);

    $(document).ready(function() {
        // Toggle winners list when a category row is clicked
        $('.category-row').on('click', function() {
            const toggleID = $(this).data('toggle');
            $('#' + toggleID).slideToggle();
        });

        // Handle category dropdown selection
        $('.category-option').on('click', function (e) {
            e.preventDefault();
            const selectedText = $(this).text();
            const selectedId = $(this).data('id');
            $('#categoryDropdown').text(selectedText);
            $('#categoryID').val(selectedId);
        });

        // AJAX removal of candidate
        $('.remove-candidate').on('click', function(e) {
            e.stopPropagation();
            if (!confirm("Are you sure you want to remove this candidate?")) return;

            const winnerID = $(this).data('id');
            const $candidateBox = $(this).closest('.winner-box');
            $.ajax({
                url: 'setWinners.php?action=removeCandidate',
                type: 'POST',
                data: { winnerID: winnerID },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $candidateBox.fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert("Failed to remove candidate: " + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert("An error occurred while removing the candidate.");
                }
            });
        });
    });
</script>

</body>
</html>
