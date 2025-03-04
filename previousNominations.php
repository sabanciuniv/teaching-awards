<?php
session_start();
require_once __DIR__ . '/database/dbConnection.php'; // Ensure database connection

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Trim username to remove accidental spaces
$username = trim($_SESSION['user']);

try {
    // Ensure database connection is established
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection not established.");
    }

    // Fetch user's nominations and their uploaded documents
    $stmt = $pdo->prepare("
        SELECT n.nominationID, n.NomineeName, n.NomineeSurname, n.SubmissionDate, d.DocumentCodedName, d.DocumentOriginalName
        FROM `Nomination_Table` AS n
        LEFT JOIN `AdditionalDocuments_Table` AS d ON n.nominationID = d.NominationID
        WHERE n.SUnetUsername = ?
        ORDER BY n.SubmissionDate DESC
    ");
    $stmt->execute([$username]);
    $nominations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$nominations) {
        $noNominations = true;
    }
} catch (PDOException $e) {
    die("<div class='alert alert-danger text-center'>SQL Error: " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Previous Nominations</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Limitless Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        body {
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
            margin: 0;
            padding-top: 70px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        .container {
            width: 80%;
            max-width: 800px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        /* Ensure the accordion button background is always secondary */
        .accordion-button {
            color: white !important;
            font-weight: bold;
            border-radius: 5px;
            padding: 15px;
            font-size: 1.1rem;
            width: 100%;
            text-align: left;
        }

        /* Remove Bootstrap's default white background on collapsed state */
        .accordion-button.collapsed {
            
            color: white !important;
            border: none;
        }

        /* Prevent unwanted hover/focus changes */
        .accordion-button:hover, 
        .accordion-button:focus {
            
            color: white !important;
            box-shadow: none !important;
        }

        /* Ensure the entire accordion item stays bg-secondary */
        .accordion-item {
            background-color: var(--bs-secondary) !important;
            color: white !important;
            border: none;
        }

        /* Make sure the expanded section is also visible */
        .accordion-collapse {
            border-top: none !important;
        }

        /* Keep the accordion body a lighter color for contrast */
        .accordion-body {
            background-color: #f8f9fa;
            color: black;
        }


        .back-button {
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <?php $backLink = "nominate.php"; include 'navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="text-center mb-4">Previous Nominations</h2>

        <?php if (isset($noNominations)) { ?>
            <div class="alert alert-info text-center">You have not made any nominations yet.</div>
        <?php } else { ?>
            <div class="accordion">
            <?php foreach ($nominations as $index => $nomination) { ?>
                <div class="accordion-item bg-secondary text-white">
                    <h2 class="accordion-header">
                        <button class="accordion-button bg-secondary text-white fw-semibold collapsed"
                                type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapsed_item<?php echo $index; ?>">
                            Nomination for <?php echo htmlspecialchars($nomination['NomineeName'] . " " . $nomination['NomineeSurname']); ?>
                        </button>
                    </h2>
                    <div id="collapsed_item<?php echo $index; ?>" class="accordion-collapse collapse">
                        <div class="accordion-body bg-light text-dark">
                            <p><strong>Nominee Name:</strong> <?php echo htmlspecialchars($nomination['NomineeName']); ?></p>
                            <p><strong>Nominee Surname:</strong> <?php echo htmlspecialchars($nomination['NomineeSurname']); ?></p>
                            <p><strong>Submission Date:</strong> <?php echo htmlspecialchars($nomination['SubmissionDate']); ?></p>

                            <!-- Document Downloads -->
                            <?php if (!empty($nomination['DocumentCodedName'])) { ?>
                                <p><strong>Uploaded Documents:</strong></p>
                                <ul class="document-list">
                                    <li>
                                        <a href="uploads/<?php echo urlencode($nomination['DocumentCodedName']); ?>"
                                        download="<?php echo htmlspecialchars($nomination['DocumentOriginalName']); ?>">
                                            <?php echo htmlspecialchars($nomination['DocumentOriginalName']); ?>
                                        </a>
                                    </li>
                                </ul>
                            <?php } else { ?>
                                <p><strong>Uploaded Documents:</strong> No files uploaded</p>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>


        <?php } ?>

        <div class="text-center back-button">
            <a href="index.php" class="btn btn-secondary"> Return to Main Page</a>
        </div>
    </div>

</body>
</html>
