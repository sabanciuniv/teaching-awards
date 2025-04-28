<?php
// notifyStudents.php
require_once __DIR__ . '/api/authMiddleware.php';
require_once __DIR__ . '/database/dbConnection.php';
require_once 'api/commonFunc.php';
init_session();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 1) JSON response
header('Content-Type: application/json; charset=utf-8');

// 2) Decode payload
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['students'])) {
    http_response_code(400);
    echo json_encode(['error'=>'Missing students array']);
    exit;
}
$students = $data['students'];
$sender   = $_SESSION['user'];

// 3) Load “notify” template (header + body + id)
$stmt = $pdo->prepare("
  SELECT TemplateID, MailHeader, MailBody
    FROM MailTemplate_Table
   WHERE MailType = 'notify'
   LIMIT 1
");
$stmt->execute();
$tpl = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tpl) {
    http_response_code(404);
    echo json_encode(['error'=>"No 'notify' template found"]);
    exit;
}
$templateID     = $tpl['TemplateID'];
$templateHeader = $tpl['MailHeader'];
$templateBody   = $tpl['MailBody'];

// 4) Lookup current academic YearID and Academic_year number
$currentYearRow = $pdo->query("
    SELECT YearID, Academic_year
      FROM AcademicYear_Table
     ORDER BY Start_date_time DESC
     LIMIT 1
")->fetch(PDO::FETCH_ASSOC);
if (!$currentYearRow) {
    http_response_code(500);
    echo json_encode(['error'=>'No academic year found']);
    exit;
}
$currentYearID     = (int)$currentYearRow['YearID'];
$academicYearStart = (int)$currentYearRow['Academic_year'];
// build label "2024-2025"
$yearLabel = $academicYearStart . '-' . ($academicYearStart + 1);

// 5) Prepare one persistent PHPMailer instance
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
    $mail->setFrom('ens492odul@gmail.com','Teaching Awards System');
    $mail->SMTPKeepAlive  = true;
    $mail->isHTML(true);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>"SMTP setup failed: {$e->getMessage()}"]);
    exit;
}

// 6) Send loop
$sent   = 0;
$failed = [];

foreach ($students as $stu) {
    $email = $stu['Email'] ?? '';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $failed[] = ['email'=>$email,'reason'=>'Invalid email'];
        continue;
    }

    try {
        $mail->clearAddresses();
        $mail->addAddress($email, $stu['StudentFullName']);

        // Subject: replace both {studentName},{year} and @name_surname,@year
        $mail->Subject = str_replace(
            ['{studentName}','{year}','@name_surname','@year'],
            [$stu['StudentFullName'],$yearLabel,$stu['StudentFullName'],$yearLabel],
            $templateHeader
        );

        // Body: same replacements
        $body = str_replace(
            ['{studentName}','{year}','@name_surname','@year'],
            [$stu['StudentFullName'],$yearLabel,$stu['StudentFullName'],$yearLabel],
            $templateBody
        );
        $mail->Body    = $body;
        $mail->AltBody = strip_tags($body);

        $mail->send();
        $sent++;

        // log into MailLog_Table
        $pdo->prepare("
          INSERT INTO MailLog_Table
            (Sender,StudentEmail,StudentName,TemplateID,MailContent,YearID)
          VALUES
            (:s,:e,:n,:tid,:c,:y)
        ")->execute([
            ':s'   => $sender,
            ':e'   => $email,
            ':n'   => $stu['StudentFullName'],
            ':tid' => $templateID,
            ':c'   => $body,
            ':y'   => $currentYearID
        ]);

    } catch (Exception $e) {
        $failed[] = ['email'=>$email,'reason'=>$e->getMessage()];
    }
}

$mail->smtpClose();

// 7) Return JSON
echo json_encode([
    'sent'   => $sent,
    'total'  => count($students),
    'failed' => $failed
]);
