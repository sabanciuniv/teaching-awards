<?php
session_start();

if (isset($_SESSION['impersonating']) && $_SESSION['impersonating']) {
    // Restore original admin identity
    $_SESSION['user'] = $_SESSION['admin_user'];
    $_SESSION['role'] = $_SESSION['admin_role'];
    $_SESSION['firstname'] = $_SESSION['admin_firstname'] ?? '';
    $_SESSION['lastname'] = $_SESSION['admin_lastname'] ?? '';
    
    // Clean up impersonation variables
    unset($_SESSION['admin_user']);
    unset($_SESSION['admin_role']);
    unset($_SESSION['admin_firstname']);
    unset($_SESSION['admin_lastname']);
    unset($_SESSION['impersonating']);
    unset($_SESSION['impersonated_full_name']);
    unset($_SESSION['student_id']);
    
    // Force fresh load of session
    header("Cache-Control: no-cache, no-store, must-revalidate");
    header("Location: adminDashboard.php"); // Redirect back to admin dashboard
    exit;
} else {
    // Not impersonating, go to index
    header("Location: index.php");
    exit;
}
?>