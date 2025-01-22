<?php
session_start();
require_once 'api/authMiddleware.php';
// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Include DB connection (adjust path if necessary)
require_once 'database/dbConnection.php';

// Fetch the username from session
$username = $_SESSION['user'];

// Default role (assume null if not found)
$role = null;

try {
    // Query the Admin_Table to fetch the user's Role
    $stmt = $pdo->prepare("SELECT Role FROM Admin_Table WHERE AdminSuUsername = :username");
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        // If found, set $role based on DB value (e.g., 'IT_Admin' or 'admin')
        $role = $row['Role']; 
    } else {
        // If no record in Admin_Table, you may optionally redirect or handle as unauthorized
        // header("Location: index.php"); 
        // exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabancı University Teaching Awards</title>
    <!-- Global stylesheets -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">
    <!-- /global stylesheets -->

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

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

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
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

        .navbar-brand img {
            height: 40px;
        }

        .navbar-brand span {
            font-size: 1.25rem;
            font-weight: bold;
            color: white !important;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar.php'; ?>
     <!--
    <nav class="navbar navbar-dark navbar-expand-lg fixed-top bg-secondary">
        <div class="container-fluid d-flex align-items-center position-relative">
            <div class="d-flex align-items-center"> -->
                <!-- Back Arrow -->
                <!--<a href="index.php" class="text-white" style="text-decoration:none; font-size:1.2rem; margin-right: 20px;">
                    <i class="fas fa-arrow-left me-3"></i>
                </a>-->
                <!-- Logo and Title -->
                <!--<a href="nominate.php" class="navbar-brand d-flex align-items-center ms-5">
                    <img src="https://yabangee.com/wp-content/uploads/sabancı-university-2.jpg" alt="Logo" style="height: 40px;">
                </a>
            </div>-->
            <!-- Centered Title -->
            <!--<div class="navbar-title position-absolute" style="left: 50%; transform: translateX(-50%); font-size: 1.5rem; font-weight: bold; color: white;">
                Teaching Awards
            </div>-->
            <!-- Toggler for Mobile -->
            <!--<button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarNav" aria-controls="navbarNav"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>-->
            <!-- Navbar Links -->
            <!--<div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">-->
                    <!-- Welcome Dropdown -->
                    <!--<li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle text-white" id="welcomeDropdown"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="welcomeDropdown">
                            <li>
                                <a class="dropdown-item" href="index.php">
                                    <i class="fas fa-home me-2"></i> Home
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-question-circle me-2"></i> Help
                                </a>
                            </li>
                            <div class="dropdown-divider"></div>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav> -->

    <div class="container">
        <div class="menu">
            <button 
                class="btn btn-secondary w-100 mb-2" 
                onclick="alert('Create New Academic Year functionality not implemented yet.')">
                + Manage New Academic Year
            </button>

            <!-- Show 'Manage Admin' button ONLY if $role is 'IT_Admin' -->
            <?php if ($role === 'IT_Admin'): ?>
                <button 
                    class="btn btn-secondary w-100 mb-2" 
                    onclick="alert('Manage Admin functionality not implemented yet.')">
                    Manage Admin
                </button>
                <div class="note">(only for IT admins)</div>
            <?php endif; ?>

            <button 
                class="btn btn-secondary w-100 mb-2" 
                onclick="alert('Excellence in Teaching Awards by Year functionality not implemented yet.')">
                Excellence in Teaching Awards by Year
            </button>
            <button 
                class="btn btn-secondary w-100 mb-2" 
                onclick="alert('Sync instructor-course functionality not implemented yet.')">
                Sync instructor-course
            </button>
            <a href="reportPage.php" style="text-decoration: none;">
                <button class="btn btn-secondary w-100">Get Reports</button>
            </a>
        </div>

        <div class="image-section">
            <img src="additionalImages/sabanciFoto1.jpg" alt="Sabancı Üniversitesi">
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
