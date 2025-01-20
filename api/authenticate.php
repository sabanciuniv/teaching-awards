<?php
$config = include(__DIR__ . '/../config.php');
$validToken = $config['api_token'];

// Get the token from the request (either GET or POST)
$providedToken = $_GET['token'] ?? $_POST['token'] ?? '';

// Function to check authentication
function checkAuth($providedToken, $validToken) {
    if ($providedToken !== $validToken) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unauthorized access'
        ]);
        http_response_code(403); // Forbidden
        exit;
    }
}
?>
