<?php
session_start();
if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Category</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Limitless Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">


    <style>
        body {
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin-top: 200px;
        }

        .content-wrapper {
            text-align: center;
        }

        .title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #000;
        }

        .categories-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            justify-content: center;
            align-items: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .category-card {
            cursor: pointer;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, border-color 0.2s;
            position: relative;
            color: #fff;
            font-size: large;
            background-color: var(--bs-secondary-bg);
            border: 3px solid transparent;
            height: 75px; /* Fix height for uniform size */
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .category-card.completed {
            border-color: #4caf50;
        }

        .category-card:hover {
            transform: translateY(-5px);
        }

        .category-card.completed .checkmark {
            display: block;
        }

        .checkmark {
            display: none;
            position: absolute;
            bottom: 10px;
            right: 10px;
            font-size: 1.5rem;
            color: #4caf50;
        }

        .card-body {
            padding: 15px;
        }

        
        .return-button {
            grid-column: 2 / 3; /* Place below the second button */
            margin-top: 20px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .return-button:hover {
            background-color: #0056b3;
        }

        
        .navbar-brand img {
            height: 40px;
        }

        .navbar-brand span {
            font-size: 1.25rem;
            font-weight: bold;
            color: white !important;
            margin-left: 10px;
        }

        
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-expand-lg fixed-top bg-secondary">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <!-- Logo and Title -->
                <a href="nominate.php" class="navbar-brand d-flex align-items-center ms-5">
                    <img src="https://yabangee.com/wp-content/uploads/sabancı-university-2.jpg" alt="Logo">
                    <span>Teaching Awards</span>
                </a>
            </div>
            <!-- Toggler for Mobile -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Navbar Links -->
            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
                <ul class="navbar-nav align-items-center">
                    <!-- Welcome Dropdown -->
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle text-white" id="welcomeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Welcome, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="welcomeDropdown">
                            <li>
                                <a class="dropdown-item" href="index.php">
                                    <i class="fas fa-home me-2"></i> Home
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-question-circle me-2"></i> Help
                                </a>
                            </li>
                            <div class="dropdown-divider"></div>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="content-wrapper">
        <div class="title">Select a Report Category</div>
        <div class="categories-container" id="categories-container">
            <!-- Categories will be dynamically loaded here -->
        </div>
        <button 
            onclick="window.location.href='adminDashboard.php'" 
            class="return-button">
            Return to Main Menu
        </button>
    </div>

    <script>
        const categories = [
            { id: 'VoteResults', name: 'Faculty Member Scores', url: 'voterListDataTable.php' },
            { id: 'VoterList', name: 'Voting Status Report', url: 'voterListDataTable.php' },
            { id: 'ParticipationRates', name: 'Voting Participation Report by Years', url: 'voterListDataTable.php' }
        ];
        // Retrieve completed categories from localStorage
        const completedCategories = JSON.parse(localStorage.getItem('completedCategories')) || [];

        function renderCategories() {
            const container = document.getElementById('categories-container');
            container.innerHTML = '';
            categories.forEach(category => {
                const isCompleted = completedCategories.includes(category.id);
                const card = document.createElement('div');
                card.className = `card category-card bg-secondary ${isCompleted ? 'completed' : ''}`;
                card.onclick = () => window.location.href = category.url; // Redirect to the category page
                
                // Card content
                const cardBody = document.createElement('div');
                cardBody.className = 'card-body';
                cardBody.textContent = category.name;

                // Checkmark for completed categories
                const checkmark = document.createElement('div');
                checkmark.className = 'checkmark';
                checkmark.textContent = '✔';

                card.appendChild(cardBody);
                card.appendChild(checkmark);
                container.appendChild(card);
            });
        }

        renderCategories();
    </script>
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
