<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Category</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <style>
        body {
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
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
</head>
<body>
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
            { id: 'VoteResults', name: 'Öğretim Üyeleri Puanları', url: 'voteScreen_A2.php' },
            { id: 'VoterList', name: 'Oy Kullanım Durumu Raporu', url: 'voterListDataTable.php' },
            { id: 'ParticipationRates', name: 'Yıllara Göre Oylamaya Katılım Raporu', url: 'voteScreen_B.php' }
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
</body>
</html>
