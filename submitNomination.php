<?php
session_start();
require_once 'api/authMiddleware.php';
if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    if (!is_dir(__DIR__ . '/uploads')) {
        mkdir(__DIR__ . '/uploads', 0777, true);
    }
    if (!is_writable(__DIR__ . '/uploads')) {
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

    // 3) Retrieve academic year from DB
    $stmt = $pdo->prepare("SELECT Academic_year FROM AcademicYear_Table WHERE YearID = ?");
    $stmt->execute([$yearID]);
    $yearRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$yearRow) {
        die("Error: Invalid academic year ID.");
    }
    $academicYear = $yearRow['Academic_year'];

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

                $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                if (!in_array($fileExtension, $allowedExtensions)) {
                    throw new Exception("Invalid file type: " . $originalName);
                }

                // Up to 20MB
                if ($_FILES['ReferenceLetterFiles']['size'][$key] > 20 * 1024 * 1024) {
                    throw new Exception("File size exceeds limit for: " . $originalName);
                }

                // Generate a unique coded name
                $codedName = 'Ref_' . $academicYear . "_" . uniqid() . "." . $fileExtension;
                $uploadPath = __DIR__ . '/uploads/' . $codedName;

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
