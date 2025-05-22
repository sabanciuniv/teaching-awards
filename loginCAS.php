<?php
// -------------------------
// Include phpCAS and config
// -------------------------

// Include phpCAS
require './phpCAS/source/CAS.php';
require_once 'api/commonFunc.php';
require_once 'database/dbConnection.php';

prep_session();

// Include configuration
$config = include 'config.php';

// Configure phpCAS client using config values
$cas_host      = $config['cas_host'];
$cas_context   = $config['cas_context'];
$cas_port      = $config['cas_port'];
$app_base_url  = $config['app_base_url'];

phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context, $app_base_url);

// Disable server validation (for testing only)
phpCAS::setNoCasServerValidation();

// Force CAS authentication
phpCAS::forceAuthentication();

// Retrieve the authenticated user's ID
$user = phpCAS::getUser();
$_SESSION['user'] = $user;


// -------------------------
// BEGIN: Fetch name and surname from database
// -----
// 
try {
    // Try fetching from Student_Table
    $stmtStudent = $pdo->prepare("SELECT StudentFullName FROM Student_Table WHERE SuNET_Username = :username LIMIT 1");
    $stmtStudent->execute([':username' => $user]);
    $rowStudent = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    if ($rowStudent) {
        $_SESSION['full_name'] = $rowStudent['StudentFullName'];
    } else {
        // Try Admin_Table
        $stmtAdmin = $pdo->prepare("SELECT Name FROM Admin_Table WHERE AdminSuUsername = :username LIMIT 1");
        $stmtAdmin->execute([':username' => $user]);
        $rowAdmin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        if ($rowAdmin) {
            $_SESSION['full_name'] = $rowAdmin['Name'];
        } else {
            // For candidates, just use the username from CAS ---> they dont have usernames in database
            $_SESSION['full_name'] = $user;
        }
    }
} catch (PDOException $e) {
    $_SESSION['full_name'] = $user; // fallback to username if there's an error
    error_log("Failed to fetch full name: " . $e->getMessage());
}



// -----------------------
// END:name and surname from database
// -----------------------



// -------------------------
// BEGIN: Admin Access Check
// -------------------------

// If the user tries to go to adminDashboard.php, ensure they exist in Admin_Table
// AND checkRole is not 'Removed'.

if (isset($_GET['redirect']) && $_GET['redirect'] === 'adminDashboard.php') {
    if (!checkIfUserIsAdmin($pdo, $user)) {
        header("Location: accessDenied.php");
        exit;
    }

    // Store the admin role (if needed)
    $role = getUserAdminRole($pdo, $user);
    $_SESSION['role'] = $role ?? 'Unknown';
}

// -----------------------
// END: Admin Access Check
// -----------------------


// Handle redirection after authentication
if (isset($_GET['redirect'])) {
    $redirect_url = $_GET['redirect'];
    // Validate and sanitize the redirect URL
    $allowed_pages = ['nominate.php', 'voteCategory.php', 'adminDashboard.php'];
    if (in_array($redirect_url, $allowed_pages)) {
        header("Location: $redirect_url");
        exit;
    }
}
// Default action if no redirect parameter is provided
echo "Authentication successful for user: " . htmlspecialchars($user);
?>
