<?php
session_start();
require_once 'api/authMiddleware.php';  // Adjust if needed
require_once 'database/dbConnection.php'; // PDO connection

// If not logged in, redirect (optional)
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['user'];  // Current user
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Teaching Awards - Excluded Candidates</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Bootstrap CSS (CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Optional CSS -->
  <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">

  <!-- FontAwesome (CDN) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <!-- jQuery (CDN) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- Bootstrap Bundle (CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    /* Make the entire page scrollable */
    html, body {
      margin: 0;
      padding: 0;
      height: 100%;
      overflow-y: auto;
    }
    body {
      font-family: Arial, sans-serif;
      background-color: #f9f9f9;
      padding-top: 70px; /* Adjust if you have a fixed navbar */
    }
    .card-header {
      background-color: #45748a;
      color: #fff;
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
      user-select: none;
    }
    th.sortable.asc::after {
      content: ' \25B2'; /* Up arrow */
      position: absolute;
      right: 8px;
      color: #ccc;
    }
    th.sortable.desc::after {
      content: ' \25BC'; /* Down arrow */
      position: absolute;
      right: 8px;
      color: #ccc;
    }
  </style>
</head>
<body>
  <!-- Example navbar (adjust path if needed) -->
  <?php $backLink = "adminDashboard.php"; include 'navbar.php'; ?>

  <div class="container mt-4">
    <div class="card shadow">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fa-solid fa-user-times"></i> Excluded Candidates</h4>
        <!-- "Add Excluded Candidate" button triggers the modal -->
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExcludedCandidateModal">
          <i class="fa-solid fa-plus"></i> Add Excluded Candidate
        </button>
      </div>
      <div class="card-body">
        <!-- Search box -->
        <input type="text" id="searchBox" class="form-control search-box" placeholder="Search for a candidate...">

        <!-- Table: Shows ONLY excluded candidates -->
        <div class="table-responsive mt-3">
          <table class="table table-striped table-bordered">
            <thead class="table-dark">
              <tr>
                <!-- Add data-sort attributes for sorting -->
                <th data-sort="Name" class="sortable">Name</th>
                <th data-sort="Mail" class="sortable">SuID</th>
                <th data-sort="Role" class="sortable">Role</th>
                <th data-sort="excluded_by" class="sortable">Excluded By</th>
                <th data-sort="excluded_at" class="sortable">Excluded At</th>
              </tr>
            </thead>
            <tbody id="candidatesTable">
              <!-- Excluded candidates loaded via AJAX -->
            </tbody>
          </table>
        </div>

        <!-- Pagination controls -->
        <nav>
          <ul class="pagination" id="paginationControls"></ul>
        </nav>
      </div>
    </div>
  </div>

  <!-- Modal: Add Excluded Candidate -->
  <div class="modal fade" id="addExcludedCandidateModal" tabindex="-1" aria-labelledby="addExcludedCandidateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addExcludedCandidateModalLabel">Add Excluded Candidate</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="addExcludedCandidateForm">
            <div class="mb-3">
              <label for="candidateID" class="form-label">Candidate ID</label>
              <!-- Must be the auto-increment 'id' from Candidate_Table -->
              <input type="number" class="form-control" id="candidateID" name="candidateID" required>
            </div>
            <button type="submit" class="btn btn-primary">Add to Exclusion</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  <!-- End Modal -->

  <script>
    // Global array to store excluded candidates
    let excludedCandidates = [];
    let currentPage = 1;
    const rowsPerPage = 9;

    // Sort tracking
    let currentSortColumn = null;
    let currentSortDirection = 'asc';

    $(document).ready(function () {
      // 1) Load excluded candidates
      loadExcludedCandidates();

      // 2) Handle search
      $("#searchBox").on("keyup", function () {
        const searchTerm = $(this).val().toLowerCase();
        // Filter in place, then re-render from page 1
        const filtered = excludedCandidates.filter(function (item) {
          return JSON.stringify(item).toLowerCase().indexOf(searchTerm) !== -1;
        });
        renderTable(filtered, 1);
        renderPaginationControls(filtered);
      });

      // 3) Add Excluded Candidate
      $("#addExcludedCandidateForm").on("submit", function(e) {
        e.preventDefault();
        const candidateID = $("#candidateID").val();

        // Do not exclude if candidate already in table
        if (excludedCandidates.some(c => c.id == candidateID)) {
          alert("This candidate is already excluded!");
          return;
        }

        $.ajax({
          url: "api/add_excluded_candidate.php",
          method: "POST",
          data: { candidateID: candidateID },
          dataType: "json",
          success: function(response) {
            if (response.success) {
              alert("Candidate excluded successfully!");
              $("#addExcludedCandidateModal").modal('hide');
              $("#candidateID").val('');
              loadExcludedCandidates(); // Refresh table
            } else {
              alert("Error: " + (response.error || "Unable to exclude candidate"));
            }
          },
          error: function(xhr, status, error) {
            console.error("Error excluding candidate:", error);
            alert("An error occurred while excluding the candidate. Check console or server logs.");
          }
        });
      });

      // 4) Column sorting
      $("th.sortable").on("click", function() {
        const column = $(this).data("sort");
        sortData(column);
      });
    });

    // Fetch only excluded candidates
    function loadExcludedCandidates() {
      $.ajax({
        url: "api/fetchCandidate.php", // This returns only excluded candidates
        method: "GET",
        dataType: "json",
        success: function (data) {
          if (!Array.isArray(data)) data = [];
          excludedCandidates = data;
          // Reset sort
          currentSortColumn = null;
          currentSortDirection = 'asc';
          // Render from page 1
          renderTable(excludedCandidates, 1);
          renderPaginationControls(excludedCandidates);
        },
        error: function (xhr, status, error) {
          console.error("Error fetching excluded candidates:", error);
        }
      });
    }

    // Render table rows for a given array of data, starting at given page
    function renderTable(dataArray, pageNum) {
      $("#candidatesTable").empty();

      if (!dataArray || dataArray.length === 0) {
        $("#candidatesTable").append('<tr><td colspan="5" class="text-center">No excluded candidates found</td></tr>');
        return;
      }

      currentPage = pageNum;
      const startIndex = (pageNum - 1) * rowsPerPage;
      const endIndex = startIndex + rowsPerPage;
      const pageData = dataArray.slice(startIndex, endIndex);

      pageData.forEach(function (candidate) {
        const excludedBy = candidate.excluded_by || "";
        const excludedAt = candidate.excluded_at || "";
        $("#candidatesTable").append(`
          <tr>
            <td>${candidate.Name}</td>
            <td>${candidate.Mail}</td>
            <td>${candidate.Role}</td>
            <td>${excludedBy}</td>
            <td>${excludedAt}</td>
          </tr>
        `);
      });

      // Update column headers with sort arrow
      $("th.sortable").removeClass("asc desc");
      if (currentSortColumn) {
        const th = $(`th[data-sort="${currentSortColumn}"]`);
        th.addClass(currentSortDirection);
      }
    }

    // Render pagination controls
    function renderPaginationControls(dataArray) {
      const totalRows = dataArray.length;
      const totalPages = Math.ceil(totalRows / rowsPerPage);
      const paginationContainer = $("#paginationControls");
      paginationContainer.empty();

      if (totalPages <= 1) return; // No need for pagination if only 1 page

      for (let i = 1; i <= totalPages; i++) {
        const li = $(`
          <li class="page-item ${i === currentPage ? 'active' : ''}">
            <a class="page-link" href="#">${i}</a>
          </li>
        `);
        li.on("click", function(e) {
          e.preventDefault();
          renderTable(dataArray, i);
          renderPaginationControls(dataArray);
        });
        paginationContainer.append(li);
      }
    }

    // Sort data in excludedCandidates by given column
    function sortData(column) {
      if (currentSortColumn === column) {
        // Toggle direction
        currentSortDirection = (currentSortDirection === 'asc') ? 'desc' : 'asc';
      } else {
        currentSortColumn = column;
        currentSortDirection = 'asc';
      }

      excludedCandidates.sort((a, b) => {
        const valA = (a[column] || "").toString().toLowerCase();
        const valB = (b[column] || "").toString().toLowerCase();
        if (valA < valB) return (currentSortDirection === 'asc') ? -1 : 1;
        if (valA > valB) return (currentSortDirection === 'asc') ? 1 : -1;
        return 0;
      });

      // Re-render from page 1
      renderTable(excludedCandidates, 1);
      renderPaginationControls(excludedCandidates);
    }
  </script>
</body>
</html>
