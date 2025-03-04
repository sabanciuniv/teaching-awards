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
    <title>Manage Admins</title>

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

    <!-- Bootstrap JS Bundle (includes Popper.js) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- jQuery -->
    <script src="assets/js/main/jquery.min.js"></script>
    <script src="assets/js/main/bootstrap.bundle.min.js"></script>

    <style>
        body {
            background-color: #f9f9f9;
            padding-top: 70px;
        }
        .search-box {
            width: 100%;
            max-width: 400px;
            margin-bottom: 15px;
        }
        /* Optional styling for pagination links */
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
    </style>
</head>

<?php
    // Using $backLink for a "Back" button in navbar
    $backLink = "adminDashboard.php"; 
    include 'navbar.php'; 
?>

<body>
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header text-white" style="background-color: #45748a;">
            <h4 class="mb-0"><i class="fa-solid fa-users-cog"></i> Manage Admins</h4>
        </div>
        <div class="card-body">
            <!-- Search and Add Admin Button -->
            <div class="d-flex justify-content-between">
                <input type="text" id="searchBox" class="form-control search-box" placeholder="Search for an admin..." style="margin-top: 15px;">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addAdminModal" style="margin-top: 15px;">
                    <i class="fa-solid fa-user-plus"></i> Add Admin
                </button>
            </div>

            <!-- Admin Table -->
            <div class="table-responsive mt-3">
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Admin Username</th>
                            <th>Role</th>
                            <th>Granted By</th>
                            <th>Granted Date</th>
                            <th>Removed By</th>
                            <th>Removed Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminTable">
                        <!-- Admin data will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION CONTROLS -->
            <nav>
                <ul class="pagination" id="paginationControls"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- ADD ADMIN MODAL -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">Add New Admin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAdminForm">
                    <div class="mb-3">
                        <label for="adminUsername" class="form-label">Username</label>
                        <input type="text" class="form-control" id="adminUsername" name="admin_username" required>
                    </div>
                    <div class="mb-3">
                        <label for="adminRole" class="form-label">Role</label>
                        <select class="form-control" id="adminRole" name="role" required>
                            <option value="Admin">Admin</option>
                            <option value="IT_Admin">IT Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Admin</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Main Script -->
<script>
$(document).ready(function () {
    // Global variables for pagination
    let allAdmins = [];
    let currentPage = 1;
    const pageSize = 9; // 9 admins per page

    // 1) Fetch Admins from API
    function fetchAdmins() {
        $.ajax({
            url: "api/getAdmins.php",
            method: "GET",
            dataType: "json",
            success: function (data) {
                // Store fetched data
                allAdmins = data || [];
                // Sort in ascending order by GrantedDate so the first added appear first
                allAdmins.sort(function(a, b) {
                    return new Date(b.GrantedDate) - new Date(a.GrantedDate);
                });
                // Initially render page 1
                renderPage(1);
            },
            error: function (xhr, status, error) {
                console.error("Error fetching admins:", error);
            }
        });
    }

    // 2) Render a given page of the (filtered) admin data
    function renderPage(page) {
        // Filter by search term
        const searchTerm = $("#searchBox").val().toLowerCase();
        let filtered = allAdmins.filter(admin => {
            const combined = (admin.AdminSuUsername + admin.Role + admin.GrantedBy + admin.GrantedDate + (admin.RemovedBy || "") + (admin.RemovedDate || "")).toLowerCase();
            return combined.includes(searchTerm);
        });

        // Calculate pagination
        const totalItems = filtered.length;
        const totalPages = Math.ceil(totalItems / pageSize);

        // Ensure page is in range
        if (page < 1) page = 1;
        if (page > totalPages) page = totalPages || 1;
        currentPage = page;

        // Slice data for current page
        const startIndex = (page - 1) * pageSize;
        const endIndex = startIndex + pageSize;
        const pageData = filtered.slice(startIndex, endIndex);

        // Build table rows
        $("#adminTable").empty();
        if (pageData.length === 0) {
            $("#adminTable").append('<tr><td colspan="7" class="text-center">No admins found</td></tr>');
        } else {
            pageData.forEach(function (admin) {
                let removedBy = admin.RemovedBy ? admin.RemovedBy : "";
                let removedDate = admin.RemovedDate ? admin.RemovedDate : "";
                let actionButtons = "";
                if (admin.RemovedDate) {
                    actionButtons = `<button class="btn btn-secondary btn-sm" disabled>Removed</button>`;
                } else {
                    actionButtons = `
                        <button class="btn btn-danger btn-sm delete-btn" data-username="${admin.AdminSuUsername}">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    `;
                }
                $("#adminTable").append(`
                    <tr>
                        <td>${admin.AdminSuUsername}</td>
                        <td>${admin.Role}</td>
                        <td>${admin.GrantedBy || ""}</td>
                        <td>${admin.GrantedDate || ""}</td>
                        <td>${removedBy}</td>
                        <td>${removedDate}</td>
                        <td>${actionButtons}</td>
                    </tr>
                `);
            });
        }

        // Render pagination controls
        renderPaginationControls(totalPages, page);
    }

    // 3) Render pagination links
    function renderPaginationControls(totalPages, current) {
        $("#paginationControls").empty();
        if (totalPages <= 1) return;

        // Previous button
        const prevDisabled = current === 1 ? "disabled" : "";
        $("#paginationControls").append(`
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" aria-label="Previous" data-page="${current - 1}">&laquo;</a>
            </li>
        `);

        // Page number buttons
        for (let i = 1; i <= totalPages; i++) {
            const active = (i === current) ? "active" : "";
            $("#paginationControls").append(`
                <li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        // Next button
        const nextDisabled = current === totalPages ? "disabled" : "";
        $("#paginationControls").append(`
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" aria-label="Next" data-page="${current + 1}">&raquo;</a>
            </li>
        `);
    }

    // 4) Handle pagination link clicks
    $(document).on("click", "#paginationControls .page-link", function (e) {
        e.preventDefault();
        const newPage = parseInt($(this).data("page"));
        if (!isNaN(newPage)) {
            renderPage(newPage);
        }
    });

    // 5) Live search
    $("#searchBox").on("keyup", function () {
        renderPage(1);
    });

    // 6) Delete Admin via AJAX
    $(document).on("click", ".delete-btn", function () {
        var username = $(this).data("username");
        if (confirm("Are you sure you want to remove this admin?")) {
            $.ajax({
                url: "api/deleteAdmin.php",
                method: "POST",
                data: { delete_admin: username },
                success: function (response) {
                    alert("Admin removed successfully!");
                    fetchAdmins();
                },
                error: function (xhr, status, error) {
                    alert("Error removing admin: " + error);
                }
            });
        }
    });

    // 7) Add Admin via AJAX
    $("#addAdminForm").on("submit", function (event) {
        event.preventDefault();
        $.ajax({
            url: "api/addAdmin.php",
            method: "POST",
            data: {
                admin_username: $("#adminUsername").val(),
                role: $("#adminRole").val()
            },
            dataType: "json",
            success: function (response) {
                if (response.status === "success") {
                    alert(response.message);
                    $("#addAdminForm")[0].reset();
                    $("#addAdminModal").modal("hide");
                    fetchAdmins();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("Error adding admin:", error);
                alert("An error occurred while adding the admin.");
            }
        });
    });

    // Initial load
    fetchAdmins();
});
</script>

</body>
</html>
