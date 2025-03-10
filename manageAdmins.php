<?php
session_start();
require_once 'api/authMiddleware.php';

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
    <title>Manage Candidates</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Limitless Theme Styles -->
    <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
    <link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="assets/js/main/jquery.min.js"></script>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
        }
        body {
            background-color: #f9f9f9;
            padding-top: 70px;
            overflow-y: auto;
        }
        
        .search-box {
            width: 100%;
            max-width: 400px;
            margin-bottom: 15px;
        }

        .pagination {
            margin-top: 1rem;
        }
        .pagination .page-item .page-link {
            color: #45748a;
        }
        .pagination .page-item.active .page-link {
            background-color: #45748a;
            border-color: #45748a;
            color: #fff;
        }

        th.sortable {
            cursor: pointer;
            position: relative;
        }
        th.sortable.asc::after {
            content: ' \25B2';
            position: absolute;
            right: 8px;
            color: #ccc;
        }
        th.sortable.desc::after {
            content: ' \25BC';
            position: absolute;
            right: 8px;
            color: #ccc;
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

    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header text-white" style="background-color: #45748a;">
                <h4 class="mb-0"><i class="fa-solid fa-user-times"></i> Manage Candidates</h4>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <input type="text" id="searchBox" class="form-control search-box" placeholder="Search for a candidate..." style="margin-top: 15px;">
                </div>

                <div class="table-responsive mt-3">
                    <table class="table table-striped table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th data-sort="Name" class="sortable">Name</th>
                                <th data-sort="Mail" class="sortable">Email</th>
                                <th data-sort="Role" class="sortable">Role</th>
                                <th data-sort="excluded_by" class="sortable">Removed By</th>
                                <th data-sort="excluded_at" class="sortable">Removed Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="candidatesTable">
                            <!-- Candidates will be loaded here via AJAX -->
                        </tbody>
                    </table>
                </div>

                <nav>
                    <ul class="pagination" id="paginationControls"></ul>
                </nav>
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
                            let actionButton = removedBy
                                ? `<span class="removed-badge">Removed</span>`
                                : `<button class="btn btn-remove btn-sm remove-btn" data-id="${candidate.id}">
                                        <i class="fa-solid fa-trash"></i> Remove
                                   </button>`;

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
