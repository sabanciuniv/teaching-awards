<?php
// Set error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Output as plain text for easy reading
header('Content-Type: text/plain');

echo "PHPMailer Test Script\n";
echo "====================\n\n";

// Check PHP version
echo "PHP Version: " . phpversion() . "\n\n";

// Check if PHPMailer files exist - update to include your project's structure
$paths = [
    './PHPMailer/src/',       // Updated to include src subfolder
    '../PHPMailer/src/',      // Updated to include src subfolder
    './vendor/phpmailer/phpmailer/src/'
];

$files_to_check = [
    'PHPMailer.php',
    'SMTP.php',
    'Exception.php'
];

$found_files = false;

foreach ($paths as $path) {
    echo "Checking path: " . $path . "\n";
    $all_files_exist = true;
    
    foreach ($files_to_check as $file) {
        $full_path = $path . $file;
        if (file_exists($full_path)) {
            echo "  ✓ Found: " . $full_path . "\n";
        } else {
            echo "  ✗ Missing: " . $full_path . "\n";
            $all_files_exist = false;
        }
    }
    
    if ($all_files_exist) {
        $found_files = true;
        echo "All required files found in: " . $path . "\n\n";
        
        // Try to include the files
        try {
            require_once $path . 'PHPMailer.php';
            require_once $path . 'SMTP.php';
            require_once $path . 'Exception.php';
            echo "Successfully included PHPMailer files\n";
            
            // Check if we can create a PHPMailer instance
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                echo "PHPMailer class exists, creating instance...\n";
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                echo "PHPMailer instance created successfully\n";
                
                // Get version
                echo "PHPMailer Version: " . PHPMailer\PHPMailer\PHPMailer::VERSION . "\n";
                
                // Set up for a test
                echo "\nSetting up test configuration...\n";
                $mail->isSMTP();
                echo "- Set to use SMTP\n";
                $mail->Host = 'localhost';
                echo "- Host: localhost\n";
                $mail->Port = 1025;
                echo "- Port: 1025\n";
                $mail->SMTPAuth = false;
                echo "- Authentication: Disabled\n";
                
                // Don't actually send, just validate
                echo "\nValidating setup...\n";
                if ($mail->validateAddress('test@example.com')) {
                    echo "- Email validation successful\n";
                } else {
                    echo "- Email validation failed\n";
                }
                
                echo "\nConfiguration test complete. If you reached this point without errors, basic setup is working.\n";
                echo "To test actual sending, you'll need to configure real SMTP settings.\n";
                
            } else {
                echo "Error: PHPMailer class not found\n";
            }
        } catch (Exception $e) {
            echo "Error including files: " . $e->getMessage() . "\n";
        }
        
        break;
    } else {
        echo "Not all files found in this path\n\n";
    }
}

if (!$found_files) {
    echo "\nCould not find all required PHPMailer files.\n";
    echo "Please make sure PHPMailer is installed correctly.\n";
    echo "You can install it using Composer: composer require phpmailer/phpmailer\n";
}

// Check if mail() function is available
echo "\nChecking PHP mail configuration:\n";
if (function_exists('mail')) {
    echo "- PHP mail() function is available\n";
    
    // Check php.ini settings
    echo "- sendmail_path: " . ini_get('sendmail_path') . "\n";
    echo "- SMTP: " . ini_get('SMTP') . "\n";
    echo "- smtp_port: " . ini_get('smtp_port') . "\n";
} else {
    echo "- PHP mail() function is NOT available\n";
}

echo "\nTest completed.\n";
?>