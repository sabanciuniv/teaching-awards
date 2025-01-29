
            


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
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

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
            <div class="table-responsive mt-3" >
                <table class="table table-striped table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Admin Username</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminTable">
                        <!-- Admin data will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ADD ADMIN MODAL -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> <!-- Added modal-dialog-centered -->
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


<script>
$(document).ready(function () {
    // Fetch Admins from API
    function fetchAdmins() {
        $.ajax({
            url: "api/getAdmins.php",
            method: "GET",
            dataType: "json",
            success: function (data) {
                $("#adminTable").empty();
                if (data.length > 0) {
                    data.forEach(function (admin) {
                        $("#adminTable").append(`
                            <tr id="row-${admin.AdminSuUsername}">
                                <td>${admin.AdminSuUsername}</td>
                                <td>${admin.Role}</td>
                                <td>
                                    <button class="btn btn-danger btn-sm delete-btn" data-username="${admin.AdminSuUsername}">
                                        <i class="fa-solid fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                } else {
                    $("#adminTable").append('<tr><td colspan="3" class="text-center">No admins found</td></tr>');
                }
            },
            error: function (xhr, status, error) {
                console.error("Error fetching admins:", error);
            }
        });
    }

    // Call fetchAdmins on page load
    fetchAdmins();

    // Search Filter
    $("#searchBox").on("keyup", function () {
        var value = $(this).val().toLowerCase();
        $("#adminTable tr").filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });

    // Delete Admin via AJAX
    $(document).on("click", ".delete-btn", function () {
        var username = $(this).data("username");

        if (confirm("Are you sure you want to delete this admin?")) {
            $.ajax({
                url: "api/deleteAdmin.php",
                method: "POST",
                data: { delete_admin: username },
                success: function (response) {
                    alert("Admin deleted successfully!");
                    fetchAdmins(); // Refresh the table
                },
                error: function (xhr, status, error) {
                    alert("Error deleting admin: " + error);
                }
            });
        }
    });

    // Add Admin via AJAX
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
                    fetchAdmins(); // Refresh the table
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
});
</script>

</body>
</html>
