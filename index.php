<?php
session_start();
require_once 'api/commonFunc.php';
require_once 'database/dbConnection.php'; 
$pageTitle= "Teaching Awards";
require_once 'api/header.php';

$academicYear = fetchCurrentAcademicYear($pdo);

if (!$academicYear) {
    die('Academic year data not found.');
}

$yearID    = $academicYear['YearID'] ?? 'N/A';
$startDate = isset($academicYear['Start_date_time']) ? date('F j, Y', strtotime($academicYear['Start_date_time'])) : 'N/A';
$endDate   = isset($academicYear['End_date_time'])   ? date('F j, Y', strtotime($academicYear['End_date_time']))   : 'N/A';

?>


<!DOCTYPE html>
<html lang="en">
<link href="assets/fonts/inter/inter.css" rel="stylesheet" type="text/css">
<link href="assets/icons/phosphor/styles.min.css" rel="stylesheet" type="text/css">
<link href="assets/css/ltr/all.min.css" id="stylesheet" rel="stylesheet" type="text/css">

<style>
    body {
        background-color: #f9f9f9;
        margin: 0;
        height: 100%;
        display: flex;
        flex-direction: column;
        font-family: Arial, sans-serif;

    }
    .navbar .nav-item .nav-link {
        margin-right: -30px; 
    }
    
    .dropdown-menu {
        transform: translateX(-70px);
        background-color: #01236a; 
        border: none; 
    }


    .dropdown-menu .dropdown-item {
        color: white;
        background-color: #01236a; 
    }

    
    .dropdown-menu .dropdown-item:hover,
    .dropdown-menu .dropdown-item:focus {
        background-color: #01236a; 
        color: white; 
    }

    .hero-section {
        position: relative;
        height: 100vh;
        overflow: hidden;
        border-radius: 40px;
        margin-left: -20px;
        margin-right: -20px;
        margin-bottom: 0;  /* kill any default gap */
    }

    .bg-video {
        position: absolute;
  top: 0;
  left: 0;
  /* width:auto + min-width covers the full width without distortion */
  width: auto;
  min-width: 100%;
  height: 100%;      /* fill the hero‐section height */
  object-fit: cover; /* crop/scale so no letterboxes appear */
  z-index: 0;
    }

    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.65); 
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
        font-size: 4.5rem; 
        font-weight: 800; 
        color: #004f9e; 
        text-transform: uppercase; 
        line-height: 1; 
        text-align: left; 
        margin-top: 5px; 
        word-spacing: 40px; 
        letter-spacing: 5px; 
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

    .unified-buttons {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        margin-top: 30px;
    }

    .unified-button {
        width: 260px;
        font-size: 1.1rem;
        font-weight: bold;
        padding: 12px 0;
        border-radius: 8px;
        text-align: center;
    }


    .unified-button:hover {
        background-color: #7d97e0;
        color: white;
    }

</style>

    <!-- Page Content -->
     <div class= "page content">
        <div class="hero-section">
            <video autoplay muted loop playsinline class="bg-video">
                <source src="additionalImages/Öğretim Ödülleri Giriş Sayfası.mp4" type="video/mp4">
                Your browser does not support HTML5 video.
             </video>
            <div class="hero-overlay"></div>
                <div class="content-wrapper">
                    <!-- Navbar -->
                    <nav class="navbar navbar-dark navbar-expand-lg fixed-top" style="background-color: #112568;">
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
                                            <a href="#" 
                                            class="nav-link dropdown-toggle text-white" 
                                            id="welcomeDropdown" 
                                            role="button" 
                                            data-bs-toggle="dropdown" 
                                            aria-expanded="false">
                                                <i class="fas fa-list"></i> 
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a class="dropdown-item" href="loginCAS.php?redirect=adminDashboard.php">Admin Login</a>
                                                </li>
                                            </ul>
                                        </li>

                                    </ul>
                                </div>
                            </div>
                        </nav>

                    <div class="login-container">
                        <!-- Header Section -->
                        <h1 class="login-title">
                            VOTE & <br>
                            CHOOSE YOUR <br>
                            FAVORITE
                        </h1>

                        <!-- Unified Button Column -->
                        <div class="unified-buttons">
                            <a href="#" class="btn btn-secondary unified-button" data-toggle="modal" data-target="#rulesModal"> View the Rules </a>
                            <a href="loginCAS.php?redirect=nominate.php" class="btn btn-secondary unified-button">Nominate a TA</a>
                            <a href="loginCAS.php?redirect=voteCategory.php" class="btn btn-secondary unified-button">Vote   <i class="fas fa-vote-yea"></i></a>
                            <a href="viewWinners.php" class="btn btn-secondary unified-button">View Previous Winners</a>
                        </div>

                        <!-- Footer Text -->
                        <p class="footer-text">YOU CAN VOTE BETWEEN <?php echo $startDate; ?> - <?php echo $endDate; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Rules -->_
    <div class="modal fade" id="rulesModal" tabindex="-1" role="dialog" aria-labelledby="rulesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="rulesModalLabel"> <strong> General Rules </strong></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                <p>To vote, you must enter your e-mail Username and password. Students are authorized to vote in their category according to their class. You have the right to enter the application and <strong>VOTE ONLY ONCE.</strong></p>

<p>Once logged in securely, you have <strong>24 hours to complete your voting</strong> using the drop down menus provided. You can rank your choices in the order of 1, 2, and 3, where 1 represents your top choice. You may vote for 1, 2, or 3 candidates, but you cannot vote for more than 3 candidates or assign the same rank to multiple candidates.</p>

<p>The points are distributed as follows:</p>
<ul>
    <li>If you vote for one person, they will receive 6 points.</li>
    <li>If you vote for two people, your first choice will receive 4 points, and your second choice will receive 2 points.</li>
    <li>If you vote for three people, your first choice will receive 3 points, your second choice will receive 2 points, and your third choice will receive 1 point.</li>
</ul>

<p>If the guidelines are not followed, you will see a warning message. Once you have finalized your selections, click "Submit" to save your vote and close the page.</p>

<p>As per the rules, candidates who placed first in a category during the last three years are not eligible to be nominated in that same category.</p>

<hr>

        <!-- TA Award Rules Section -->
        <h6><strong>Rules for Teaching Assistant Awards</strong></h6>
        <p><strong>Purpose:</strong> The Teaching Assistant Award was created to acknowledge Teaching Assistants who excel in their activities.</p>

        <p><strong>Eligibility:</strong></p>
        <ul>
            <li>The nominee must be a current graduate student</li>
            <li>The nominee must be a TA in at least one course during the 2023–2024 academic year</li>
        </ul>

        <p><strong>Criteria:</strong></p>
        <ul>
            <li>a. Being nominated by more than one person</li>
            <li>b. Being nominated both by faculty and by students</li>
            <li>c. Course evaluation results</li>
            <li>d. Data/Feedback about their work in more than one course (from course instructors)</li>
            <li>e. Whether nomination letters have been provided individually if nominated by a group of students</li>
            <li>f. GPA of the nominee</li>
        </ul>

        <p><strong>Questions?</strong> For your questions about the nomination guidelines and the process, please contact Deniz İnan at <a href="mailto:deniz.inan@sabanciuniv.edu">deniz.inan@sabanciuniv.edu</a></p>

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