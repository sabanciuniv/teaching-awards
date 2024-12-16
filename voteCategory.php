<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Category</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    
    <!-- Bootstrap and Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

   
   <style>
        body {
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
            padding-top: 50px; 
        }

        /* Navbar */
        .navbar {
            background-color: #3f51b5;
            padding: 10px 20px;
        }
        .navbar-brand img {
            height: 40px;
        }
        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
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
            height: 75px;
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

        .return-button {
            position: fixed; /* Fix the position relative to the viewport */
            bottom: 100px; /* Distance from the bottom */
            left: 50%; /* Align to center horizontally */
            transform: translateX(-50%); /* Adjust for exact centering */
            text-align: center; /* Ensure alignment */
            z-index: 10; /* Ensures it stays above other content */
        }

        .return-button button {
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
            background-color: #007bff; /* Default color */
            color: white; /* Text color */
            border: none;
            cursor: pointer;
        }

        .return-button button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-expand-lg fixed-top">
        <div class="container-fluid">
            <!-- Logo -->
            <a href="voteCategory.php" class="navbar-brand d-flex align-items-center">
                <img src="https://yabangee.com/wp-content/uploads/sabancı-university-2.jpg" alt="Logo">
                <span class="ms-2 text-white fs-5 fw-bold">Sabancı University</span>
            </a>

			
			<!-- Navbar Links -->
			<div class="collapse navbar-collapse">
				<ul class="navbar-nav ms-auto align-items-center">
					<!-- Welcome Dropdown -->
					<li class="nav-item dropdown">
						<a href="#" class="nav-link dropdown-toggle text-white" id="welcomeDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
							Welcome, <strong>damla.aydin</strong>
						</a>
						<ul class="dropdown-menu dropdown-menu-end" aria-labelledby="welcomeDropdown">
							<li>
								<a class="dropdown-item" href="voteCategory.php">
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

 
<div class="return-button">
    <button 
        onclick="window.location.href='index.php'" 
        class="btn btn-primary"
        onmouseover="this.style.backgroundColor='#0056b3'"
        onmouseout="this.style.backgroundColor='#007bff'">
        Return to Main Menu
    </button>
</div>

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
