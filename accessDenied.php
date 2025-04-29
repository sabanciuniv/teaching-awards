<?php
// Optional: Start session if needed
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Access Denied</title>
    <link rel="stylesheet" href="custom.css"> <!-- Optional: Your own custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            font-family: Arial, sans-serif;
        }

        .denied-box {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .denied-box h1 {
            font-size: 24px;
            color: #dc3545;
        }

        .denied-box p {
            font-size: 16px;
            margin-top: 10px;
        }

        .denied-box a {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: white;
            background-color: #112568;
            padding: 10px 20px;
            border-radius: 5px;
        }

        .denied-box a:hover {
            background-color: #0d1f45;
        }
    </style>
</head>
<body>
    <div class="denied-box">
        <h1>Access Denied</h1>
        <p>You do not have permission to access this page.</p>
        <a href="index.php">Return to Homepage</a>
    </div>
</body>
</html>
