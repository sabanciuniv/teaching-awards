<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting Category</title>

    <!-- Global stylesheets -->
    <link href="https://fonts.googleapis.com/css?family=Roboto:400,300,100,500,700,900" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">
    <!-- /global stylesheets -->

    <!-- Core JS files -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/global_assets/js/main/jquery.min.js"></script>
    <script src="assets/global_assets/js/main/bootstrap.bundle.min.js"></script>
    <!-- /core JS files -->

    <!-- Theme JS files -->
    <script src="assets/js/app.js"></script>
    <!-- /theme JS files -->

    <style>
        body {
            background-color: #f9f9f9;
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .form-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .form-box {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 30px;
            max-width: 400px;
            width: 100%;
        }

        .form-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
            color: #333;
        }

        .btn-submit {
            background-color: #4caf50;
            color: #fff;
            font-size: 1rem;
            font-weight: bold;
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-submit:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="page-content">
        <div class="content-wrapper">
            <div class="form-container">
                <div class="form-box">
                    <h2 class="form-title">Select a Voting Category</h2>
                    <form action="submitVote.php" method="post">
                        <div class="form-group">
                            <div class="dropdown">
                                <button class="btn btn-indigo dropdown-toggle form-control" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    Select a category
                                </button>
                                <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                    <a class="dropdown-item" href="voteScreen_A1.php">Birinci Sınıf Üniversite Derslerine Katkı Ödülü 1 (Küçük sınıf dersleri)</a>
                                    <a class="dropdown-item" href="voteScreen_A2.php">Birinci Sınıf Üniversite Derslerine Katkı Ödülü 2 (Amfi dersleri)</a>
                                    <button class="dropdown-item" type="button" onclick="setCategory('B')">Yılın Mezunları Ödülü</button>
                                    <button class="dropdown-item" type="button" onclick="setCategory('C')">Temel Geliştirme Yılı Öğretim Görevlisi Ödülü</button>
                                    <button class="dropdown-item" type="button" onclick="setCategory('D')">Birinci Sınıf Eğitim Asistanı Ödülü</button>
                                    <button class="dropdown-item" type="button" onclick="setCategory('F')">Eğitim ve Öğrenim Öğrenci Geribildirimi (SFTL) Ödülü</button>
                                    <button class="dropdown-item" type="button" onclick="setCategory('G')">Onur Ödülü</button>
                                </div>
                                <input type="hidden" id="category" name="category" required>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function setCategory(value) {
            document.getElementById('category').value = value;
            document.getElementById('dropdownMenuButton').innerText = value; // Update button text
        }
    </script>
</body>
</html>
