<?php
// Start the session (optional if login tracking is required)
session_start();

// Redirect to another page if the user is already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teaching Awards</title>

    <!-- Limitless Theme CSS -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/core.min.css">
    <link rel="stylesheet" href="assets/css/components.min.css">
    <link rel="stylesheet" href="assets/css/colors.min.css">

    <!-- Custom CSS for page -->
    <style>
        body {
            background-color: #f9f9f9;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .login-container {
            text-align: center;
            margin-top: 50px;
        }

        .login-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .login-subtitle {
            font-size: 1.5rem;
            margin-bottom: 30px;
            color: #555;
        }

        .rules-button {
            background-color: #dde8ff;
            color: #333;
            font-size: 1.2rem;
            font-weight: bold;
            padding: 10px 30px;
            border-radius: 8px;
            margin: 15px 0;
            border: none;
            cursor: pointer;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .rules-button:hover {
            background-color: #c2d4ff;
        }

        .action-buttons .btn {
            margin: 10px;
            font-size: 1rem;
            font-weight: bold;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-nominate {
            background-color: #ffcccc;
            color: #333;
        }

        .btn-nominate:hover {
            background-color: #ff9999;
        }

        .btn-login {
            background-color: #f4b4b4;
            color: #333;
        }

        .btn-login:hover {
            background-color: #e49a9a;
        }

        .footer-text {
            font-size: 0.9rem;
            margin-top: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <!-- Page Content -->
    <div class="page-content">
        <div class="content-wrapper">
            <div class="login-container">
                <!-- Header Section -->
                <img src="assets/images/screenshots/sabancilogo.png" alt="Logo" style="height: 60px; margin-bottom: 20px;">
                <h1 class="login-title">VOTE & CHOOSE YOUR FAVORITE</h1>
                <p class="login-subtitle">Click to View the Rules</p>

                <!-- Rules Button -->
                <button class="rules-button">⬇️ View the Rules ⬇️</button>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="nominate.php" class="btn btn-nominate">Nominate</a>
                    <a href="voteScreen.php" class="btn btn-login">Login</a>
                </div>

                <!-- Footer Text -->
                <p class="footer-text">YOU CAN VOTE BETWEEN start_date - end_date</p>
            </div>
        </div>
    </div>

    <!-- Limitless Theme JS -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
