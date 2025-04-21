<?php
// notifyStudents.php
// ------------------
// Reads JSON { students: […], year: N }, sends personalized “notify” emails
// using the MailHeader as Subject and MailBody as the HTML body,
// logs each send (including TemplateID), and returns { sent, total, failed }.

session_start();
require_once __DIR__ . '/api/authMiddleware.php';
require_once __DIR__ . '/database/dbConnection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Force JSON output
header('Content-Type: application/json; charset=utf-8');

// 1) Decode payload
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['students'], $data['year'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing students array or year']);
    exit;
}
$students = $data['students'];
$year     = (int)$data['year'];
$sender   = $_SESSION['user'];

// 2) Load TemplateID, MailHeader & MailBody from the “notify” template
$stmt = $pdo->prepare("
  SELECT TemplateID, MailHeader, MailBody
    FROM MailTemplate_Table
   WHERE MailType = 'notify'
     AND MailHeader IS NOT NULL
     AND MailBody   IS NOT NULL
   LIMIT 1
");
$stmt->execute();
$template = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$template) {
    http_response_code(404);
    echo json_encode(['error' => "No 'notify' template found"]);
    exit;
}
$templateID     = (int)$template['TemplateID'];
$templateHeader = $template['MailHeader'];
$templateBody   = $template['MailBody'];

// 3) PHPMailer setup (persistent SMTP)
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host           = 'smtp.gmail.com';
    $mail->SMTPAuth       = true;
    $mail->Username       = 'ens492odul@gmail.com';
    $mail->Password       = 'aycmatyxmxhphsvh';
    $mail->SMTPSecure     = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port           = 587;
    $mail->setFrom('ens492odul@gmail.com', 'Teaching Awards System');
    $mail->SMTPKeepAlive  = true;   // keep connection open
    $mail->isHTML(true);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => "SMTP setup failed: {$e->getMessage()}"]);
    exit;
}

// 4) Send loop & logging
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

        // Dynamic subject from MailHeader
        $mail->Subject = str_replace(
            ['{year}', '{studentName}'],
            [$year, $stu['StudentFullName']],
            $templateHeader
        );

        // Personalized body
        $body = str_replace(
            ['{year}', '{studentName}'],
            [$year, $stu['StudentFullName']],
            $templateBody
        );
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        $sent++;

        // Log into MailLog_Table (including TemplateID)
        $log = $pdo->prepare("
            INSERT INTO MailLog_Table
                (Sender, StudentEmail, StudentName, TemplateID, MailContent)
            VALUES
                (:s, :e, :n, :tid, :c)
        ");
        $log->execute([
            ':s'   => $sender,
            ':e'   => $email,
            ':n'   => $stu['StudentFullName'],
            ':tid' => $templateID,
            ':c'   => $body
        ]);

    } catch (Exception $e) {
        $failed[] = ['email' => $email, 'reason' => $mail->ErrorInfo];
    }
}

// 5) Close SMTP connection and respond
$mail->smtpClose();

echo json_encode([
    'sent'   => $sent,
    'total'  => count($students),
    'failed' => $failed
]);
