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
    .sticky-sync-container {
      position: fixed;
      bottom: 40px; /* Adjusted to be clearly visible */
      right: 30px; 
      z-index: 1050; 
      display: flex !important;
      align-items: center;
      justify-content: center;
    }

    /* Make the sync button visible and properly sized */
    #syncButton {
      width: 170px;  /* Adjust width */
      height: 60px;  /* Adjust height */
      font-size: 16px;  /* Ensure text remains readable */
      padding: 10px;  /* Adjust padding */
      border-radius: 8px;  /* Rounded corners */
      background-color: #ff9800;  /* Orange color */
      color: white;  /* Text color */
      border: none;
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
      transition: all 0.3s ease-in-out;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;  /* Space between icon and text */
    }

    /* Hover effect for better visibility */
    #syncButton:hover {
      background-color: #e68900; 
      transform: scale(1.05);
    }

    /* Ensure visibility on smaller screens */
    @media (max-width: 768px) {
      .sticky-sync-container {
        right: 10px;
        bottom: 10px;
      }

      #syncButton {
        width: 60px; /* Slightly smaller on mobile */
        height: 60px;
        font-size: 14px;
        padding: 10px;
      }
    }


    /* Media query to adjust the button for smaller screens */
    @media (max-width: 768px) {
      .sticky-sync-container {
        right: 15px;
        bottom: 15px;
      }
    }



</style>
</head>
<body>
  <!-- Example navbar (adjust path if needed) -->
  <?php $backLink = "adminDashboard.php"; include 'navbar.php'; ?>


  <div class="sticky-sync-container">
    <button id="syncButton" class="btn">
      <i class="fa-solid fa-sync fa-lg"></i>
      <span> Data Sync </span>
    </button>
  </div>


  <div class="container mt-4">
    <div class="card shadow">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="fa-solid fa-user-times"></i> Excluded Candidates</h4>
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
                  <th data-sort="Mail" class="sortable">Email</th>
                  <th data-sort="SU_ID" class="sortable">SuID</th>
                  <th data-sort="Role" class="sortable">Role</th>
                  <th data-sort="Sync_Date" class="sortable">Last Synced</th>
                  <th data-sort="Status" class="sortable">Status</th>
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




  <script>
    let candidates = [];
    let currentPage = 1;
    const rowsPerPage = 9;

    // Sort tracking
    let currentSortColumn = null;
    let currentSortDirection = 'asc';

    $(document).ready(function () {
      // 1) Load all candidates
      loadCandidates();

      // 2) Handle search
      $("#searchBox").on("keyup", function () {
        const searchTerm = $(this).val().toLowerCase();
        const filtered = candidates.filter(item => JSON.stringify(item).toLowerCase().includes(searchTerm));
        renderTable(filtered, 1);
        renderPaginationControls(filtered);
      });

      // 3) Column sorting
      $("th.sortable").on("click", function() {
        const column = $(this).data("sort");
        sortData(column);
      });
    });

    function loadCandidates() {
      $.ajax({
        url: "api/getCandidates.php",
        method: "GET",
        dataType: "json",
        success: function (response) {
          if (response.status === 'success') {
            candidates = response.candidates;
            renderTable(candidates, 1);
            renderPaginationControls(candidates);
          } else {
            console.error("Error:", response.message);
          }
        },
        error: function (xhr, status, error) {
          console.error("Error fetching candidates:", error);
        }
      });
    }

    function renderTable(dataArray, pageNum) {
      $("#candidatesTable").empty();

      if (!dataArray || dataArray.length === 0) {
        $("#candidatesTable").append('<tr><td colspan="7" class="text-center">No candidates found</td></tr>');
        return;
      }

      currentPage = pageNum;
      const startIndex = (pageNum - 1) * rowsPerPage;
      const endIndex = startIndex + rowsPerPage;
      const pageData = dataArray.slice(startIndex, endIndex);

      pageData.forEach(candidate => {

        const statusClass = candidate.Status === "Etkin" ? "btn-success" : "btn-danger";
        const statusText = candidate.Status === "Etkin" ? "On" : "Off";

        $("#candidatesTable").append(`
          <tr>
            <td>${candidate.Name}</td>
            <td>${candidate.Mail}</td>
            <td>${candidate.SU_ID}</td>
            <td>${candidate.Role}</td>
            <td>${candidate.Sync_Date || ""}</td>
            <td>
              <button class="btn ${statusClass} toggle-status" data-id="${candidate.id}" data-status="${candidate.Status}">
                ${statusText}
              </button>
            </td>
          </tr>
        `);
      });

        // Add click event to toggle buttons
        $(".toggle-status").on("click", function () {
              const button = $(this);
              const candidateID = button.data("id");
              const currentStatus = button.data("status");

              const newStatus = currentStatus === "Etkin" ? "İşten ayrıldı" : "Etkin";
              const newClass = newStatus === "Etkin" ? "btn-success" : "btn-danger";
              const newText = newStatus === "Etkin" ? "On" : "Off";

              // Update button UI immediately
              button.removeClass("btn-success btn-danger").addClass(newClass).text(newText);
              button.data("status", newStatus);

              // Send update request to server
              $.ajax({
                  url: "api/updateCandidateStatus.php",
                  method: "POST",
                  data: { candidateID: candidateID, status: newStatus },
                  dataType: "json",
                  success: function (response) {
                      if (!response.success) {
                          alert("Error updating status: " + response.message);
                          // Revert UI if error occurs
                          button.removeClass(newClass).addClass(currentStatus === "Etkin" ? "btn-success" : "btn-danger");
                          button.text(currentStatus === "Etkin" ? "On" : "Off");
                          button.data("status", currentStatus);
                      }
                  },
                  error: function (xhr, status, error) {
                      console.error("Error updating status:", error);
                      alert("An error occurred while updating the status.");
                  }
              });


              // If status is changed to "Off", add to Exception_Table
              if (newStatus === "İşten ayrıldı") {
                $.ajax({
                    url: "api/add_excluded_candidate.php",
                    method: "POST",
                    data: { candidateID: candidateID },
                    dataType: "json",
                    success: function (response) {
                        if (!response.success) {
                            alert("Error excluding candidate: " + response.error);
                            // Revert UI if error occurs
                            button.removeClass(newClass).addClass("btn-success").text("On");
                            button.data("status", "Etkin");
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Error excluding candidate:", error);
                        alert("An error occurred while excluding the candidate.");
                        // Revert UI
                        button.removeClass(newClass).addClass("btn-success").text("On");
                        button.data("status", "Etkin");
                    }
                });
              } 
          });                   

        $("th.sortable").removeClass("asc desc");
          if (currentSortColumn) {
            $(`th[data-sort="${currentSortColumn}"]`).addClass(currentSortDirection);
        }
    }

  function renderPaginationControls(dataArray) {
      const totalRows = dataArray.length;
      const totalPages = Math.ceil(totalRows / rowsPerPage);
      const paginationContainer = $("#paginationControls");
      paginationContainer.empty();

      if (totalPages <= 1) return; // No pagination needed

      let pageItems = [];

      // "First" and "Prev" buttons
      if (currentPage > 1) {
          pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="1">« First</a></li>`);
          pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${currentPage - 1}">‹ Prev</a></li>`);
      }

      let maxVisiblePages = 5; // Adjust for better appearance

      if (totalPages <= maxVisiblePages) {
          // Show all pages if small number
          for (let i = 1; i <= totalPages; i++) {
              pageItems.push(`<li class="page-item ${i === currentPage ? 'active' : ''}">
                                <a class="page-link" href="#" data-page="${i}">${i}</a>
                              </li>`);
          }
      } else {
          // Show first few pages, ellipsis, middle, ellipsis, last few pages
          if (currentPage > 3) {
              pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
              if (currentPage > 4) pageItems.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
          }

          let startPage = Math.max(2, currentPage - 2);
          let endPage = Math.min(totalPages - 1, currentPage + 2);

          for (let i = startPage; i <= endPage; i++) {
              pageItems.push(`<li class="page-item ${i === currentPage ? 'active' : ''}">
                                <a class="page-link" href="#" data-page="${i}">${i}</a>
                              </li>`);
          }

          if (currentPage < totalPages - 3) {
              if (currentPage < totalPages - 4) pageItems.push(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
              pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">${totalPages}</a></li>`);
          }
      }

      // "Next" and "Last" buttons
      if (currentPage < totalPages) {
          pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${currentPage + 1}">Next ›</a></li>`);
          pageItems.push(`<li class="page-item"><a class="page-link" href="#" data-page="${totalPages}">Last »</a></li>`);
      }

      paginationContainer.html(pageItems.join(""));

      // Click event for pagination
      $(".page-link").on("click", function (e) {
          e.preventDefault();
          const selectedPage = parseInt($(this).attr("data-page"));
          if (!isNaN(selectedPage)) {
              renderTable(dataArray, selectedPage);
              renderPaginationControls(dataArray);
          }
      });
  }


    function sortData(column) {
      if (currentSortColumn === column) {
        currentSortDirection = (currentSortDirection === 'asc') ? 'desc' : 'asc';
      } else {
        currentSortColumn = column;
        currentSortDirection = 'asc';
      }

      candidates.sort((a, b) => {
        const valA = (a[column] || "").toString().toLowerCase();
        const valB = (b[column] || "").toString().toLowerCase();
        return valA.localeCompare(valB) * (currentSortDirection === 'asc' ? 1 : -1);
      });

      renderTable(candidates, 1);
      renderPaginationControls(candidates);
    }


    $(document).ready(function () {
      // Load candidates when the page loads
      loadCandidates();

      $("#syncButton").on("click", function () {
        const syncButton = $(this);
        syncButton.prop("disabled", true);

        // Keep both icon and text visible during sync
        syncButton.html('<i class="fa-solid fa-sync fa-spin"></i> Synchronizing...');

        $.ajax({
            url: "api/synchronizeCandidates.php",
            method: "POST",
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    alert("Synchronization successful!");
                    loadCandidates();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("Synchronization error:", error);
                alert("An error occurred during synchronization.");
            },
            complete: function () {
                // Restore original text after syncing
                syncButton.prop("disabled", false);
                syncButton.html('<i class="fa-solid fa-sync"></i> Data Sync');
            }
        });
    });
  });

  </script>
</body>
</html>
