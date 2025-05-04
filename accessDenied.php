<?php
// Optional: Start session if needed
session_start();
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <title>Access Denied - Sabancı University</title>

    <!-- Bootstrap CSS & FontAwesome -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/components.min.css" rel="stylesheet">
    <link href="assets/css/layout.min.css" rel="stylesheet">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/phosphor-icons@1.4.2/src/css/icons.min.css" rel="stylesheet">

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
</head>
<body>

<?php include 'navbar.php'; ?>

<!-- MAIN CONTENT -->
<div class="denied-box">
    <h1>Access Denied</h1>
    <p>You do not have permission to access this page.</p>
    <a href="index.php" class="btn btn-secondary">Return to Homepage</a>
</div>

</body>
</html>
