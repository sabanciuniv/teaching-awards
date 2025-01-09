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
    <title>Nominate - Teaching Awards</title>

    <!-- Limitless Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>

    <!-- Custom Styles -->
    <style>
        body {
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
            margin: 0;
            padding-top: 70px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 100vh;
        }

        .card {
            background-color: var(--bs-secondary);
            color: white;
            border: 1px solid var(--bs-secondary);
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 500px;
        }

        .card-header {
            font-size: 1.3rem;
            font-weight: bold; 
            text-align: center; 
            padding: 15px; 
        }

        .form-body {
            padding: 20px;
        }

        .form-control {
            color: var(--bs-secondary);
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
    
    <?php include 'navbar.php'; ?>

    <!-- Nomination Form Card -->
    <div class="card mt-5">
        <!-- Form Title -->
        <div class="card-header bg-secondary text-white text-center">Nomination Form</div>
        <!-- Form Body -->
        <div class="form-body">
            <form action="index.php" method="post" enctype="multipart/form-data">
                <!-- Your Username -->
                <div class="mb-3">
                    <label class="form-label text-secondary">Your Username</label>
                    <input type="text" name="your_name" class="form-control border-secondary text-secondary" value="<?php echo htmlspecialchars($_SESSION['user']); ?>" readonly>
                </div>
                <!-- Nominee's Name -->
                <div class="mb-3">
                    <label class="form-label text-secondary">Nominee's Name</label>
                    <input type="text" name="nominee_name" class="form-control text-secondary border-secondary" placeholder="Enter nominee's name" required>
                </div>
                <!-- Nominee's Surname -->
                <div class="mb-3">
                    <label class="form-label text-secondary">Nominee's Surname</label>
                    <input type="text" name="nominee_surname" class="form-control text-secondary border-secondary" placeholder="Enter nominee's surname" required>
                </div>
                <!-- Upload References -->
                <div class="mb-3">
                    <label class="form-label text-secondary">Upload Reference Document</label>
                    <input type="file" name="reference_document" class="form-control text-secondary border-secondary" required>
                </div>
                <!-- Submit Button -->
                <div class="text-end">
                    <button type="submit" class="btn button-secondary bg-secondary text-white">
                        Submit <i class="icon-paperplane"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
