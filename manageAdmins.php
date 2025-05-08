<?php
require_once 'api/authMiddleware.php';
require_once 'api/commonFunc.php';
require_once 'database/dbConnection.php';
$pageTitle= "Manage Admins";
require_once 'api/header.php';

init_session();

// Get user from session
$user = $_SESSION['user'];

// Get their admin role
$role = getUserAdminRole($pdo, $user);

// Only allow IT_Admins
if ($role !== 'IT_Admin') {
    logUnauthorizedAccess($pdo, $user, basename(__FILE__));
    header("Location: accessDenied.php");
    exit();
}

$adminsData = getAllAdmins($pdo);
$adminsJson = json_encode($adminsData['data']);
?>


<!DOCTYPE html>
<html lang="en">
<!-- Bootstrap JS Bundle (includes Popper.js) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="assets/js/main/jquery.min.js"></script>

<style>
    /* Make the page scrollable if content is tall */
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

    /* Indicate sortable columns with a pointer and optional arrow icons */
    th.sortable {
        cursor: pointer;
        position: relative; /* so we can place arrows */
    }
    th.sortable.asc::after {
        content: ' \25B2'; /* up arrow */
        position: absolute;
        right: 8px;
        color: #ccc;
    }
    th.sortable.desc::after {
        content: ' \25BC'; /* down arrow */
        position: absolute;
        right: 8px;
        color: #ccc;
    }
</style>
<body>
<?php
    // Using $backLink for a "Back" button in navbar
    $backLink = "adminDashboard.php"; 
    include 'navbar.php'; 
?>

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
                            <!-- Add data-sort attribute and class "sortable" to each sortable column header -->
                            <th data-sort="AdminSuUsername" class="sortable">Admin Username</th>
                            <th data-sort="Role" class="sortable">Role</th>
                            <th data-sort="GrantedBy" class="sortable">Granted By</th>
                            <th data-sort="GrantedDate" class="sortable">Granted Date</th>
                            <th data-sort="RemovedBy" class="sortable">Removed By</th>
                            <th data-sort="RemovedDate" class="sortable">Removed Date</th>
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
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" aria-label="Close" style="border:none; background:none;">
                    <i class="fa-solid fa-times"></i>
                </button>
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
    const allAdmins = <?php echo $adminsJson; ?>;
</script>

<!-- Main Script -->
<script>
$(document).ready(function () {
    // Global variables for pagination and sorting
    let currentPage = 1;
    const pageSize = 9; // 9 admins per page
    let currentSortField = null;
    let currentSortDirection = "asc"; // default ascending for clicked column

    // 1) Initialize Admins
    function fetchAdmins() {
        if (!Array.isArray(allAdmins)) {
            allAdmins = [];
        }

        // If no column sort is chosen, use default sort
        if (!currentSortField) {
            allAdmins.sort(function(a, b) {
                let aStatus = a.RemovedDate ? 1 : 0;
                let bStatus = b.RemovedDate ? 1 : 0;
                if (aStatus !== bStatus) {
                    return aStatus - bStatus; // active (0) comes first
                }
                return new Date(b.GrantedDate) - new Date(a.GrantedDate);
            });
        } else {
            sortAdminsByField(currentSortField, currentSortDirection);
        }

        renderPage(1);
    }


    // 2) Sorting function by chosen column
    function sortAdminsByField(field, direction) {
        allAdmins.sort(function(a, b) {
            let valA = a[field] || "";
            let valB = b[field] || "";
            // If the field is a date, convert to Date objects
            if (field === "GrantedDate" || field === "RemovedDate") {
                valA = new Date(valA);
                valB = new Date(valB);
            }
            // For string values, use localeCompare
            if (typeof valA === "string" && typeof valB === "string") {
                let cmp = valA.localeCompare(valB);
                return direction === "asc" ? cmp : -cmp;
            } else {
                // For dates or numeric
                return direction === "asc" ? valA - valB : valB - valA;
            }
        });
    }

    // 3) Render a given page of the (filtered) admin data
    function renderPage(page) {
        const searchTerm = $("#searchBox").val().toLowerCase();
        let filtered = allAdmins.filter(admin => {
            const combined = (
                admin.AdminSuUsername +
                admin.Role +
                admin.GrantedBy +
                admin.GrantedDate +
                (admin.RemovedBy || "") +
                (admin.RemovedDate || "")
            ).toLowerCase();
            return combined.includes(searchTerm);
        });

        const totalItems = filtered.length;
        const totalPages = Math.ceil(totalItems / pageSize);

        if (page < 1) page = 1;
        if (page > totalPages) page = totalPages || 1;
        currentPage = page;

        const startIndex = (page - 1) * pageSize;
        const endIndex = startIndex + pageSize;
        const pageData = filtered.slice(startIndex, endIndex);

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
        renderPaginationControls(totalPages, page);
    }

    // 4) Render pagination links
    function renderPaginationControls(totalPages, current) {
        $("#paginationControls").empty();
        if (totalPages <= 1) return;

        const prevDisabled = current === 1 ? "disabled" : "";
        $("#paginationControls").append(`
            <li class="page-item ${prevDisabled}">
                <a class="page-link" href="#" aria-label="Previous" data-page="${current - 1}">&laquo;</a>
            </li>
        `);

        for (let i = 1; i <= totalPages; i++) {
            const active = (i === current) ? "active" : "";
            $("#paginationControls").append(`
                <li class="page-item ${active}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        const nextDisabled = current === totalPages ? "disabled" : "";
        $("#paginationControls").append(`
            <li class="page-item ${nextDisabled}">
                <a class="page-link" href="#" aria-label="Next" data-page="${current + 1}">&raquo;</a>
            </li>
        `);
    }

    // 5) Handle pagination link clicks
    $(document).on("click", "#paginationControls .page-link", function (e) {
        e.preventDefault();
        const newPage = parseInt($(this).data("page"));
        if (!isNaN(newPage)) {
            renderPage(newPage);
        }
    });

    // 6) Live search
    $("#searchBox").on("keyup", function () {
        renderPage(1);
    });

    // 7) Column header click for sorting
    $(document).on("click", "th.sortable", function () {
        const field = $(this).data("sort");
        
        // Toggle or set the sort direction
        if (currentSortField === field) {
            currentSortDirection = (currentSortDirection === "asc") ? "desc" : "asc";
        } else {
            currentSortField = field;
            currentSortDirection = "asc";
        }

        // Remove existing arrow classes from all headers
        $("th.sortable").removeClass("asc desc");
        // Add the new direction class to the clicked header
        $(this).addClass(currentSortDirection);

        sortAdminsByField(currentSortField, currentSortDirection);
        renderPage(1);
    });

    // 8) Delete Admin via AJAX
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

    // 9) Add Admin via AJAX
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
