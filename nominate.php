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

    <style>
        body {
            background-color: #f5f7fa;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
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
    <!-- Nomination Form Card -->
    <div class="card">
        <!-- Form Title -->
        <div class="form-title">Nomination Form</div>

        <!-- Form Body -->
        <form action="nominate_submit.php" method="post" enctype="multipart/form-data">
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
    <script src="assets/js/app.js"></script>
</body>
</html>
