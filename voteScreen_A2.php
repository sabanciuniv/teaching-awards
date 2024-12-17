<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Teaching Awards - Sabancı University</title>

    <!-- Bootstrap and Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <style>
        /* General Page Styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
        }

        /* Navbar */
        .navbar {
            background-color: #3f51b5;
            padding: 10px 20px;
            height: 70px; 
        }
        .navbar-brand img {
            height: 30px;
        }
        .navbar-nav .nav-link {
            color: white !important;
            font-weight: 500;
        }

        /* Welcome Section */
        .welcome-section {
            text-align: right;
            margin-top: 10px;
            color: white;
            font-size: 1.1rem;
        }

        /* Content Section */
        .content {
            padding: 20px;
        }

        /* Cards Section */
        .card {
            display: flex;
            flex-direction: column;
            justify-content: center; /* Vertically center content */
            align-items: center; /* Horizontally center content */
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            background-color: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%; /* Make the image circular */
            margin-bottom: 10px;
        }

        /* Award Category Header */
        .award-category {
            text-align: center;
            font-size: 1.2rem;
            font-weight: bold;
            color: white;
            background-color: #3f51b5;
            padding: 10px;
            border-radius: 8px;
            margin: 20px 0;
        }

        /* Submit Button */
        .submit-btn {
            position: fixed;
            bottom: 20px;
            right: 30px;
            background-color: #3f51b5;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .submit-btn:hover {
            background-color: #3f51b5;
            cursor: pointer;
        }

        /* Background Placeholder Fix */
        .background-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 25px;
            background-color: #3f51b5;
            z-index: -1;
        }
    </style>
</head>

<body>
    <!-- Background Placeholder -->
    <div class="background-placeholder"></div>

   <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-expand-lg">
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


    <!-- Content Section -->
    <div class="content container">
		<!-- Award Category -->
		<div class="award-category">
        Birinci Sınıf Üniversite Derslerine Katkı Ödülü 2 (Amfi dersleri)
		</div>

        <!-- Instructor Cards -->
        <div class="row justify-content-center">
            <div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
                    <h6>Name Surname</h6>
                    <p>MATH101 Instructor</p>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">1st place</a>
                            <a class="dropdown-item" href="#">2nd place</a>
                            <a class="dropdown-item" href="#">3rd place</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
                    <h6>Name Surname</h6>
                    <p>MATH102 Instructor</p>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">1st place</a>
                            <a class="dropdown-item" href="#">2nd place</a>
                            <a class="dropdown-item" href="#">3rd place</a>
                        </div>
                    </div>
                </div>
            </div>
			<div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
                    <h6>Name Surname</h6>
                    <p>SPS101 Instructor</p>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">1st place</a>
                            <a class="dropdown-item" href="#">2nd place</a>
                            <a class="dropdown-item" href="#">3rd place</a>
                        </div>
                    </div>
                </div>
            </div>
			<div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
                    <h6>Name Surname</h6>
                    <p>SPS102 Instructor</p>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">1st place</a>
                            <a class="dropdown-item" href="#">2nd place</a>
                            <a class="dropdown-item" href="#">3rd place</a>
                        </div>
                    </div>
                </div>
            </div>
			<div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
                    <h6>Name Surname</h6>
                    <p>TLL102 Instructor</p>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">1st place</a>
                            <a class="dropdown-item" href="#">2nd place</a>
                            <a class="dropdown-item" href="#">3rd place</a>
                        </div>
                    </div>
                </div>
            </div>
			<div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
                    <h6>Name Surname</h6>
                    <p>IF100 Instructor</p>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">1st place</a>
                            <a class="dropdown-item" href="#">2nd place</a>
                            <a class="dropdown-item" href="#">3rd place</a>
                        </div>
                    </div>
                </div>
            </div>
			<div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
                    <h6>Name Surname</h6>
                    <p>HIST191 Instructor</p>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">1st place</a>
                            <a class="dropdown-item" href="#">2nd place</a>
                            <a class="dropdown-item" href="#">3rd place</a>
                        </div>
                    </div>
                </div>
            </div>
			<div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
                    <h6>Name Surname</h6>
                    <p>HIST192 Instructor</p>
                    <div class="dropdown">
                        <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">1st place</a>
                            <a class="dropdown-item" href="#">2nd place</a>
                            <a class="dropdown-item" href="#">3rd place</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <button class="submit-btn" onclick="redirectToCategoryPage()">Submit</button>

    <!-- JavaScript -->
    <script>
        function redirectToCategoryPage() {
            window.location.href = "voteCategory.php?completedCategoryId=A1";
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
