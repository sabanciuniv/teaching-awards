<?php
// Start the session and perform authentication
session_start();
require_once 'api/authMiddleware.php';
// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
require_once __DIR__ . '/database/dbConnection.php';
$user = $_SESSION['user'];

// Start output buffering immediately
ob_start();

require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Strict error handling: log errors but do not display them
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/notifyStudents_error.log');

// Force JSON output and disable MIME sniffing
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

/**
 * Sends a JSON error response and exits.
 */
function sendErrorResponse($message, $code = 400) {
    while (ob_get_level()) { ob_end_clean(); }
    http_response_code($code);
    error_log("Notification Error [{$code}]: {$message}");
    echo json_encode(['error' => $message, 'code' => $code]);
    exit;
}

/**
 * Catches fatal errors and sends a JSON error response.
 */
function handleFatalError() {
    $error = error_get_last();
    if ($error !== null) {
        $errno   = $error['type'];
        $errfile = $error['file'];
        $errline = $error['line'];
        $errstr  = $error['message'];
        sendErrorResponse("Fatal Error: [{$errno}] {$errstr} in {$errfile} on line {$errline}", 500);
    }
}
register_shutdown_function('handleFatalError');

try {
    // Read input data (JSON or form-data)
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $rawInput = file_get_contents("php://input");
        $data = json_decode($rawInput, true);
        if ($data === null) {
            sendErrorResponse("Invalid JSON input");
        }
    } else {
        $data = $_POST;
        $data['students'] = isset($data['students']) ? json_decode($data['students'], true) : [];
    }

    // Ensure required input exists
    if (!isset($data['students']) || !isset($data['category'])) {
        sendErrorResponse('Missing student data or category');
    }

    // "category" is a numeric CategoryID (e.g., 7)
    $categoryID = (int)$data['category'];
    $students   = $data['students'];

    // 1) Fetch the mail template from MailTemplate_Table using CategoryID
    $stmtTemplate = $pdo->prepare("
        SELECT TemplateBody
          FROM MailTemplate_Table
         WHERE CategoryID = :catID
         LIMIT 1
    ");
    $stmtTemplate->execute([':catID' => $categoryID]);
    $rowTemplate = $stmtTemplate->fetch(PDO::FETCH_ASSOC);
    if (!$rowTemplate) {
        sendErrorResponse("No mail template found for CategoryID {$categoryID}", 404);
    }
    $templateBody = $rowTemplate['TemplateBody'];

    // 2) Configure SMTP settings
    $smtpConfig = [
        'host'     => 'smtp.gmail.com',
        'port'     => 587,
        'username' => 'ens492odul@gmail.com',
        'password' => 'aycmatyxmxhphsvh',
        'from'     => 'ens492odul@gmail.com',
        'fromName' => 'Sabanci Teaching Awards System'
    ];

    // Use the session user as sender
    $sender = $user;

    $sentCount = 0;
    $failed = [];

    // 3) Loop over each student, send email, and log the send
    foreach ($students as $student) {
        // Validate student's email address
        if (empty($student['Mail']) || !filter_var($student['Mail'], FILTER_VALIDATE_EMAIL)) {
            $failed[] = [
                'email'  => $student['Mail'] ?? 'unknown',
                'reason' => 'Invalid email address'
            ];
            continue;
        }

        $mail = new PHPMailer(true);
        try {
            // SMTP configuration
            $mail->isSMTP();
            $mail->Host       = $smtpConfig['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpConfig['username'];
            $mail->Password   = $smtpConfig['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpConfig['port'];

            // Set sender and recipient
            $mail->setFrom($smtpConfig['from'], $smtpConfig['fromName']);
            $mail->addAddress($student['Mail'], $student['StudentFullName']);
            $mail->addReplyTo('teaching-awards@sabanciuniv.edu', 'Teaching Awards Support');

            $mail->isHTML(true);
            $mail->Subject = 'Reminder: Your Vote is Important!';

            // Replace placeholders in the template (if any). For example: {studentName} and {categoryID}
            $studentName = htmlspecialchars($student['StudentFullName']);
            $personalizedBody = str_replace(
                ['{studentName}', '{categoryID}'],
                [$studentName, $categoryID],
                $templateBody
            );

            $mail->Body    = $personalizedBody;
            $mail->AltBody = strip_tags($personalizedBody);

            // Attempt to send the mail
            if (!$mail->send()) {
                throw new Exception($mail->ErrorInfo);
            }
            $sentCount++;

            // 4) Log the mail into MailLog_Table
            $logStmt = $pdo->prepare("
                INSERT INTO MailLog_Table (Sender, StudentEmail, StudentName, MailContent)
                VALUES (:sender, :studentEmail, :studentName, :mailContent)
            ");
            $logStmt->execute([
                ':sender'       => $sender,
                ':studentEmail' => $student['Mail'],
                ':studentName'  => $student['StudentFullName'],
                ':mailContent'  => $personalizedBody
            ]);

        } catch (Exception $e) {
            error_log("Email send failed for {$student['Mail']}: " . $e->getMessage());
            $failed[] = [
                'email'  => $student['Mail'],
                'reason' => $e->getMessage()
            ];
        }

        // Optionally, add a delay (usleep) here if needed
    }

    // 5) Prepare and output JSON response
    $response = [
        'sent'   => $sentCount,
        'total'  => count($students),
        'failed' => $failed
    ];

    while (ob_get_level()) { ob_end_clean(); }
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    sendErrorResponse('Unexpected error: ' . $e->getMessage(), 500);
} finally {
    while (ob_get_level()) { ob_end_clean(); }
}
?>
