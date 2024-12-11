<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Category</title>
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
            background-color: var(--bs-secondary-bg); /* Use secondary background */
            border: 3px solid transparent; /* Default border */
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
            text-align: center;
            padding: 25px;
        }
    </style>
</head>
<body>
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
</body>
</html>
