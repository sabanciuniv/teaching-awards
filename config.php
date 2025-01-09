<?php
// Auto-detect base URL for internal app usage
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$script_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$app_base_url = $protocol . $host . $script_path;

// Manually set the CAS service URL if needed
$cas_service_url = getenv('CAS_SERVICE_URL') ?: $app_base_url; // Use environment variable or auto-detect

return [
    'app_base_url' => $app_base_url,  // Dynamic base URL for app
    'cas_service_url' => $cas_service_url,  // CAS service URL (can be overridden)
    'cas_host' => 'login.sabanciuniv.edu',  // CAS server host
    'cas_context' => '/cas',  // CAS context
    'cas_port' => 443,  // CAS server port
    'allowed_pages' => [
        'nominate.php',
        'voteCategory.php',
        'adminDashboard.php'
    ]
];
