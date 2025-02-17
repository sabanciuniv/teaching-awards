<?php
// Start session
session_start();

// Include DB connection (adjust the path as needed)
require_once __DIR__ . '/database/dbConnection.php';

// Fetch all available years from the AcademicYear_Table to populate the dropdown
try {
    $stmtYears = $pdo->prepare("SELECT YearID, Academic_year FROM AcademicYear_Table ORDER BY YearID DESC");
    $stmtYears->execute();
    $academicYears = $stmtYears->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Handle the error in a way suitable for your environment
    die("Error fetching academic years: " . $e->getMessage());
}
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

<?php 
// Include your navbar; adjust the path or remove if not needed
$backLink = isset($_SESSION['previous_page']) && $_SESSION['previous_page'] === 'adminDashboard.php'
    ? "adminDashboard.php"
    : "index.php";

// Clear referrer after use
unset($_SESSION['previous_page']);


include 'navbar.php'; 
?>

<div class="container">
    <h1 class="text-center my-4">View Winners by Category &amp; Year</h1>

    <!-- Form for Category & Year Selection -->
    <div class="mb-4">
        <form id="filter-form" class="d-flex justify-content-center">
            <!-- Year Dropdown -->
            <select id="year" name="year" class="form-select w-25 me-3" required>
                <option value="" disabled selected>Select Year</option>
                <?php foreach($academicYears as $year): ?>
                    <option value="<?php echo $year['YearID']; ?>">
                        <?php echo htmlspecialchars($year['Academic_year']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Category Dropdown -->
            <select id="category" name="category" class="form-select w-25 me-5" required>
                <option value="" disabled selected>Select Category</option>
                <option value="1">A1</option>
                <option value="2">A2</option>
                <option value="3">B</option>
                <option value="4">C</option>
                <option value="5">D</option>
            </select>

            <button type="submit" class="btn btn-primary">View Winners</button>
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

    <!-- Error/Message Display -->
    <div id="error-message" class="alert alert-danger d-none"></div>
</div>

<script>
    document.getElementById('filter-form').addEventListener('submit', async function (event) {
        event.preventDefault();

        const year = document.getElementById('year').value;
        const category = document.getElementById('category').value;

        if (!year || !category) {
            alert('Please select both a year and a category.');
            return;
        }

        try {
            const response = await fetch(`fetchWinners.php?year=${year}&category=${category}`, {
                method: 'GET',
                headers: { 'Content-Type': 'application/json' },
            });

            const data = await response.json();

            const winnersTable = document.getElementById('winners-table');
            const tableBody = winnersTable.querySelector('tbody');
            const errorMessage = document.getElementById('error-message');

            // Clear previous data
            tableBody.innerHTML = '';
            errorMessage.classList.add('d-none');
            winnersTable.classList.add('d-none');

            if (data.error) {
                errorMessage.textContent = data.error;
                errorMessage.classList.remove('d-none');
                return;
            }

            if (data.message) {
                // Show any message (e.g. "No votes found")
                errorMessage.textContent = data.message;
                errorMessage.classList.remove('d-none');
                return;
            }

            if (data.winners && data.winners.length > 0) {
                // Populate the winners table
                data.winners.forEach(winner => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${winner.rank || 'N/A'}</td>
                        <td>${winner.candidate_name || 'N/A'}</td>
                        <td>${winner.candidate_email || 'N/A'}</td>
                        <td>${winner.candidate_role || 'N/A'}</td>
                        <td>${winner.Academic_year || 'N/A'}</td>
                    `;
                    tableBody.appendChild(row);
                });

                winnersTable.classList.remove('d-none');
            }
        } catch (error) {
            // Handle errors
            console.error('Error fetching winners:', error);
            const errorMessage = document.getElementById('error-message');
            errorMessage.textContent = 'An error occurred while fetching winners data. Please try again later.';
            errorMessage.classList.remove('d-none');
        }
    });
</script>

</body>
</html>