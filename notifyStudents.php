<?php
// Start output buffering IMMEDIATELY
ob_start();

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Strict error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Prevent displaying errors to browser
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/notifyStudents_error.log');

// Ensure no output before headers
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

function sendErrorResponse($message, $code = 400) {
    // Clear any previous output
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    http_response_code($code);
    
    // Log detailed error
    error_log("Notification Error [{$code}]: {$message}");
    
    // Ensure clean JSON output
    echo json_encode([
        'error' => $message,
        'code' => $code
    ]);
    
    exit;
}

// Catch any fatal errors
function handleFatalError() {
    $error = error_get_last();
    if ($error !== null) {
        $errno = $error['type'];
        $errfile = $error['file'];
        $errline = $error['line'];
        $errstr = $error['message'];
        
        sendErrorResponse("Fatal Error: [{$errno}] {$errstr} in {$errfile} on line {$errline}", 500);
    }
}
register_shutdown_function('handleFatalError');

try {
    // Validate input early
    $rawInput = file_get_contents("php://input");
    if ($rawInput === false) {
        sendErrorResponse('Unable to read input stream');
    }

    $data = json_decode($rawInput, true);
    if ($data === null) {
        sendErrorResponse('Invalid JSON input: ' . json_last_error_msg());
    }

    // Validate required input
    if (!isset($data['students']) || !isset($data['category'])) {
        sendErrorResponse('Missing student data or category');
    }

    $category = htmlspecialchars($data['category']);
    $students = $data['students'];
    $sentCount = 0;
    $failed = [];

    // SMTP Configuration (consider moving to a config file)
    $smtpConfig = [
        'host'     => 'smtp.gmail.com',
        'port'     => 587,
        'username' => 'ens492odul@gmail.com',
        'password' => 'aycmatyxmxhphsvh',
        'from'     => 'ens492odul@gmail.com',
        'fromName' => 'Sabanci Teaching Awards System'
    ];

    // Process each student
    foreach ($students as $student) {
        // Strict email validation
        if (empty($student['Mail']) || !filter_var($student['Mail'], FILTER_VALIDATE_EMAIL)) {
            $failed[] = [
                'email' => $student['Mail'] ?? 'unknown',
                'reason' => 'Invalid email address'
            ];
            continue;
        }

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host       = $smtpConfig['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpConfig['username'];
            $mail->Password   = $smtpConfig['password'];
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpConfig['port'];

            // Email details
            $mail->setFrom($smtpConfig['from'], $smtpConfig['fromName']);
            $mail->addAddress($student['Mail'], $student['StudentFullName']);
            $mail->addReplyTo('teaching-awards@sabanciuniv.edu', 'Teaching Awards Support');

            $mail->isHTML(true);
            $mail->Subject = 'Reminder: Your Vote is Important!';

            // Sanitize student name
            $studentName = htmlspecialchars($student['StudentFullName']);

            $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif;'>
                <p>Dear {$studentName},</p>
                <p>We noticed that you haven't yet cast your vote in the <strong>" . htmlspecialchars($category) . "</strong> category.</p>
                <p>Your vote is important to help ensure fair results.</p>
                <p>Please log in to the voting system and vote before the deadline.</p>
                <p style='font-size: 12px; color: #888;'>This is an automated message. If you already voted, please disregard this.</p>
            </body>
            </html>";

            $mail->AltBody = "Dear {$studentName},\n\n"
                . "We noticed that you haven't yet cast your vote in the '{$category}' category.\n"
                . "Your vote is important. Please log in and vote before the deadline.\n\n"
                . "If you have already voted, please disregard this message.";

            // Send email
            if (!$mail->send()) {
                throw new \PHPMailer\PHPMailer\Exception($mail->ErrorInfo);
            }
            $sentCount++;

        } catch (\PHPMailer\PHPMailer\Exception $e) {
            // Log specific email sending errors
            error_log("Email send failed for {$student['Mail']}: " . $e->getMessage());
            $failed[] = [
                'email' => $student['Mail'],
                'reason' => $e->getMessage()
            ];
        }

        // Prevent overwhelming the SMTP server
        //usleep(100000);
    }

    // Prepare and send JSON response
    $response = [
        'sent' => $sentCount,
        'total' => count($students),
        'failed' => $failed
    ];

    // Clear output buffer just in case
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;



} catch (Exception $e) {
    sendErrorResponse('Unexpected error: ' . $e->getMessage(), 500);
} finally {
    // Ensure all buffers are cleared
    while (ob_get_level()) {
        ob_end_clean();
    }
}
?>