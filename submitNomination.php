<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        echo "<pre>";
        print_r($_FILES);
        print_r($_POST);
        echo "</pre>";
        if (empty($_FILES['ReferenceLetterFiles']['name'][0])) {
            die("Error: No files uploaded.");
        }
        require_once __DIR__ . '/database/dbConnection.php';
        if (!is_dir(__DIR__ . '/uploads')) {
            mkdir(__DIR__ . '/uploads', 0777, true);
        }
        if (!is_writable(__DIR__ . '/uploads')) {
            die("Error: Uploads directory is not writable.");
        }
        // Sanitize user inputs
        $username = htmlspecialchars($_POST['SUnetUsername'] ?? '');
        $nomineeName = htmlspecialchars($_POST['NomineeName'] ?? '');
        $nomineeSurname = htmlspecialchars($_POST['NomineeSurname'] ?? '');
        $yearID = htmlspecialchars($_POST['year_id'] ?? '');
        if (!$username || !$nomineeName || !$nomineeSurname || !$yearID) {
            die("Error: Missing form data.");
        }
        // Retrieve the actual academic year from the database using year_id
        $stmt = $pdo->prepare("SELECT Academic_year FROM AcademicYear_Table WHERE YearID = ?");
        $stmt->execute([$yearID]);
        $yearRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$yearRow) {
            die("Error: Invalid academic year ID.");
        }
        $academicYear = $yearRow['Academic_year'];
        try {
            $pdo->beginTransaction();
            // Insert nomination data
            $stmt = $pdo->prepare("INSERT INTO Nomination_Table (SUnetUsername, NomineeName, NomineeSurname, YearID) VALUES (?, ?, ?, ?)");
            if (!$stmt->execute([$username, $nomineeName, $nomineeSurname, $yearID])) {
                throw new Exception("Error inserting nomination data.");
            }
            $nominationID = $pdo->lastInsertId();
            $processedFiles = [];
            foreach ($_FILES['ReferenceLetterFiles']['tmp_name'] as $key => $tmp_name) {
                $originalName = $_FILES['ReferenceLetterFiles']['name'][$key];
                // Prevent duplicate file uploads
                if (in_array($originalName, $processedFiles)) {
                    continue;
                }
                if ($_FILES['ReferenceLetterFiles']['error'][$key] == 0) {
                    $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    // Validate allowed file types
                    $allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
                    if (!in_array($fileExtension, $allowedExtensions)) {
                        throw new Exception("Invalid file type: " . $originalName);
                    }
                    // Validate file size (max 5MB)
                    if ($_FILES['ReferenceLetterFiles']['size'][$key] > 5 * 1024 * 1024) {
                        throw new Exception("File size exceeds limit for: " . $originalName);
                    }
                    // Generate a unique and sanitized file name with academic year
                    $codedName = 'Ref_' . $academicYear . "_" . uniqid() . "." . $fileExtension;
                    $uploadPath = __DIR__ . '/uploads/' . $codedName;
                    if (move_uploaded_file($tmp_name, $uploadPath)) {
                        $stmt = $pdo->prepare("INSERT INTO AdditionalDocuments_Table (NominationID, DocumentType, DocumentCodedName, DocumentOriginalName) VALUES (?, ?, ?, ?)");
                        if (!$stmt->execute([$nominationID, $fileExtension, $codedName, $originalName])) {
                            throw new Exception("Error inserting document data.");
                        }
                        // Add file to processed list to prevent duplicate processing
                        $processedFiles[] = $originalName;
                    } else {
                        throw new Exception("Error moving uploaded file: " . $originalName);
                    }
                } else {
                    throw new Exception("Upload error for file: " . $originalName . " (Error Code: " . $_FILES['ReferenceLetterFiles']['error'][$key] . ")");
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
?>