<?php
// Include the phpCAS library
require './phpCAS/source/CAS.php';

// CAS Server Configuration
$cas_host = 'login.sabanciuniv.edu';
$cas_context = '/cas';
$cas_port = 443;
$app_base_url = 'http://localhost/ENS491-492';


// Initialize phpCAS client
phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context, $app_base_url);

// Start the session
session_start();

// Destroy the local session
session_unset(); // Unset all session variables
session_destroy(); // Destroy the session

// Log out from CAS and redirect to login page
phpCAS::logoutWithRedirectService($app_base_url . "/index.php");
exit();
?>
