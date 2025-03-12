<?php
session_start();
require_once 'api/authMiddleware.php';

// If the user is not logged in, redirect to the login page
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Include DB connection
require_once 'database/dbConnection.php';

// Fetch the username from session
$username = $_SESSION['user'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teaching Awards - Manage Candidates</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/app.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }
        .container {
            margin-top: 100px;
        
        }
        .card-header {
            background-color: #45748a;
            color: white;
        }
        .search-box {
            width: 300px;
        }
        .btn-remove {
            background-color: #dc3545;
            color: white;
        }
        .btn-remove:hover {
            background-color: #c82333;
        }
        .removed-badge {
            background-color: #6c757d;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php $backLink = "adminDashboard.php"; include 'navbar.php'; ?>

    <div class="container">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fa-solid fa-user-times"></i> Manage Voting Candidates</h4>
                <input type="text" id="searchBox" class="form-control search-box" placeholder="Search for a candidate...">
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Name</th>
                            <th>SuID</th>
                            <th>Role</th>
                            <th>Excluded By</th>
                            <th>Excluded At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="candidatesTable">
                        <!-- Candidates will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function () {
        function loadCandidates() {
            $.ajax({
                url: "api/fetch_candidates.php",
                method: "GET",
                dataType: "json",
                success: function (data) {
                    $("#candidatesTable").empty();
                    if (data.length === 0) {
                        $("#candidatesTable").append('<tr><td colspan="6" class="text-center">No candidates available</td></tr>');
                    } else {
                        data.forEach(function (candidate) {
                            let removedBy = candidate.excluded_by ? candidate.excluded_by : "";
                            let removedAt = candidate.excluded_at ? candidate.excluded_at : "";
                            let actionButton = "";

                            if (removedBy) {
                                actionButton = `<span class="removed-badge">Removed</span>`;
                            } else {
                                actionButton = `
                                    <button class="btn btn-remove btn-sm remove-btn" data-id="${candidate.id}">
                                        <i class="fa-solid fa-trash"></i> Remove
                                    </button>
                                `;
                            }

                            $("#candidatesTable").append(`
                                <tr>
                                    <td>${candidate.Name}</td>
                                    <td>${candidate.Mail}</td>
                                    <td>${candidate.Role}</td>
                                    <td>${removedBy}</td>
                                    <td>${removedAt}</td>
                                    <td>${actionButton}</td>
                                </tr>
                            `);
                        });
                    }
                },
                error: function (xhr, status, error) {
                    console.error("Error fetching candidates:", error);
                }
            });
        }

        // Load candidates on page load
        loadCandidates();

        // Search function
        $("#searchBox").on("keyup", function () {
            let searchTerm = $(this).val().toLowerCase();
            $("#candidatesTable tr").filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(searchTerm) > -1);
            });
        });

        // Remove candidate
        $(document).on("click", ".remove-btn", function () {
            var candidateID = $(this).data("id");

            if (confirm("Are you sure you want to remove this candidate?")) {
                $.ajax({
                    url: "api/remove_candidate.php",
                    method: "POST",
                    data: { candidateID: candidateID },
                    success: function (response) {
                        alert("Candidate removed successfully!");
                        loadCandidates();
                    },
                    error: function (xhr, status, error) {
                        console.error("Error removing candidate:", error);
                    }
                });
            }
        });
    });
    </script>
</body>
</html>
