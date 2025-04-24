<?php
require_once 'api/authMiddleware.php';
require_once 'api/commonFunc.php';
init_session();
checkVotingWindow($pdo);

$category = isset($_GET['category']) ? htmlspecialchars($_GET['category']) : 'B';
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Teaching Awards - Sabancı University</title>

  <!-- Limitless Theme CSS -->
  <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
  <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <style>
    /* General Page Styles */
    html, body {
      height: 100%;
      margin: 0;
      overflow-y: auto; /* Enables vertical scrolling */
      overflow-x: hidden; /* Prevents horizontal scrolling */
    }
    body {
      margin: 0;
      background-color: #f5f7fa;
      padding-top: 80px;
    }

    /* Navbar */
    .navbar-brand img {
      height: 40px;
    }
    .navbar-brand span {
      font-size: 1.25rem;
      font-weight: bold;
      color: white !important;
      margin-left: 10px;
    }

    /* Content Section */
    .content {
      padding: 20px;
    }

    /* Instructor Card Styles */
   /* Instructor Card Styles */
  .card {
      height: 300px; /* or any height that fits your content well */
      width: 270px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      align-items: center;
      text-align: center;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 15px;
      background-color: #fff;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
      margin: 10px;
    }

    .course-history-scroll {
      max-height: 100px;
      overflow-y: auto;
      overflow-x: hidden;
      width: 100%;
      text-align: center;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 0 10px;
      margin-top: 5px;
    }
    .card img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 50%;
      margin-bottom: 10px;
    }

    /* Award Category Header */
    .award-category {
      text-align: center;
      font-size: 1.2rem;
      font-weight: bold;
      color: white;
      padding: 10px;
      border-radius: 8px;
      margin: 20px 0;
    }

    /* Submit Button */
    .submit-btn {
      position: fixed;
      bottom: 20px;
      right: 30px;
      color: white;
      border: none;
      padding: 10px 20px;
      font-size: 1rem;
      border-radius: 5px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
    }
    .submit-btn:hover {
      cursor: pointer;
    }

    /* Background Placeholder Fix */
    .background-placeholder {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 25px;
      background-color: #3f51b5;
      z-index: -1;
    }

    /* Disabled option style */
    .dropdown-item.disabled {
      pointer-events: none;
      opacity: 0.5;
    }

    /* Custom close button style for the confirmation modal */
    #confirmModal .btn-close {
      background: none !important;
      background-image: none !important; /* Remove default icon */
      border: none !important;
      box-shadow: none !important;
      appearance: none;
      width: 1em;
      height: 1em;
      padding: 0;
      opacity: 1; /* Always fully visible */
      position: relative;
    }
    #confirmModal .btn-close::before {
      content: "×";         /* The 'X' character */
      color: #ff0000;       /* Red color */
      font-size: 1.4rem;    /* Adjust as needed */
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }
    #confirmModal .btn-close:hover::before,
    #confirmModal .btn-close:focus::before {
      opacity: 0.8;
    }

  </style>
</head>

<body>
  <!-- Background Placeholder -->
  <div class="background-placeholder"></div>

  <?php $backLink = "voteCategory.php"; include 'navbar.php'; ?>

  <!-- Content Section -->
  <div class="content container">
    <!-- Award Category Header -->
    <div class="award-category bg-secondary text-white">
      Yılın Mezunları Ödülü
    </div>
    <div class="row justify-content-center mt-4" id="instructors-list">
      <p class="text-muted">Loading instructors...</p>
    </div>
  </div>

  <!-- Submit Button -->
  <button class="submit-btn btn-secondary" onclick="submitVote()">Submit</button>

  <!-- Confirmation Modal -->
  <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="confirmModalLabel">Confirm Voting</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <!-- The modal body now includes a summary area -->
        <div class="modal-body" id="confirmModalBody">
          <div id="confirmSummary"></div>
          <p>Are you sure about your voting? There is no going back.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmSubmit">I Accept</button>
        </div>
      </div>
    </div>
  </div>

  <!-- JavaScript (Bootstrap) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Custom JavaScript for Dropdown Ranking and Vote Submission -->
  <script>
    // Object to track selected ranks (mapping instructor index to rank number as a string)
    let selectedRanks = {};

    document.addEventListener("DOMContentLoaded", function () {
      const category = "<?= $category ?>";
      const term = "<?= isset($term) ? $term : '' ?>";
      const apiUrl = `api/getInstructors.php?category=${category}&term=${term}`;

      fetch(apiUrl, { credentials: "include" })
        .then(response => response.json())
        .then(data => {
          if (data.status === "success") {
            displayInstructors(data.data);
          } else {
            document.getElementById("instructors-list").innerHTML = `<p class="text-danger">${data.message}</p>`;
          }
        })
        .catch(error => console.error("Error fetching instructors:", error));

      // Attach the confirm button's click event listener only once
      document.getElementById('confirmSubmit').addEventListener('click', function () {
        var confirmModal = bootstrap.Modal.getInstance(document.getElementById('confirmModal'));
        confirmModal.hide();
        doSubmitVote();
      });
    });

    function displayInstructors(instructors) {
      const container = document.getElementById("instructors-list");
      container.innerHTML = ""; // Clear existing content

      if (instructors.length === 0) {
        container.innerHTML = `<p class="text-warning">No instructors found.</p>`;
        return;
      }

      instructors.forEach((instructor, index) => {
      const cardHTML = `
        <div class="d-flex justify-content-center mb-4">
          <div class="card">
            <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
            <h6>${instructor.InstructorName || 'Unknown'}</h6>
            <div class="course-history-scroll">
              ${instructor.Courses || 'No courses found'}
            </div>


            <div class="dropdown mt-2">
              <button class="btn btn-secondary dropdown-toggle rank-btn"
                      type="button"
                      data-bs-toggle="dropdown"
                      id="rank-btn-${index}"
                      data-candidate-id="${instructor.InstructorID}">
                Rank here
              </button>
              <div class="dropdown-menu">
                <a class="dropdown-item rank-option" data-rank="1" data-index="${index}" href="#">1st place</a>
                <a class="dropdown-item rank-option" data-rank="2" data-index="${index}" href="#">2nd place</a>
                <a class="dropdown-item rank-option" data-rank="3" data-index="${index}" href="#">3rd place</a>
              </div>
            </div>
            <div id="selected-rank-${index}" class="mt-2"></div>
          </div>
        </div>
      `;

      container.innerHTML += cardHTML;
      });

      // Add event listeners for each dropdown rank option
      document.querySelectorAll('.rank-option').forEach(item => {
        item.addEventListener('click', function (e) {
          e.preventDefault();
          let rank = parseInt(this.getAttribute('data-rank'));
          let index = this.getAttribute('data-index');

          // Only allow selecting the next rank in sequence
          let assignedCount = Object.keys(selectedRanks).length;
          let nextRank = assignedCount + 1;

          if (rank !== nextRank) {
            alert("You must pick rank " + nextRank + " first!");
            return;
          }

          // Assign the rank to the selected instructor
          selectedRanks[index] = rank.toString();
          updateUI();
        });
      });
    }

    // Update the UI for rank buttons and selected rank display
    function updateUI() {
      document.querySelectorAll('.rank-btn').forEach((btn, index) => {
        let selectedDiv = document.getElementById(`selected-rank-${index}`);
        selectedDiv.innerHTML = '';

        if (selectedRanks[index]) {
          let assignedRank = selectedRanks[index];
          btn.innerHTML = `Rank ${assignedRank}`;
          selectedDiv.innerHTML = `
            <span>Rank: ${assignedRank}</span>
            <button class="btn btn-danger btn-sm ms-2 remove-rank" onclick="removeRank(${index})">X</button>
          `;
        } else {
          btn.innerHTML = `Rank here`;
        }
      });

      // Disable or enable dropdown options based on the number of assigned ranks
      let assignedCount = Object.keys(selectedRanks).length;
      let nextRank = assignedCount + 1;

      document.querySelectorAll('.rank-option').forEach(option => {
        let rank = parseInt(option.getAttribute('data-rank'));
        if (assignedCount >= 3) {
          option.classList.add('disabled');
        } else {
          if (rank === nextRank) {
            option.classList.remove('disabled');
          } else {
            option.classList.add('disabled');
          }
        }
      });
    }

    // Removal logic: removing a rank removes that rank and any higher assigned ranks
    function removeRank(index) {
      const rankRemovedStr = selectedRanks[index];
      if (!rankRemovedStr) return;
      const rankRemoved = parseInt(rankRemovedStr);

      for (const [instIndex, instRankStr] of Object.entries(selectedRanks)) {
        const instRank = parseInt(instRankStr);
        if (instRank >= rankRemoved) {
          delete selectedRanks[instIndex];
        }
      }
      updateUI();
    }

    // Fetch the current academic year
    async function getAcademicYear() {
      try {
        const response = await fetch("api/getAcademicYear.php", { credentials: "include" });
        const data = await response.json();
        if (data.status === "success") {
          return data.academicYear;
        } else {
          console.error("Error fetching academic year:", data.message);
          return null;
        }
      } catch (error) {
        console.error("Error fetching academic year:", error);
        return null;
      }
    }

    // Function to build a summary of the selected votes
    function updateConfirmModalSummary() {
      // Get all candidate name elements from the displayed cards.
      // We assume the candidate name is in the h6 element within each card.
      const candidateElements = document.querySelectorAll('#instructors-list .card h6');
      let summaryHTML = '<ul>';
      // Loop through possible ranks 1 to 3 in order
      for (let r = 1; r <= 3; r++) {
        // Look for an entry in selectedRanks with this rank
        for (const [index, rank] of Object.entries(selectedRanks)) {
          if (parseInt(rank) === r) {
            const candidateName = candidateElements[index] ? candidateElements[index].textContent : 'Unknown';
            summaryHTML += `<li>Rank ${r}: ${candidateName}</li>`;
          }
        }
      }
      summaryHTML += '</ul>';
      return summaryHTML;
    }

    // When the user clicks submit, update the modal summary then show the confirmation modal.
    function submitVote() {
      const summaryHTML = updateConfirmModalSummary();
      document.getElementById("confirmSummary").innerHTML = summaryHTML;
      var confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
      confirmModal.show();
    }

    // This function handles the actual vote submission.
    async function doSubmitVote() {
      console.log("Selected Ranks:", selectedRanks);

      const categoryId = 'B';
      const academicYear = await getAcademicYear();
      if (!academicYear) {
        alert("Failed to get the academic year.");
        return;
      }
      let votes = [];

      Object.entries(selectedRanks).forEach(([index, rank]) => {
        let candidateButton = document.querySelector(`#rank-btn-${index}`);
        if (candidateButton) {
          let candidateID = candidateButton.getAttribute("data-candidate-id");
          if (candidateID && candidateID.trim() !== "") {
            votes.push({ candidateID, rank });
          }
        }
      });

      if (votes.length === 0) {
        alert("Please rank at least one candidate.");
        return;
      }

      fetch("submitVote.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          categoryID: categoryId,
          academicYear: academicYear,
          votes: votes
        })
      })
      .then(response => response.text())
      .then(text => {
        console.log("Raw Response from Server:", text);
        try {
          return JSON.parse(text);
        } catch (error) {
          console.error("Response is not valid JSON:", text);
          throw error;
        }
      })
      .then(data => {
        if (data.status === "success") {
          window.location.href = `thankYou.php?context=vote&completedCategoryId=${categoryId}`;
        } else {
          alert(data.message);
        }
      })
      .catch(error => console.error("Fetch Error:", error));
    }
  </script>
</body>
</html>
