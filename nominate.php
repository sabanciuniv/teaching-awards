<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nominate - Teaching Awards</title>

    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>

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
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
            padding: 20px;
        }

        .form-title {
            text-align: center;
            margin-top: 40px; 
            margin-bottom: 30px; 
            font-size: 1.8rem;
            font-weight: bold;
            color: #3f51b5; 
        }

        .form-group label {
            font-weight: bold;
            color: #333;
        }

        .form-control {
            border-radius: 6px;
            background-color: #f7f7f9;
            border: 1px solid #ddd;
            padding: 10px;
        }

        .form-control:focus {
            border-color: #3f51b5; 
            box-shadow: 0 0 3px rgba(63, 81, 181, 0.5);
        }

        .btn-indigo {
            background-color: #3f51b5;
            color: white;
            font-weight: bold;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-indigo:hover {
            background-color: #303f9f; 
        }

        .icon-paperplane {
            margin-left: 8px;
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
    <!-- Nomination Form Card -->
    <div class="card">
        <!-- Form Title -->
        <div class="form-title">Nomination Form</div>

        <!-- Form Body burası degistirildi, tekrar degistirilecek -->

        <form action="index.php" method="post" enctype="multipart/form-data">
            <!-- Your Name -->
            <div class="form-group">
                <label for="your-name">Your Name</label>
                <input type="text" id="your-name" name="your_name" class="form-control" placeholder="Enter your name" required>
            </div>

            <!-- Your Surname -->
            <div class="form-group">
                <label for="your-surname">Your Surname</label>
                <input type="text" id="your-surname" name="your_surname" class="form-control" placeholder="Enter your surname" required>
            </div>

            <!-- Nominee's Name -->
            <div class="form-group">
                <label for="nominee-name">Nominee's Name</label>
                <input type="text" id="nominee-name" name="nominee_name" class="form-control" placeholder="Enter nominee's name" required>
            </div>

            <!-- Nominee's Surname -->
            <div class="form-group">
                <label for="nominee-surname">Nominee's Surname</label>
                <input type="text" id="nominee-surname" name="nominee_surname" class="form-control" placeholder="Enter nominee's surname" required>
            </div>

            <!-- Upload References Form -->
            <div class="form-group">
                <label for="reference-document">Upload Reference Document</label>
                <input type="file" id="reference-document" name="reference_document" class="form-control" accept=".pdf,.doc,.docx" required>
            </div>

            <!-- Submit Button -->
            <div class="form-group text-right">
                <button type="submit" class="btn btn-indigo">
                    Submit <i class="icon-paperplane"></i>
                </button>    
            </div>
        </form>
    </div>
    

    <script src="assets/js/main/jquery.min.js"></script>
    <script src="assets/js/main/bootstrap.bundle.min.js"></script>
  <!-- JavaScript -->
  <script>
        function redirectToCategoryPage() {
            window.location.href = "voteCategory.php?completedCategoryId=A1";
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
