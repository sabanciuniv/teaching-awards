<?php
session_start();
if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Teaching Awards - Sabancı University</title>

    <!-- Limitless Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">


    <style>
        /* General Page Styles */
        html, body {
            height: 100%;
            margin: 0;
            overflow-y: auto; /* Enables vertical scrolling */
            overflow-x: hidden; /* Prevents horizontal scrolling */
        }
        body {
            margin: 0;
            padding-top: 80px;
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
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
            padding: 10px;
            border-radius: 8px;
            margin: 20px 0;
        }

        /* Submit Button */
        .submit-btn {
            position: fixed;
            bottom: 20px;
            right: 30px;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        .submit-btn:hover {
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
   <nav class="navbar navbar-dark navbar-expand-lg fixed-top bg-secondary">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <!-- Back Arrow -->
                <a href="voteCategory.php" class="text-white" style="text-decoration:none; font-size:1.2rem;">
                    <i class="fas fa-arrow-left me-3"></i>
                </a>
                <!-- Logo and Title -->
                <a href="voteScreen_A2.php" class="navbar-brand d-flex align-items-center ms-5">
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


    <!-- Content Section -->
    <div class="content container">
		<!-- Award Category -->
		<div class="award-category bg-secondary text-white">
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
    <button class="submit-btn btn-secondary" onclick="redirectToCategoryPage()">Submit</button>

    <!-- JavaScript -->
    <script>
        function redirectToCategoryPage() {
            window.location.href = "voteCategory.php?completedCategoryId=A1";
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
