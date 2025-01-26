<?php
session_start();

// Determine context: "nominate" or "vote"
$context = isset($_GET['context']) ? htmlspecialchars($_GET['context']) : 'vote'; // Default to "vote"
$completedCategoryId = isset($_GET['completedCategoryId']) ? htmlspecialchars($_GET['completedCategoryId']) : null;

// Define messages and redirect URLs based on context
if ($context === 'nominate') {
    $thankYouMessage = "Thank You for Your Nomination!";
    $redirectUrl = "index.php";
    $buttonText = "Back to Main Page";
} else {
    $thankYouMessage = "Thank You for Voting!";
    $redirectUrl = "voteCategory.php";
    $buttonText = "Back to Vote Categories";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You</title>
    <!-- Limitless Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }

        .thank-you-container {
            background: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }

        .thank-you-message {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .home-button {
            margin-top: 20px;
            font-size: 18px;
            padding: 10px 30px;
            background-color: #45748a;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .home-button:hover {
            background-color: rgb(203, 206, 208);
            color: #333;
        }
    </style>
</head>
<body>
    <div class="thank-you-container">
        <h1 class="thank-you-message"><?= $thankYouMessage ?></h1>
        <p>Your action has been successfully recorded.</p>
        <a href="<?= $redirectUrl ?>" class="home-button" id="back-button"><?= $buttonText ?></a>
    </div>
    <script>
        (function () {
            // Retrieve the completed category ID from PHP
            const completedCategoryId = "<?= $completedCategoryId ?>";

            if (completedCategoryId) {
                // Retrieve or initialize the completed categories in localStorage
                const completedCategories = JSON.parse(localStorage.getItem('completedCategories')) || [];

                // Add the completed category if not already marked
                if (!completedCategories.includes(completedCategoryId)) {
                    completedCategories.push(completedCategoryId);
                    localStorage.setItem('completedCategories', JSON.stringify(completedCategories));
                }
            }
        })();
    </script>
</body>
</html>
