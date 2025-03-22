<?php
require_once __DIR__ . '/database/dbConnection.php';

// Include PHPMailer classes manually
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['students']) || !isset($data['category'])) {
    echo json_encode(['error' => 'Missing student data or category']);
    exit;
}

$category = htmlspecialchars($data['category']);
$students = $data['students'];

$sentCount = 0;
$failed = [];

foreach ($students as $student) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'localhost';     // Mailpit
        $mail->Port = 1025;            // Mailpit SMTP port
        $mail->SMTPAuth = false;       // No auth needed for Mailpit

        $mail->setFrom('no-reply@university.edu', 'Voting System');
        $mail->addAddress($student['Mail'], $student['StudentFullName']);

        $mail->Subject = 'Reminder: Please vote!';
        $mail->Body = "Dear {$student['StudentFullName']},\n\nYou have not yet voted in category '{$category}'. Please log in to the system and cast your vote as soon as possible.\n\nThank you.";

        $mail->send();
        $sentCount++;
    } catch (Exception $e) {
        $failed[] = $student['Mail'];
    }
}

echo json_encode([
    'sent' => $sentCount,
    'failed' => $failed
]);
