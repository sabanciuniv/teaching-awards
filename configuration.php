<?php
session_start();
require_once 'api/authMiddleware.php';  
require_once 'database/dbConnection.php';

// If not logged in, redirect 
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);


$username = $_SESSION['user'];  // Current user

// Fetch academic years
try {
  $stmt = $pdo->query("SELECT YearID, Academic_year FROM AcademicYear_Table ORDER BY Academic_year DESC LIMIT 1");
  $currentYear = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$currentYear) {
      throw new Exception("No academic year found.");
  }
} catch (PDOException $e) {
  die("<strong style='color:red;'>SQL Error:</strong> " . $e->getMessage());
}

$currentYearID = $currentYear['YearID'];  // Use this in the query
$currentAcademicYear = $currentYear['Academic_year'];  // Display this in UI

// Prepare SQL query using YearID (not Academic_year)
$stmt = $pdo->prepare("
SELECT c.id, c.SU_ID, c.Name, c.Mail, c.Role, c.Sync_Date, c.Status,
      GROUP_CONCAT(DISTINCT cat.CategoryCode SEPARATOR ', ') AS Categories,
      GROUP_CONCAT(DISTINCT CONCAT(co.Subject_Code, ' ', co.Course_Number) SEPARATOR ', ') AS Courses
FROM Candidate_Table c
LEFT JOIN Candidate_Course_Relation cc ON c.id = cc.CandidateID
LEFT JOIN Category_Table cat ON cc.CategoryID = cat.CategoryID
LEFT JOIN Courses_Table co ON cc.CourseID = co.CourseID
WHERE (cc.Academic_Year = :academicYear OR cc.Academic_Year IS NULL)
GROUP BY c.id
ORDER BY c.Name ASC;
");


$stmt->bindParam(':academicYear', $currentYearID, PDO::PARAM_INT);
$stmt->execute();
$candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<script>
// Pass PHP data directly to JavaScript
let allCandidates = <?php echo json_encode($candidates); ?>;
let currentAcademicYear = <?php echo json_encode($currentAcademicYear); ?>;
</script>

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
      padding-top: 20px; /* Adjust if you have a fixed navbar */
    }
    .card-header {
      background-color: #45748a;
      color: #fff;
    }
    .search-box {
      width: 100%;
      padding: 10px;
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

    th.sortable::after {
      content: ' \25B2'; /* Default to up arrow */
      position: absolute;
      right: 8px;
      color: #ccc;
      opacity: 0.3; /* Make it light when not active */
    }

    /* When active (ascending) */
    th.sortable.asc::after {
      content: ' \25B2'; /* Up arrow */
      color: #000;
      opacity: 1;
    }

    /* When active (descending) */
    th.sortable.desc::after {
      content: ' \25BC'; /* Down arrow */
      color: #000;
      opacity: 1;
    }

    .sticky-sync-container {
      position: fixed;
      bottom: 40px;
      right: 30px;
      z-index: 1050;
      display: flex !important;
      flex-direction: column;
      align-items: flex-end;
      gap: 10px;
    }

    /* Make the sync button visible and properly sized */
    #syncButton {
      height: 60px;
      font-size: 16px;
      padding: 10px 20px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
      border-radius: 8px;  /* Rounded corners */
      background-color: #ff9800;  /* Orange color */
      color: white;  /* Text color */
      border: none;
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
      transition: all 0.3s ease-in-out;
      display: flex;
      align-items: center;
      justify-content: center;
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

    .container{
      max-width: 95%;
      width: 95%;
      margin-left: auto;
      margin-right: auto;
    }

    /* Make table use full width */
    .table {
      width: 100%;
      table-layout: fixed;
    }

    /* Enable horizontal scrolling for smaller screens */
    .table-responsive {
      width: 100%;
      overflow-x: auto;
    }

    /* Column width specifications */
    .table th:nth-child(1) { width: 15%; } /* Name */
    .table th:nth-child(2) { width: 25%; } /* Email */
    .table th:nth-child(3) { width: 10%; }  /* SuID */
    .table th:nth-child(4) { width: 8%; }  /* Role */
    .table th:nth-child(5) { width: 12%; } /* Categories */
    .table th:nth-child(6) { width: 22%; } /* Courses */
    .table th:nth-child(7) { width: 10%; } /* Last Synced */
    .table th:nth-child(8) { width: 8%; }  /* Status */

    /* Prevent text overflow in table cells */
    .table td {
      word-break: break-word;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    /* Set minimum widths for critical columns */
    .table th:nth-child(1),
    .table td:nth-child(1) {
      min-width: 150px; /* Name */
    }

    .table th:nth-child(2),
    .table td:nth-child(2) {
      min-width: 180px; /* Email */
    }

    .table th:nth-child(4),
    .table td:nth-child(4) {
      white-space: nowrap;
      text-align: center;
    }

    .table th:nth-child(6),
    .table td:nth-child(6) {
      min-width: 200px; /* Courses */
    }

    /* Format date column */
    .table th:nth-child(7),
    .table td:nth-child(7) {
      white-space: nowrap;
    }

</style>
</head>
<body>
  <!-- Example navbar (adjust path if needed) -->
  <?php $backLink = "adminDashboard.php"; include 'navbar.php'; ?>


  <div class="sticky-sync-container">

    <button id="viewLogsBtn" class="btn btn-secondary ms-2">
      <i class="fa-solid fa-file-alt"></i> View Logs
    </button>
    <button id="syncButton" class="btn">
      <i class="fa-solid fa-sync fa-lg"></i>
      <span> Data Sync </span>
    </button>
  </div>

  
  <div class="container mt-4">
    <div class="card-body">
      <!-- Current Academic Year -->
      <?php if ($currentAcademicYear): ?>
          <?php 
          // +1 logic for current academic year
          $displayCurrentYear = $currentAcademicYear . '-' . ($currentAcademicYear + 1);
          ?>
          <div class="alert alert-success" style="margin-top: 15px;">
              <h5>Current Academic Year: <strong><?= $displayCurrentYear; ?></strong></h5>
          </div>
          
      <?php else: ?>
          <div class="alert alert-danger">No Academic Year Found</div>
      <?php endif; ?>


      <div class="card-header d-flex justify-content-between align-items-center bg-dark text-white">
          <h5 class="mb-0"><i class="fa-solid fa-user-times"></i> Excluded Candidates Table</h5>
      </div>



      <div class="card-body">
        <!-- Search box -->
        <input type="text" id="searchBox" class="form-control search-box" placeholder="Search for a candidate...">

        <!-- Table: Shows ONLY excluded candidates -->
        <div class="table-responsive mt-3">
          <table class="table table-striped table-bordered">
          <thead class="table-dark">
          <tr>
            <th class="sortable" data-column="Name">Name</th>
            <th class="sortable" data-column="Mail">Email</th>
            <th class="sortable" data-column="SU_ID">SuID</th>
            <th class="sortable" data-column="Role">Role</th>
            <th class="sortable" data-column="Categories">Categories</th> 
            <th class="sortable" data-column="Courses">Courses</th>
            <th class="sortable" data-column="Sync_Date">Last Synced</th>
            <th class="sortable" data-column="Status">Status</th>
          </tr>
      </thead>
      <tbody id="candidatesTable">
          <?php foreach ($candidates as $candidate): ?>
              <tr>
                  <td><?= htmlspecialchars($candidate['Name']) ?></td>
                  <td><?= htmlspecialchars($candidate['Mail']) ?></td>
                  <td><?= htmlspecialchars($candidate['SU_ID']) ?></td>
                  <td><?= htmlspecialchars($candidate['Role']) ?></td>
                  <td><?= htmlspecialchars($candidate['Categories'] ?: '-') ?></td>
                  <td><?= htmlspecialchars($candidate['Courses'] ?: '-') ?></td>
                  <td><?= htmlspecialchars($candidate['Sync_Date']) ?></td>
                  <td>
                      <button class="btn <?= ($candidate['Status'] === 'Etkin') ? 'btn-success' : 'btn-danger'; ?>">
                          <?= ($candidate['Status'] === 'Etkin') ? 'On' : 'Off'; ?>
                      </button>
                  </td>
              </tr>
          <?php endforeach; ?>
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
    let currentPage = 1;
    const rowsPerPage = 7;

    // Sort tracking
    let currentSortColumn = null;
    let currentSortDirection = 'asc';

    $(document).ready(function () {


      $("th.sortable").on("click", function () {
        const column = $(this).data("column");

        // Remove previous sort indicators
        $("th.sortable").removeClass("asc desc");

        // Toggle sort direction
        if (currentSortColumn === column) {
            currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortDirection = 'asc';
        }

        currentSortColumn = column;

        // Add the appropriate sort indicator
        $(this).addClass(currentSortDirection);

        sortData(column);
    });
      // Use allCandidates directly instead of loading via AJAX
      renderTable(allCandidates, 1);
      renderPaginationControls(allCandidates);

      // Search functionality
      $("#searchBox").on("keyup", function() {
        const searchTerm = $(this).val().toLowerCase();
        const filteredCandidates = allCandidates.filter(candidate => 
          candidate.Name.toLowerCase().includes(searchTerm) || 
          candidate.Mail.toLowerCase().includes(searchTerm) || 
          candidate.SU_ID.toLowerCase().includes(searchTerm) ||
          (candidate.Courses && candidate.Courses.toLowerCase().includes(searchTerm))
        );
        renderTable(filteredCandidates, 1);
        renderPaginationControls(filteredCandidates);
      });
    });

    // Update your renderTable function to work with the data structure from PHP
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
            const categoryDisplay = candidate.Categories ||"-";
            const coursesDisplay = candidate.Courses ||"-";
            
            $("#candidatesTable").append(`
              <tr>
                <td>${candidate.Name}</td>
                <td>${candidate.Mail}</td>
                <td>${candidate.SU_ID}</td>
                <td>${candidate.Role}</td>
                <td>${categoryDisplay}</td>
                <td>${coursesDisplay}</td>
                <td>${candidate.Sync_Date || ""}</td>
                <td>
                  <button class="btn ${statusClass} toggle-status" data-id="${candidate.id}" data-status="${candidate.Status}">
                    ${statusText}
                  </button>
                </td>
              </tr>
            `);
        });
        
        $(".toggle-status").on("click", function () {
          const button = $(this);
          const candidateID = button.data("id");           // Get candidate ID
          const currentStatus = button.data("status");     // Get current status 

          const newStatus = currentStatus === "Etkin" ? "İşten ayrıldı" : "Etkin";  // Toggle status
          const newClass = newStatus === "Etkin" ? "btn-success" : "btn-danger";   // Change button color
          const newText = newStatus === "Etkin" ? "On" : "Off";                    // Change button text

          // Immediately update button UI
          button.removeClass("btn-success btn-danger").addClass(newClass).text(newText);
          button.data("status", newStatus);  // Update data-status

          // Send updated status to server
          $.ajax({
              url: "api/updateCandidateStatus.php",  // Your backend script
              method: "POST",
              data: { candidateID: candidateID, status: newStatus },
              dataType: "json",
              success: function (response) {
                  if (!response.success) {
                      alert("Error updating status: " + response.message);

                      // Revert UI if update fails
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

          // If new status is "İşten ayrıldı", add candidate to Exception_Table
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
            }else{
              $.ajax({
                url: "api/delete_excluded_candidate.php",
                method: "POST",
                data: { candidateID: candidateID },
                dataType: "json",
                success: function (response) {
                    if (!response.success) {
                        alert("Error removing from exception: " + response.error);
                    }
                },
                error: function () {
                    alert("An error occurred while removing the candidate from exclusion list.");
                }
              });
            }
        });
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
      allCandidates.sort((a, b) => {
          const valA = a[column] ?? "";
          const valB = b[column] ?? "";

          const numA = parseFloat(valA);
          const numB = parseFloat(valB);

          if (!isNaN(numA) && !isNaN(numB)) {
              return (numA - numB) * (currentSortDirection === 'asc' ? 1 : -1);
          }

          const dateA = new Date(valA);
          const dateB = new Date(valB);
          if (!isNaN(dateA.getTime()) && !isNaN(dateB.getTime())) {
              return (dateA - dateB) * (currentSortDirection === 'asc' ? 1 : -1);
          }

          return valA.toString().localeCompare(valB.toString()) * (currentSortDirection === 'asc' ? 1 : -1);
      });

      renderTable(allCandidates, 1);
      renderPaginationControls(allCandidates);
    }




    $(document).ready(function () {
    $("#syncButton").on("click", function () {
        const syncButton = $(this);
        syncButton.prop("disabled", true); // Disable button during sync
        syncButton.html('<i class="fa-solid fa-sync fa-spin"></i> Synchronizing...'); // Show loading animation

        $.ajax({
            url: "api/synchronizeAll.php", // The PHP script that runs the sync
            method: "POST",
            dataType: "json",
            success: function (response) {
                if (response.success) {                    
                    // Instead of reloading, update table dynamically
                    location.reload();
                } else {
                    alert("Error: " + response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error("Synchronization error:", error);
                alert(`An error occurred during synchronization: ${xhr.responseText}`);
            },
            complete: function () {
                syncButton.prop("disabled", false); // Re-enable button
                syncButton.html('<i class="fa-solid fa-sync"></i> Data Sync'); // Restore original text
            }
        });
    });
  });


  $("#viewLogsBtn").on("click", function () {
    $("#syncLogsContent").html("<p>Loading logs...</p>");

    fetch("api/listSyncLogs.php")
      .then(res => res.json())
      .then(data => {
        if (data.success && data.logs.length > 0) {
          let logList = '<ul class="list-group">';
          data.logs.forEach(log => {
            logList += `
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <strong>${log.filename}</strong> <small class="text-muted">(${log.academicYear}, ${log.sync_date})</small>
                </div>
                <button class="btn btn-sm btn-outline-primary" onclick="showLogDetails('${log.academicYear}', '${log.filename}')">
                  View
                </button>
              </li>
            `;
          });
          logList += '</ul>';
          $("#syncLogsContent").html(logList);
        } else {
          $("#syncLogsContent").html("<p>No logs found.</p>");
        }
      })
      .catch(error => {
        console.error("Error fetching logs:", error);
        $("#syncLogsContent").html("<p>Error loading logs.</p>");
      });

    new bootstrap.Modal(document.getElementById("syncLogsModal")).show();
  });

  const appBaseUrl = <?php echo json_encode($config['app_base_url']); ?>; //get the base url pro2-dev ... from config

  function showLogDetails(academicYear, filename) {
    const path = `${appBaseUrl}odul/logs/${academicYear}/${filename}`;
    fetch(path)
      .then(res => res.json())
      .then(json => {
        const pre = document.createElement("pre");
        pre.textContent = JSON.stringify(json, null, 2);
        $("#syncLogsContent").html(pre);
      })
      .catch(err => {
        console.error("Failed to load log:", err);
        $("#syncLogsContent").html("<p>Unable to load log file.</p>");
      });
  }



  </script>


  <div class="modal fade" id="syncLogsModal" tabindex="-1" aria-labelledby="syncLogsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="syncLogsModalLabel">Sync Logs</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="syncLogsContent">
          <p>Loading logs...</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
