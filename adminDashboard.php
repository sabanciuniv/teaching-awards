<?php
session_start();
require_once 'api/authMiddleware.php';

// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Include DB connection
require_once 'database/dbConnection.php';

// Fetch the username from session
$username = $_SESSION['user'];
$user = $_SESSION['user'];

// Default role 
$role = null;

try {
    // Fetch the user's most recent active row (i.e., checkRole != 'Removed')
    // so if the user was removed and re-added with a different role,
    // we only get the *current* role. 
    $stmt = $pdo->prepare("
        SELECT Role 
        FROM Admin_Table 
        WHERE AdminSuUsername = :username 
          AND checkRole != 'Removed'
        ORDER BY GrantedDate DESC 
        LIMIT 1
    ");
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // If found, set $role to the user's current role
        $role = $row['Role']; 
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabancı University Teaching Awards</title>
    
    <!-- Limitless Theme Styles -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet">
    <link href="assets/css/components.min.css" rel="stylesheet">
    <link href="assets/css/layout.min.css" rel="stylesheet">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding-top: 70px;
            background-color: #f5f5f5;
        }
        .header {
            background-color: #003d78;
            padding: 20px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header img {
            height: 50px;
        }
        .header .title {
            font-size: 24px;
            font-weight: bold;
        }
        .container {
            display: flex;
            padding: 20px;
            margin-top: 80px;
        }
        .menu {
            width: 300px;
            background-color: #e6f0ff;
            border-radius: 8px;
            padding: 20px;
        }
        .menu button {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            font-size: 16px;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .menu button:hover {
            background-color: #3b6cb7;
        }
        .menu .note {
            font-size: 14px;
            color: #333;
            margin-top: -10px;
            margin-bottom: 10px;
        }
        .image-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .image-section img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <div class="container">
        <div class="menu">
            <button 
                class="btn btn-secondary w-100 mb-2" 
                onclick="window.location.href='manageAcademicYear.php';">
                Manage New Academic Year
            </button>

            <!-- Show 'Manage Admin' button ONLY if $role is 'IT_Admin' -->
            <?php if ($role === 'IT_Admin'): ?>
                <button 
                    class="btn btn-secondary w-100 mb-2" 
                    onclick="window.location.href='manageAdmins.php';">
                    Manage Admins
                </button>
            <?php endif; ?>

            <button class="btn btn-secondary w-100 mb-2" 
                onclick="setAdminReferrer();">
                Excellence in Teaching Awards by Year
            </button>

            <script>
                function setAdminReferrer() {
                    // Store referrer in session via an AJAX request
                    fetch("storeReferrer.php", { method: "POST" }).then(() => {
                        window.location.href = "viewWinners.php";
                    });
                }
            </script>

            <button 
                class="btn btn-secondary w-100 mb-2" 
                onclick="window.location.href='configuration.php';">
                Configuration Page
            </button>
            <a href="reportPage.php" style="text-decoration: none;">
                <button class="btn btn-secondary w-100">Get Reports</button>
            </a>

            <a href="mailPage.php" style="text-decoration: none;">
                <button class="btn btn-secondary w-100">Mails</button>
            </a>

            
        </div>

        <div class="image-section">
            <img src="additionalImages/sabanciFoto1.jpg" alt="Sabancı Üniversitesi">
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
