<?php
// Start the session (optional if login tracking is required)
require './phpCAS/source/CAS.php';

// Configure phpCAS client
$cas_host = 'login.sabanciuniv.edu'; // Replace with your university's CAS server
$cas_context = '/cas';            // Replace with your CAS context
$cas_port = 443;                  // Use 443 for HTTPS or 80 for HTTP
$app_base_url = 'http://apps-local.sabanciuniv.edu'; 

phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context, $app_base_url);

// Optional: Disable server validation (for testing only)
phpCAS::setNoCasServerValidation();

// Force CAS authentication
phpCAS::forceAuthentication();

// Retrieve the authenticated user's ID
$user = phpCAS::getUser();

// Example: Store user in a session
session_start();
$_SESSION['user'] = $user;
//session_start();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teaching Awards</title>

	<!-- Global stylesheets -->
	<link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
	<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
	<link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
	<!-- /global stylesheets -->


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
                <button class="rules-button" data-toggle="modal" data-target="#rulesModal">⬇ View the Rules ⬇</button>

                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="nominate.php" class="btn btn-indigo">Nominate</a>
                    <a href="voteCategory.php" class="btn btn-indigo">Vote Page</a>
                </div>

                <!-- Footer Text -->
                <p class="footer-text">YOU CAN VOTE BETWEEN start_date - end_date</p>
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
</body>
</html>