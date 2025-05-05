<?php
require_once 'api/authMiddleware.php';

require_once 'api/commonFunc.php';
$pageTitle= "Report Category";
require_once 'api/header.php';
init_session();


// Include the database connection (adjust the path if needed)
require_once __DIR__ . '/database/dbConnection.php';

// Get the current user from the session
$user = $_SESSION['user'];

// Admin access check (allow Admin and IT_Admin), log if unauthorized
$role = getUserAdminRole($pdo, $user);
if (!in_array($role, ['IT_Admin', 'Admin'])) {
    logUnauthorizedAccess($pdo, $user, basename(__FILE__));
    header("Location: index.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<style>
    body {
        background-color: #f9f9f9;
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
        font-size: 1.5rem;
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
</style>
<body>

    <?php $backLink = "adminDashboard.php"; include 'navbar.php'; ?>
   
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
            { id: 'VoteResults', name: 'Faculty Member Scores', url: 'facultyMemberScoreTable.php' },
            { id: 'VoterList', name: 'Voting Status Report', url: 'studentUsagePage.php' },
            { id: 'ParticipationRates', name: 'Voting Participation Report by Years', url: 'participationList.php' },
           
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
