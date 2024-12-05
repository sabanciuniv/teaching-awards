<?php // Start the session
require './phpCAS/source/CAS.php';

// Configure phpCAS client
$cas_host = 'login.sabanciuniv.edu';
$cas_context = '/cas';          
$cas_port = 443;                 
$app_base_url = 'http://apps-local.sabanciuniv.edu'; 

phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context, $app_base_url);

//Disable server validation (for testing only)
phpCAS::setNoCasServerValidation();

// Force CAS authentication
phpCAS::forceAuthentication();

// Retrieve the authenticated user's ID
$user = phpCAS::getUser();

session_start();
$_SESSION['user'] = $user;

// Handle redirection after authentication
if (isset($_GET['redirect'])) {
    $redirect_url = $_GET['redirect'];
    // Validate and sanitize the redirect URL
    $allowed_pages = ['nominate.php', 'voteCategory.php'];
    if (in_array($redirect_url, $allowed_pages)) {
        header("Location: $redirect_url");
        exit;
    }
}

// Default action if no redirect parameter is provided
echo "Authentication successful for user: " . htmlspecialchars($user);

?>