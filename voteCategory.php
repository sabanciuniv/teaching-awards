<?php
session_start();
if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    while(TRUE){
        echo($_SESSION['user']);
    }
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Category</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

   <style>
        body {
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            align-items: center;
            flex-direction: column;
            padding-top: 70px; 
        }

        /* Navbar */
        /* Logo and title in the navbar */
        .navbar-brand img {
            height: 40px;
        }

        .navbar-brand span {
            font-size: 1.25rem;
            font-weight: bold;
            color: white !important;
            margin-left: 10px;
        }

        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
            text-align: center;
        }

        .title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #000;
        }

        .categories-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin: 0 auto;
            max-width: 800px;
        }

        .category-card {
            flex: 0 0 250px;
            text-align: center;
            cursor: pointer;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, border-color 0.2s;
            color: #fff;
            height: 75px;
            background-color: var(--bs-secondary-bg); /* Use secondary background */
            border: 3px solid transparent; /* Default border */
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .category-card.completed {
            border-color: #4caf50; /* Green border for completed */
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
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-expand-lg fixed-top bg-secondary">
        <div class="container-fluid d-flex align-items-center">
            <div class="d-flex align-items-center">
                <!-- Back Arrow -->
                <a href="index.php" class="text-white" style="text-decoration:none; font-size:1.2rem;">
                    <i class="fas fa-arrow-left me-3"></i>
                </a>
                <!-- Logo and Title -->
                <a href="index.php" class="navbar-brand d-flex align-items-center ms-5">
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

    </div>
        <div class="content-wrapper">
        <div class="title">Select a Voting Category</div>
        <div class="categories-container" id="categories-container">
            <!-- Categories will be dynamically loaded here -->
        </div>
    </div>

    <script>
        const categories = [
            { id: 'A1', name: 'Birinci Sınıf Üniversite Derslerine Katkı Ödülü 1', url: 'voteScreen_A1.php' },
            { id: 'A2', name: 'Birinci Sınıf Üniversite Derslerine Katkı Ödülü 2', url: 'voteScreen_A2.php' },
            { id: 'B', name: 'Yılın Mezunları Ödülü', url: 'voteScreen_B.php' },
            { id: 'C', name: 'Temel Geliştirme Yılı Öğretim Görevlisi Ödülü', url: 'voteScreen_C.php' },
            { id: 'D', name: 'Birinci Sınıf Eğitim Asistanı Ödülü', url: 'voteScreen_D.php' },
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

        function markCategoryAsCompleted(categoryId) {
            if (!completedCategories.includes(categoryId)) {
                completedCategories.push(categoryId);
                localStorage.setItem('completedCategories', JSON.stringify(completedCategories));
                renderCategories();
            }
        }

        // Check for category completion on return
        const queryParams = new URLSearchParams(window.location.search);
        const completedCategoryId = queryParams.get('completedCategoryId');
        if (completedCategoryId) {
            markCategoryAsCompleted(completedCategoryId);
        }

        renderCategories();
    </script>


    <!-- JavaScript -->
    <script>
        function redirectToCategoryPage() {
            window.location.href = "voteCategory.php?completedCategoryId=A1";
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
