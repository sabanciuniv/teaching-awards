<?php
session_start();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teaching Awards</title>

    <link href="assets/fonts/inter/inter.css" rel="stylesheet" type="text/css">
	<link href="assets/icons/phosphor/styles.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/ltr/all.min.css" id="stylesheet" rel="stylesheet" type="text/css">

    <!-- Limitless Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">



    <!-- Custom CSS for page -->
    <style>
        
        body {
            background-color: #f9f9f9;
            margin: 0;
            height: 100%;
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
        }
        
          /* Navbar */
        /* Logo and title in the navbar */
        .navbar-brand span {
            font-size: 1rem;
            font-weight: bold;
            color: white !important;
            margin-left: 50px;
        }

        .hero-section {
            height: 100vh;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            background-image: url('assets/images/sabancı\ university.png'); 
            border-radius: 40px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.75); /* Adjust opacity as needed */
            z-index: 1;
        }

        .content-wrapper {
            position: relative;
            z-index: 2;
            text-align: center;
            padding: 20px;
            color: #333;
        }
        .login-title {
            font-size: 4.5rem; /* Extremely large font size */
            font-weight: 800; /* Super thick and bold */
            color: #004f9e; /* Dark blue color */
            text-transform: uppercase; /* Uppercase for emphasis */
            line-height: 1; /* Tight spacing between lines */
            text-align: left; /* Left-aligned */
            margin-top: 5px; /* Increased margin for spacing */
            word-spacing: 40px; /* Wider spacing between words */
            letter-spacing: 5px; /* Slight spacing between letters for better clarity */
        }

        .login-container {
            text-align: center;
            margin-top: 80px;
        }

        .login-subtitle {
            font-size: 1.5rem;
            margin-bottom: 5px;
            font-weight: 1000;
            color:rgb(54, 56, 70);
        }

        .rules-button {
            font-size: 1.2rem;
            font-weight: bold;
            padding: 10px 42px;
            border-radius: 8px;
            margin: 10px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
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
            font-size: 1rem;
            margin-top: 20px;
            color: #666;
            color:rgb(54, 56, 70);
            font-weight: bold;
        }

    </style>
</head>

    <!-- Page Content -->
     <div class= "page content">
        <div class="hero-section">
            <div class="hero-overlay"></div>
                <div class="content-wrapper">
                    <!-- Navbar -->
                    <nav class="navbar navbar-dark navbar-expand-lg fixed-top bg-secondary">
                            <div class="container-fluid">
                                <div class="d-flex align-items-center">
                                </div>
                                <!-- Toggler for Mobile -->
                                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                                    <span class="navbar-toggler-icon"></span>
                                </button>

                                <!-- Navbar Links -->
                                <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                                    <ul class="navbar-nav align-items-center">
                                        <!-- Welcome Dropdown -->
                                        <li class="nav-item dropdown">
                                            <a href="#" class="nav-link dropdown-toggle text-white" id="welcomeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <strong>TEMP dropdown</strong>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="welcomeDropdown">
                                                <li>
                                                    <a class="dropdown-item" href="loginCAS.php?redirect=adminDashboard.php">Admin Login </a>
                                                </li>
                                            </ul>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </nav>

                    <div class="login-container">
                        <!-- Header Section -->
                        <img src="assets/images/screenshots/sabancilogo.png" alt="Logo" style="height: 60px; margin-bottom: 20px;">
                        <h1 class="login-title">
                            VOTE & <br>
                            CHOOSE YOUR <br>
                            FAVORITE
                        </h1>

                        <p class="login-subtitle">Click Below to View the Rules</p>

                        <!-- Rules Button -->
                        <button class="rules-button btn-secondary text-white" data-toggle="modal" data-target="#rulesModal">⬇ View the Rules ⬇</button>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <a href="loginCAS.php?redirect=nominate.php" class="btn btn-secondary">Nominate</a>
                            <a href="loginCAS.php?redirect=voteCategory.php" class="btn btn-secondary">Vote Page</a>
                            <!-- <a href="viewWinners.php" class="btn btn-indigo">View Winners</a> -->
                        </div>

                        <div class="action-buttons" style="text-align: center;">
                            <a href="viewWinners.php" class="btn btn-secondary" style="width: 260px;">View Winners</a>
                        </div>

                        <!-- Footer Text -->
                        <p class="footer-text">YOU CAN VOTE BETWEEN start_date - end_date</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Rules -->_
    <div class="modal fade" id="rulesModal" tabindex="-1" role="dialog" aria-labelledby="rulesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rulesModalLabel">Rules</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                <p>To vote, you must enter your e-mail Username and password. Students are authorized to vote in their category according to their class. You have the right to enter the application and <strong>VOTE ONLY ONCE.</strong></p>

<p>Once logged in securely, you have <strong>30 minutes to complete your voting</strong> using the drop down menus provided. You can rank your choices in the order of 1, 2, and 3, where 1 represents your top choice. You may vote for 1, 2, or 3 candidates, but you cannot vote for more than 3 candidates or assign the same rank to multiple candidates.</p>

<p>The points are distributed as follows:</p>
<ul>
    <li>If you vote for one person, they will receive 6 points.</li>
    <li>If you vote for two people, your first choice will receive 4 points, and your second choice will receive 2 points.</li>
    <li>If you vote for three people, your first choice will receive 3 points, your second choice will receive 2 points, and your third choice will receive 1 point.</li>
</ul>

<p>If the guidelines are not followed, you will see a warning message. Once you have finalized your selections, click "Submit" to save your vote and close the page.</p>

<p>As per the rules, candidates who placed first in a category during the last three years are not eligible to be nominated in that same category.</p>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Limitless Theme JS -->
    <script src="assets/js/main/jquery.min.js"></script>
    <script src="assets/js/main/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>