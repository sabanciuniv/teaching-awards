<?php
require_once __DIR__ . '/database/dbConnection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Include the required files
// Include the required files - update these paths
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

// Then create a new instance
$mail = new PHPMailer(true); // true enables exceptions

// Set headers for JSON response
header('Content-Type: application/json');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Get posted JSON data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['students']) || !isset($data['category'])) {
    echo json_encode(['error' => 'Missing student data or category']);
    exit;
}

$category = htmlspecialchars($data['category']);
$students = $data['students'];

// Log for debugging
error_log("Attempting to notify " . count($students) . " students for category: " . $category);

$sentCount = 0;
$failed = [];

// Email configuration - MODIFY THESE WITH YOUR ACTUAL SMTP SETTINGS
$smtpHost = 'smtp.your-university.edu';  // Your SMTP server
$smtpPort = 587;                        // Common ports: 587 (TLS), 465 (SSL)
$smtpUsername = 'your-email@your-university.edu';
$smtpPassword = 'your-smtp-password';
$fromEmail = 'voting-system@your-university.edu';
$fromName = 'University Voting System';

try {
    // For each student in the list
    foreach ($students as $student) {
        // Skip if email is missing or invalid
        if (empty($student['Mail']) || !filter_var($student['Mail'], FILTER_VALIDATE_EMAIL)) {
            $failed[] = [
                'email' => $student['Mail'] ?? 'unknown',
                'reason' => 'Invalid email address'
            ];
            continue;
        }

        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUsername;
            $mail->Password = $smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $smtpPort;
            
            // Uncomment for debugging SMTP issues
            // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            
            // Recipients
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($student['Mail'], $student['StudentFullName']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Reminder: Your Vote is Important!';
            
            // Create a more engaging email body
            $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; }
                    .container { padding: 20px; }
                    .header { color: #003366; font-size: 20px; font-weight: bold; }
                    .content { margin: 15px 0; line-height: 1.5; }
                    .footer { font-size: 12px; color: #666; margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>Dear {$student['StudentFullName']},</div>
                    <div class='content'>
                        <p>We notice that you haven't yet cast your vote in the <strong>{$category}</strong> category.</p>
                        <p>Your vote is important and helps ensure that the election results accurately represent the student body's preferences.</p>
                        <p>Please log in to the voting system and cast your vote as soon as possible. The voting period will end soon.</p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message from the University Voting System. Please do not reply to this email.</p>
                        <p>If you have already voted, please disregard this message.</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Plain text alternative for email clients that don't support HTML
            $textBody = "Dear {$student['StudentFullName']},\n\n";
            $textBody .= "We notice that you haven't yet cast your vote in the '{$category}' category.\n\n";
            $textBody .= "Your vote is important and helps ensure that the election results accurately represent the student body's preferences.\n\n";
            $textBody .= "Please log in to the voting system and cast your vote as soon as possible. The voting period will end soon.\n\n";
            $textBody .= "This is an automated message from the University Voting System. Please do not reply to this email.\n";
            $textBody .= "If you have already voted, please disregard this message.";
            
            $mail->Body = $emailBody;
            $mail->AltBody = $textBody;
            
            // Log attempt
            error_log("Attempting to send email to: " . $student['Mail']);
            
            // Send the email
            $mail->send();
            $sentCount++;
            
            // Optional: Log success
            error_log("Email sent successfully to: " . $student['Mail']);
            
            // Optional: Add a small delay to prevent overwhelming the SMTP server
            usleep(100000); // 100ms
            
        } catch (Exception $e) {
            // Log the error and continue with the next student
            error_log("Failed to send to {$student['Mail']}: " . $mail->ErrorInfo);
            $failed[] = [
                'email' => $student['Mail'],
                'reason' => $mail->ErrorInfo
            ];
        }
    }
    
    // Return results
    echo json_encode([
        'sent' => $sentCount,
        'total' => count($students),
        'failed' => $failed
    ]);
    
} catch (Exception $e) {
    // Handle any other exceptions
    error_log("General error in notification process: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'sent' => $sentCount,
        'failed' => $failed
    ]);
}