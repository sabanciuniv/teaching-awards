<?php
session_start();
require_once 'api/authMiddleware.php';
require_once 'config.php'; // Load config file

$config = require 'config.php'; // Fetch configuration
$uploadDir = $config['upload_directory']; // Get the upload directory path

if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$apiUrl = 'http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/getAcademicYear.php';
$response = file_get_contents($apiUrl);
$data = json_decode($response, true);

if ($data['status'] !== 'success') {
    die("Error: Could not fetch academic year.");
}

$academicYear = $data['academicYear']; 

// Define the directory path for this academic year
$yearlyUploadDir = $uploadDir . $academicYear . '/'; // Path in config + '2024/'

// Ensure directory exists or create it
if (!is_dir($yearlyUploadDir)) {
    mkdir($yearlyUploadDir, 0777, true); // Create the directory with write permissions
}

if (!is_writable($yearlyUploadDir)) {
    die("Error: Directory for the academic year is not writable.");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1) Verify that the user accepted the rules
    $rulesAccepted = $_POST['rulesAccepted'] ?? '';
    if ($rulesAccepted !== 'true') {
        die("Error: You must accept the rules to submit.");
    }

    echo "<pre>";
    print_r($_FILES);
    print_r($_POST);
    echo "</pre>";

    if (empty($_FILES['ReferenceLetterFiles']['name'][0])) {
        die("Error: No files uploaded.");
    }

    require_once __DIR__ . '/database/dbConnection.php';




    // Ensure uploads folder exists and is writable
    if (!is_dir($yearlyUploadDir)) {
        mkdir($yearlyUploadDir, 0777, true);
    }
    if (!is_writable($yearlyUploadDir)) {
        die("Error: Uploads directory is not writable.");
    }

    // 2) Gather form data
    $username       = $_SESSION['user'];
    $nomineeName    = htmlspecialchars($_POST['NomineeName'] ?? '');
    $nomineeSurname = htmlspecialchars($_POST['NomineeSurname'] ?? '');
    $yearID         = htmlspecialchars($_POST['year_id'] ?? '');

    if (!$username || !$nomineeName || !$nomineeSurname || !$yearID) {
        die("Error: Missing form data.");
    }


    try {
        $pdo->beginTransaction();

        // 4) Insert into Nomination_Table, storing 'true' as a string in isAccepted
        $stmt = $pdo->prepare("
            INSERT INTO Nomination_Table
            (SUnetUsername, NomineeName, NomineeSurname, YearID, SubmissionDate, isAccepted)
            VALUES (?, ?, ?, ?, NOW(), ?)
        ");
        // Use the literal string 'true'
        if (!$stmt->execute([$username, $nomineeName, $nomineeSurname, $yearID, 'true'])) {
            throw new Exception("Error inserting nomination data.");
        }
        $nominationID = $pdo->lastInsertId();

        // 5) Process uploaded files
        $processedFiles = [];
        foreach ($_FILES['ReferenceLetterFiles']['tmp_name'] as $key => $tmp_name) {
            $originalName = $_FILES['ReferenceLetterFiles']['name'][$key];
            if (in_array($originalName, $processedFiles)) {
                continue;
            }
            if ($_FILES['ReferenceLetterFiles']['error'][$key] == 0) {
                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                $allowedExtensions = ['pdf', 'doc', 'docx', 'txt', 'ppt', 'pptx', 'jpg', 'jpeg', 'png'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception("Invalid file type: " . $originalName);
                }

                // Up to 20MB
                if ($_FILES['ReferenceLetterFiles']['size'][$key] > 20 * 1024 * 1024) {
                    throw new Exception("File size exceeds limit for: " . $originalName);
                }

                // Generate a unique coded name
                $codedName = 'Ref_' . $academicYear . "_" . uniqid() . "." . $fileExtension;
                $uploadPath = $yearlyUploadDir . $codedName;

                if (move_uploaded_file($tmp_name, $uploadPath)) {
                    chmod($uploadPath, 0775);
                    $stmt = $pdo->prepare("
                        INSERT INTO AdditionalDocuments_Table
                        (NominationID, DocumentType, DocumentCodedName, DocumentOriginalName)
                        VALUES (?, ?, ?, ?)
                    ");
                    if (!$stmt->execute([$nominationID, $fileExtension, $codedName, $originalName])) {
                        throw new Exception("Error inserting document data.");
                    }
                    $processedFiles[] = $originalName;
                } else {
                    throw new Exception("Error moving uploaded file: " . $originalName);
                }
            } else {
                throw new Exception(
                    "Upload error for file: " . $originalName .
                    " (Error Code: " . $_FILES['ReferenceLetterFiles']['error'][$key] . ")"
                );
            }
        }

        $pdo->commit();
        echo "Success";
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Upload error: " . $e->getMessage());
        echo "Error: " . $e->getMessage();
    }
}
