<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nominate - Teaching Awards</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- Custom Styles -->
    <style>
        body {
            background-color: #f9f9f9;
            font-family: Arial, sans-serif;
        }

        .container {
            margin-top: 50px;
            max-width: 600px;
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header img {
            max-height: 50px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
        }

        label {
            font-size: 1rem;
            font-weight: bold;
            margin-top: 15px;
        }

        .form-control {
            border-radius: 10px;
            padding: 10px;
            background-color: #f3f3f3;
        }

        .upload-box {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            background-color: #f9f9f9;
            margin-top: 15px;
        }

        .upload-box:hover {
            background-color: #f1f1f1;
            cursor: pointer;
        }

        .upload-box p {
            font-size: 0.9rem;
            color: #777;
        }

        .btn-submit {
            background-color: #f4b4b4;
            color: #333;
            border: none;
            font-weight: bold;
            border-radius: 8px;
            padding: 10px 20px;
            width: 100%;
        }

        .btn-submit:hover {
            background-color: #e49a9a;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header text-center">
            <img src="assets/images/screenshots/sabancilogo.png" alt="Sabanci University Logo">
            <h1>TEACHING AWARDS</h1>
        </div>

        <!-- Form -->
        <form action="nominate_submit.php" method="post" enctype="multipart/form-data">
            <!-- Your Name -->
            <label for="your-name">Your NAME</label>
            <input type="text" id="your-name" name="your_name" class="form-control" required>

            <!-- Your Surname -->
            <label for="your-surname">Your SURNAME</label>
            <input type="text" id="your-surname" name="your_surname" class="form-control" required>

            <!-- Nominee's Name -->
            <label for="nominee-name">Nominee's NAME</label>
            <input type="text" id="nominee-name" name="nominee_name" class="form-control" required>

            <!-- Nominee's Surname -->
            <label for="nominee-surname">Nominee's SURNAME</label>
            <input type="text" id="nominee-surname" name="nominee_surname" class="form-control" required>

            <!-- Upload Document -->
            <label for="reference-doc">UPLOAD REFERENCE DOCUMENT</label>
            <div class="upload-box">
                <input type="file" id="reference-doc" name="reference_doc" class="form-control-file" style="display: none;" required>
                <p>Drop your document here, or <span class="text-primary">click to browse</span></p>
                <p><i class="bi bi-upload"></i></p>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-submit mt-4">SUBMIT</button>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle click event for upload box
        document.querySelector('.upload-box').addEventListener('click', function() {
            document.getElementById('reference-doc').click();
        });

    </script>
</body>
</html>
