<?php
// Auto-detect base URL for internal app usage
$app_base_url = 'http://pro2-dev.sabanciuniv.edu/odul/';

// Manually set the CAS service URL if needed
$cas_service_url = 'http://pro2-dev.sabanciuniv.edu/odul/'; // Ensure CAS service aligns with base

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
    ],

    'database' => [  
        'host' => 'pro2-dev.sabanciuniv.edu',
        'dbname' => 'odul',
        'port' => 3306,
        'username' => 'odul',
        'password' => 'fQDq66ZP',
    ]
];
