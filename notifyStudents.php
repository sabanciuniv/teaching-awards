<?php
// notifyStudents.php

// Load configuration and helpers
$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/api/authMiddleware.php';
require_once __DIR__ . '/database/dbConnection.php';
require_once __DIR__ . '/api/commonFunc.php';

init_session();
// only admins may send notifications
enforceAdminAccess($pdo);
ini_set('max_execution_time', '1200'); //1200 seconds = 20 minutes
ignore_user_abort(true);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1) JSON response header
header('Content-Type: application/json; charset=utf-8');

// 2) Decode payload
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['students'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing students array']);
    exit;
}
$students = $data['students'];
$sender   = $_SESSION['user'];

// 3) Load “notify” template
$stmt = $pdo->prepare(
    "SELECT TemplateID, MailHeader, MailBody
     FROM MailTemplate_Table
     WHERE MailType = 'notify'
     LIMIT 1"
);
$stmt->execute();
$tpl = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tpl) {
    http_response_code(404);
    echo json_encode(['error' => "No 'notify' template found"]);
    exit;
}
$templateID     = $tpl['TemplateID'];
$templateHeader = $tpl['MailHeader'];
$templateBody   = $tpl['MailBody'];

// 4) Fetch current academic year info
$yearInfo = fetchCurrentAcademicYear($pdo);
if (!$yearInfo) {
    http_response_code(500);
    echo json_encode(['error' => 'No academic year found']);
    exit;
}
$currentYearID = (int) $yearInfo['YearID'];
$startYear     = (int) $yearInfo['Academic_year'];
$yearLabel     = "$startYear-" . ($startYear + 1);

// 5) PHPMailer setup
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = $config['mail']['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['mail']['username'];
    $mail->Password   = $config['mail']['password'];
    $mail->CharSet 	  = "UTF-8";	
    // Map encryption
    if ($config['mail']['encryption'] === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } elseif ($config['mail']['encryption'] === 'tls') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mail->Port         = $config['mail']['port'];
    $mail->setFrom(
        $config['mail']['from_address'], 
        $config['mail']['from_name']
    );
    $mail->SMTPKeepAlive = true;
    $mail->isHTML(true);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => "SMTP setup failed: {$e->getMessage()}"]);
    exit;
}

// 6) Send loop
$sent   = 0;
$failed = [];

foreach ($students as $stu) {
    $email = $stu['Email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $failed[] = ['email' => $email, 'reason' => 'Invalid email'];
        continue;
    }

    try {
        $mail->clearAddresses();
        $mail->addAddress($email, $stu['StudentFullName']);

        // personalize header & body
        $search  = ['{studentName}','{year}','@name_surname','@year'];
        $replace = [
            $stu['StudentFullName'],
            $yearLabel,
            $stu['StudentFullName'],
            $yearLabel
        ];

        $mail->Subject = str_replace($search, $replace, $templateHeader);
        $body         = str_replace($search, $replace, $templateBody);
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        $sent++;

        // log into MailLog_Table
        $stmtLog = $pdo->prepare(
            "INSERT INTO MailLog_Table
             (Sender, StudentEmail, StudentName, TemplateID, MailContent, YearID)
             VALUES (:s, :e, :n, :tid, :c, :y)"
        );
        $stmtLog->execute([
            ':s'   => $sender,
            ':e'   => $email,
            ':n'   => $stu['StudentFullName'],
            ':tid' => $templateID,
            ':c'   => $body,
            ':y'   => $currentYearID
        ]);

    } catch (Exception $e) {
        $failed[] = ['email' => $email, 'reason' => $e->getMessage()];
    }
}

// close SMTP connection
$mail->smtpClose();

// 7) Return JSON summary
echo json_encode([
    'sent'   => $sent,
    'total'  => count($students),
    'failed' => $failed
]);
?>
