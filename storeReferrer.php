<?php
    session_start();
    $_SESSION['previous_page'] = 'adminDashboard.php';
    http_response_code(200);
    exit();
?>
