<?php
// Start session and include required files
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Winners</title>
    <!-- Limitless Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            padding: 80px;
            background-color: #f8f9fa;
        }
        .table-container {
            margin: 20px auto;
            max-width: 800px;
        }
        .alert {
            max-width: 800px;
            margin: 20px auto;
        }
    </style>
</head>
<body>

<?php $backLink = "index.php"; include 'navbar.php'; ?>
<div class="container">
    <h1 class="text-center my-4">View Winners by Category</h1>

    <!-- Dropdown for Category Selection -->
    <div class="mb-4">
        <form id="filter-form" class="d-flex justify-content-center">
            <select id="category" name="category" class="form-select w-50" required>
                <option value="" disabled selected>Select Category</option>
                <option value="A1">A1</option>
                <option value="A2">A2</option>
                <option value="B">B</option>
                <option value="C">C</option>
                <option value="D">D</option>
            </select>
            <button type="submit" class="btn btn-primary ms-5">View Winners</button>
        </form>
    </div>

    <!-- Winners Table -->
    <div id="winners-container" class="table-container">
        <table id="winners-table" class="table table-bordered table-striped d-none">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Academic Year</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>

    <!-- Error Message -->
    <div id="error-message" class="alert alert-danger d-none"></div>
</div>

<script>
    document.getElementById('filter-form').addEventListener('submit', async function (event) {
        event.preventDefault();

        // Get selected category
        const category = document.getElementById('category').value;

        // Fetch winners data from API
        try {
            const response = await fetch(`http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/getWinners.php?category=${category}`);
            const data = await response.json();

            const winnersTable = document.getElementById('winners-table');
            const winnersContainer = document.getElementById('winners-container');
            const errorMessage = document.getElementById('error-message');

            // Clear existing data
            winnersTable.querySelector('tbody').innerHTML = '';
            errorMessage.classList.add('d-none');
            winnersTable.classList.add('d-none');

            if (data.status === 'success') {
                // Populate winners table
                data.data.forEach(winner => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${winner.Rank}</td>
                        <td>${winner.WinnerName}</td>
                        <td>${winner.WinnerEmail}</td>
                        <td>${winner.WinnerRole}</td>
                        <td>${winner.AcademicYear}</td>
                    `;
                    winnersTable.querySelector('tbody').appendChild(row);
                });

                // Show the table
                winnersTable.classList.remove('d-none');
            } else {
                // Show error message if no winners found
                errorMessage.textContent = data.message || 'No winners found.';
                errorMessage.classList.remove('d-none');
            }
        } catch (error) {
            // Handle fetch errors
            const errorMessage = document.getElementById('error-message');
            errorMessage.textContent = 'An error occurred while fetching winners data. Please try again later.';
            errorMessage.classList.remove('d-none');
        }
    });
</script>

</body>
</html>
