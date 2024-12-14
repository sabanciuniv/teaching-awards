<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabancı University Teaching Awards</title>
    <!-- Global stylesheets -->
	<link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
	<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
	<link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">
	<link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
	<!-- /global stylesheets -->


	<!-- Core JS files -->
	<script src="assets/js/jquery.min.js"></script>
	<script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/global_assets/js/main/jquery.min.js"></script>
	<script src="assets/global_assets/js/main/bootstrap.bundle.min.js"></script>
	<!-- /core JS files -->

	<!-- Theme JS files -->
	<script src="assets/js/app.js"></script>
	<script src="assets/js/custom.js"></script>
	<!-- /theme JS files -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .header {
            background-color: #003d78;
            padding: 20px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header img {
            height: 50px;
        }
        .header .title {
            font-size: 24px;
            font-weight: bold;
        }
        .container {
            display: flex;
            padding: 20px;
        }
        .menu {
            width: 300px;
            background-color: #e6f0ff;
            border-radius: 8px;
            padding: 20px;
        }
        .menu button {
            width: 100%;
            padding: 15px;
            margin: 10px 0;
            font-size: 16px;
            background-color: #4f87d7;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .menu button:hover {
            background-color: #3b6cb7;
        }
        .menu .note {
            font-size: 14px;
            color: #333;
            margin-top: -10px;
            margin-bottom: 10px;
        }
        .image-section {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .image-section img {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Sabancı University Teaching Awards</div>
        <div class="user-info">Name Surname(will be dynamic after)</div>
    </div>
    <div class="container">
        <div class="menu">
            <button onclick="alert('Create New Academic Year functionality not implemented yet.')">+ Create New Academic Year</button>
            <button onclick="alert('Manage Admin functionality not implemented yet.')">Manage Admin</button>
            <div class="note">(only for IT admins)</div>
            <button onclick="alert('Set Vote Period functionality not implemented yet.')">Set Vote Period</button>
            <button onclick="alert('Excellence in Teaching Awards by Year functionality not implemented yet.')">Excellence in Teaching Awards by Year</button>
            <button onclick="alert('Sync instructor-course functionality not implemented yet.')">Sync instructor-course</button>
            <a href="reportPage.php" style="text-decoration: none;"><button>Get Reports</button></a>

        </div>
        <div class="image-section">
            <img src="additionalImages/sabanciFoto1.jpg" alt="Sabancı Üniversitesi">
        </div>
    </div>
</body>
</html>
