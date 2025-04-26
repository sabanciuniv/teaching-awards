<?php
// votingClosed.php

require_once __DIR__ . '/database/dbConnection.php';
require_once __DIR__ . '/api/commonFunc.php';
init_session();

// Fetch the current academic year record (includes Start_date_time and End_date_time)
$currentAY = fetchCurrentAcademicYear($pdo);
$start = $currentAY
  ? (new DateTime($currentAY['Start_date_time']))->format('j F Y H:i')
  : null;
$end   = $currentAY
  ? (new DateTime($currentAY['End_date_time']))->format('j F Y H:i')
  : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Voting Closed</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & theme CSS -->
  <link href="assets/css/bootstrap.min.css"           rel="stylesheet">
  <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet">
  <link href="assets/css/components.min.css"          rel="stylesheet">
  <link href="assets/css/layout.min.css"              rel="stylesheet">
  <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">
  <!-- Font Awesome (optional) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    html, body {
      height: 100%;
      margin: 0;
    }
    body {
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #f9f9f9;
      padding: 0 20px;
    }
    .message-box {
      background: #fff;
      padding: 50px;
      border-radius: 8px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
      max-width: 600px;
      width: 100%;
      text-align: center;
    }
    .message-box h1 {
      font-size: 3rem;
      margin-bottom: 1rem;
      color: #333;
    }
    .message-box p {
      font-size: 1.2rem;
      margin-bottom: 2rem;
      color: #555;
    }
    .dates {
      margin-bottom: 2rem;
      font-size: 1rem;
      color: #666;
    }
    .btn-custom {
      background-color: #45748a !important;
      color: #fff !important;
      border: none !important;
      padding: 15px 40px !important;
      font-size: 1.1rem;
      border-radius: 5px !important;
      transition: background-color 0.3s ease;
    }
    .btn-custom:hover {
      background-color: #365a6b !important;
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="message-box">
    <h1>Voting Is Currently Closed</h1>
    <p class="dates">
      <?php if ($start && $end): ?>
        Voting is open from <strong><?= $start ?></strong><br>
        until <strong><?= $end ?></strong>.
      <?php else: ?>
        Voting dates are not available at this time.
      <?php endif ?>
    </p>
    <a href="index.php" class="btn btn-custom">
      <i class="fa fa-home"></i> Return to Main Page
    </a>
  </div>

  <!-- JS (if needed) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
