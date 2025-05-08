<?php
// Start session to maintain user data
session_start();

// Set page title for header
$pageTitle = "Access Denied - Sabancı University";
require_once 'api/header.php';
?>

<style>
    body {
        background-color: #f8f9fa;
        margin: 0;
        padding-top: 80px; /* Leave space for fixed navbar */
        font-family: Arial, sans-serif;
    }

    .denied-box {
        background-color: white;
        padding: 40px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        max-width: 500px;
        margin: 50px auto;

        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .denied-box p {
        font-size: 16px;
        margin-top: 10px;
    }

    .denied-box a.btn {
        margin-top: 20px;
        color: white;
    }

    .denied-box a.btn:hover {
        background-color: #0d1f45;
    }
</style>

<body>
    <?php include 'navbar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="denied-box">
        <h1>Access Denied</h1>
        <p>You do not have permission to access this page.</p>
        <a href="index.php" class="btn btn-secondary">Return to Homepage</a>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>