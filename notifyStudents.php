<?php
// Start output buffering IMMEDIATELY
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
 * Sends a JSON error response and ends execution
 */
function sendErrorResponse($message, $code = 400) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code($code);
    error_log("Notification Error [{$code}]: {$message}");
    echo json_encode([
        'error' => $message,
        'code'  => $code
    ]);
    exit;
}

/**
 * Catches any fatal errors (e.g., syntax errors) and sends a JSON response
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
    // Read input data as JSON or from POST
    if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
        $rawInput = file_get_contents("php://input");
        $data = json_decode($rawInput, true);
    } else {
        $data = $_POST;
        $data['students'] = json_decode($data['students'] ?? '[]', true);
    }

    // Ensure we have both "students" and "category" in the request
    if (!isset($data['students']) || !isset($data['category'])) {
        sendErrorResponse('Missing student data or category');
    }

    // "category" here is a numeric CategoryID (e.g., 7)
    $categoryID = (int)$data['category'];
    $students   = $data['students'];

    // Include your DB connection
    require_once __DIR__ . '/database/dbConnection.php';

    // 1) Fetch the mail template based on CategoryID
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

    // 2) Configure your SMTP settings
    $smtpConfig = [
        'host'     => 'smtp.gmail.com',
        'port'     => 587,
        'username' => 'ens492odul@gmail.com',
        'password' => 'aycmatyxmxhphsvh',
        'from'     => 'ens492odul@gmail.com',
        'fromName' => 'Sabanci Teaching Awards System'
    ];

    $sentCount = 0;
    $failed = [];

    // 3) Loop over students and send the email
    foreach ($students as $student) {
        // Validate the student's email
        if (empty($student['Mail']) || !filter_var($student['Mail'], FILTER_VALIDATE_EMAIL)) {
            $failed[] = [
                'email'  => $student['Mail'] ?? 'unknown',
                'reason' => 'Invalid email address'
            ];
            continue;
        }

        $mail = new PHPMailer(true);
        try {
            // SMTP Auth
            $mail->isSMTP();
            $mail->Host       = $smtpConfig['host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $smtpConfig['username'];
            $mail->Password   = $smtpConfig['password'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $smtpConfig['port'];

            // From & to
            $mail->setFrom($smtpConfig['from'], $smtpConfig['fromName']);
            $mail->addAddress($student['Mail'], $student['StudentFullName']);
            $mail->addReplyTo('teaching-awards@sabanciuniv.edu', 'Teaching Awards Support');

            $mail->isHTML(true);
            $mail->Subject = 'Reminder: Your Vote is Important!';

            // You can do placeholder replacements here if your templateBody uses them:
            // e.g. {studentName}, {categoryID}, etc.
            $studentName = htmlspecialchars($student['StudentFullName']);
            $personalizedBody = str_replace(
                ['{studentName}', '{categoryID}'],
                [$studentName, $categoryID],
                $templateBody
            );

            // Set body
            $mail->Body    = $personalizedBody;
            $mail->AltBody = strip_tags($personalizedBody);

            // Send
            if (!$mail->send()) {
                throw new Exception($mail->ErrorInfo);
            }
            $sentCount++;
        } catch (Exception $e) {
            error_log("Email send failed for {$student['Mail']}: " . $e->getMessage());
            $failed[] = [
                'email'  => $student['Mail'],
                'reason' => $e->getMessage()
            ];
        }
        
        // If you want a delay to avoid SMTP rate limits, uncomment:
        // usleep(100000);
    }

    // 4) Output JSON response with send results
    $response = [
        'sent'   => $sentCount,
        'total'  => count($students),
        'failed' => $failed
    ];

    while (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode($response);
    exit;

} catch (Exception $e) {
    sendErrorResponse('Unexpected error: ' . $e->getMessage(), 500);
} finally {
    while (ob_get_level()) {
        ob_end_clean();
    }
}
?>
