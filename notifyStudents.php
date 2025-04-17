<?php
require_once 'api/authMiddleware.php';
require_once __DIR__ . '/database/dbConnection.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// JSON output
header('Content-Type: application/json; charset=utf-8');

// Read payload
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['students'], $data['year'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Missing students array or year']);
    exit;
}
$students = $data['students'];
$year     = (int)$data['year'];
$sender   = $_SESSION['user'];

// Fetch the 'notify' template
$stmt = $pdo->prepare("
  SELECT MailBody
    FROM MailTemplate_Table
   WHERE MailType = 'notify'
   LIMIT 1
");
$stmt->execute();
$row = $stmt->fetch();
if (!$row) {
    http_response_code(404);
    echo json_encode(['error'=>"No 'notify' template found"]);
    exit;
}
$template = $row['MailBody'];

// SMTP config
$smtp = [
  'host'=>'smtp.gmail.com','port'=>587,
  'user'=>'ens492odul@gmail.com','pass'=>'aycmatyxmxhphsvh',
  'from'=>'ens492odul@gmail.com','fromName'=>'Teaching Awards'
];

$sent = 0;
$failed = [];

foreach ($students as $stu) {
    $email = $stu['Email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $failed[] = ['email'=>$email,'reason'=>'Invalid email'];
        continue;
    }
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['user'];
        $mail->Password   = $smtp['pass'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp['port'];

        $mail->setFrom($smtp['from'],$smtp['fromName']);
        $mail->addAddress($email,$stu['StudentFullName']);
        $mail->isHTML(true);
        $mail->Subject = "Reminder: Please vote for Academic Year {$year}";

        // personalize
        $body = str_replace(
            ['{studentName}','{year}'],
            [$stu['StudentFullName'],$year],
            $template
        );
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);
        $mail->send();
        $sent++;

        // log
        $log = $pdo->prepare("
          INSERT INTO MailLog_Table
            (Sender,StudentEmail,StudentName,MailContent)
          VALUES
            (:s,:e,:n,:c)
        ");
        $log->execute([
          ':s'=>$sender,
          ':e'=>$email,
          ':n'=>$stu['StudentFullName'],
          ':c'=>$body
        ]);
    } catch (Exception $e) {
        $failed[] = ['email'=>$email,'reason'=>$mail->ErrorInfo];
    }
}

echo json_encode([
    'sent'   => $sent,
    'total'  => count($students),
    'failed' => $failed
]);
