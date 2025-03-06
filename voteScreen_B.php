<?php
session_start();
require_once 'api/authMiddleware.php';

if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit();
}

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
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script> <!-- SortableJS for Drag & Drop -->

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
            font-family: 'Roboto', sans-serif;
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

        /* Welcome Section */
        .welcome-section {
            text-align: right;
            margin-top: 10px;
            color: white;
            font-size: 1.1rem;
        }

        /* Content Section */
        .content {
            padding: 20px;
        }

       /* Instructor Card Wrapper */
      .instructor-card-wrapper {
          display: flex;
          flex-direction: column;
          align-items: center;
          background: white;
          border-radius: 12px;
          padding: 15px;
          box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Soft shadow */
          width: 180px; /* Reduced width */
          height: 260px; /* Adjusted height */
          position: relative;
          margin: 10px; /* Reduce margin for better alignment */
          border: 1px solid #d1d1d1;
      }

      /* Instructor Card */
      .instructor-card {
          width: 100%;
          height: 100%;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          cursor: grab;
          border-radius: 10px;
          background-color: #f9f9f9;
          text-align: center;
      }

      /* Instructor Image */
      .instructor-card img {
          width: 70px; /* Reduced image size */
          height: 70px;
          object-fit: cover;
          border-radius: 50%;
          margin-bottom: 10px;
      }

      /* Instructor List Layout - Keep cards in the same row */
      #instructors-list {
          display: flex;
          flex-wrap: nowrap; /* Prevent wrapping */
          justify-content: center;
          gap: 15px; /* Adjust spacing between cards */
          padding: 20px;
          overflow-x: auto; /* Allow horizontal scrolling if needed */
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

        /* Ranking Area */
        .ranking-area {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .rank-slot {
            border: 2px dashed #ccc;
            min-height: 100px;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rank-slot h5 {
            margin: 0;
            font-size: 1rem;
            color: #666;
            pointer-events: none;
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

        <div class="row">
          <!-- Instructors List -->
          <div class="col-md-8">
              <div class="row" id="instructors-list">
                  <p class="text-muted">Loading instructors...</p>
              </div>
          </div>

          <!-- Ranking Slots -->
          <div class="col-md-4 ranking-area">
              <h5 class="text-center mb-3">Rank Instructors</h5>
              <div class="rank-slot" id="rank-1"><h5>1st Place</h5></div>
              <div class="rank-slot" id="rank-2"><h5>2nd Place</h5></div>
              <div class="rank-slot" id="rank-3"><h5>3rd Place</h5></div>
          </div>
    </div>

        <button class="submit-btn btn-secondary" onclick="submitVote()">Submit</button>
    </div>


    <!-- JavaScript (Bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript for Ranking & Removal Logic -->
    <script>

        // Track selected ranks { "instructorIndex": "1"|"2"|"3" }
        let selectedRanks = {};

        
        document.addEventListener("DOMContentLoaded", function () {
            const category = "<?= $category ?>";
            const term = "<?= $term ?>";
            const apiUrl = `http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/getInstructors.php?category=${category}&term=${term}`;

            fetch(apiUrl, { credentials: "include" }) // Ensures session cookies are sent
                .then(response => response.json())
                .then(data => {
                    if (data.status === "success") {
                        displayInstructors(data.data);
                    } else {
                        document.getElementById("instructors-list").innerHTML = `<p class="text-danger">${data.message}</p>`;
                    }
                })
                .catch(error => console.error("Error fetching instructors:", error));
        });

        function displayInstructors(instructors) {
          const container = document.getElementById("instructors-list");
          container.innerHTML = "";

          if (instructors.length === 0) {
              container.innerHTML = `<p class="text-warning">No instructors found.</p>`;
              return;
          }

          instructors.forEach((instructor) => {
              container.innerHTML += `
                  <div class="instructor-card-wrapper">  <!-- Outer Card -->
                      <div class="instructor-card" draggable="true" data-candidate-id="${instructor.InstructorID}">
                          <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
                          <h6 style="font-weight: bold;">${instructor.InstructorName || 'Unknown'}</h6>
                          <p style="color: #555;">${instructor.CourseName || 'Unknown Course'}</p>
                      </div>
                  </div>
              `;
          });

          // Initialize the instructors list as draggable
          new Sortable(document.getElementById("instructors-list"), {
              group: { name: "shared", pull: true, put: true },
              animation: 150,
              draggable: ".instructor-card-wrapper",
              ghostClass: "sortable-ghost"
          });

          // Ranking slots with single-item constraint
          ["rank-1", "rank-2", "rank-3"].forEach(rankId => {
              new Sortable(document.getElementById(rankId), {
                  group: { name: "shared", pull: true, put: true },
                  animation: 150,
                  onAdd: function (evt) {
                      let slot = evt.to;
                      let instructorCards = slot.querySelectorAll('.instructor-card');

                      if (instructorCards.length > 1) {
                          // If the slot is already occupied, revert the drag operation
                          slot.removeChild(evt.item);
                          evt.from.insertBefore(evt.item, evt.from.children[evt.oldIndex] || null);
                          alert("This slot is already occupied!");
                      } else {
                          // Hide placeholder text when occupied
                          let label = slot.querySelector('h5');
                          if (label) label.style.display = 'none';

                          // Store ranking selection
                          let candidateID = evt.item.getAttribute("data-candidate-id");
                          let rank = parseInt(slot.id.replace("rank-", ""));
                          selectedRanks[candidateID] = rank;
                      }
                  },
                  onRemove: function (evt) {
                      let slot = evt.from;
                      if (slot.children.length === 0) {
                          // Show placeholder text if empty
                          let label = slot.querySelector('h5');
                          if (label) label.style.display = '';
                      }

                      // Remove candidate from rankings
                      let candidateID = evt.item.getAttribute("data-candidate-id");
                      delete selectedRanks[candidateID];
                  }
              });
          });
        }


        // Update UI (button text, disable/enable rank options, etc.)
        function updateUI() {
            // Update each card's "Rank here" button and selected rank display
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

            // Figure out how many ranks are assigned so far
            let assignedCount = Object.keys(selectedRanks).length;
            let nextRank = assignedCount + 1;

            // Disable or enable rank options
            document.querySelectorAll('.rank-option').forEach(option => {
                let thisRank = parseInt(option.getAttribute('data-rank'));

                // If we already have 3 assigned, disable everything
                if (assignedCount >= 3) {
                    option.classList.add('disabled');
                } else {
                    // Only enable the exact next rank
                    if (thisRank === nextRank) {
                        option.classList.remove('disabled');
                    } else {
                        option.classList.add('disabled');
                    }
                }
            });
        }

        // Removal logic:
        // - Removing rank3 => remove only rank3
        // - Removing rank2 => remove rank2 and rank3
        // - Removing rank1 => remove rank1, rank2, and rank3
        function removeRank(index) {
            const rankRemovedStr = selectedRanks[index];
            if (!rankRemovedStr) return; // no rank to remove

            const rankRemoved = parseInt(rankRemovedStr);

            // Remove all instructors/candidates who have rank >= rankRemoved
            for (const [i, rStr] of Object.entries(selectedRanks)) {
                const r = parseInt(rStr);
                if (r >= rankRemoved) {
                    delete selectedRanks[i];
                }
            }

            updateUI();
        }

        // Initialize UI
        updateUI();



        //get the current academic year
        async function getAcademicYear() {
            try {
                const response = await fetch("api/getAcademicYear.php", { credentials: "include" });
                const data = await response.json();

                if (data.status === "success") {
                    return data.academicYear; // Return formatted academic year
                } else {
                    console.error("Error fetching academic year:", data.message);
                    return null;
                }
            } catch (error) {
                console.error("Error fetching academic year:", error);
                return null;
            }
        }


        async function submitVote() {
          console.log("Selected Ranks:", selectedRanks);
          
          const categoryId = 'B';  // Ensure category is properly set
          const academicYear = await getAcademicYear(); // Get the academic year dynamically

          if (!academicYear) {
              alert("Failed to get the academic year.");
              return;
          }

          let votes = [];

          // Extract candidates from ranking slots
          ["rank-1", "rank-2", "rank-3"].forEach((slotId, index) => {
              let slot = document.getElementById(slotId);
              let instructorCard = slot.querySelector(".instructor-card");

              if (instructorCard) {
                  let candidateID = instructorCard.getAttribute("data-candidate-id");
                  if (candidateID) {
                      votes.push({ candidateID, rank: index + 1 });
                  }
              }
          });

          if (votes.length === 0) {
              alert("Please rank at least one candidate.");
              return;
          }

          // Send vote data to the backend
          fetch("submitVote.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify({
                  categoryID: categoryId,
                  academicYear: academicYear,
                  votes: votes
              })
          })
          .then(response => response.text()) // Log raw response
          .then(text => {
              console.log("Raw Response from Server:", text);
              try {
                  return JSON.parse(text); // Convert to JSON
              } catch (error) {
                  console.error("Response is not valid JSON:", text);
                  throw error;
              }
          })
          .then(data => {
              console.log("Parsed Response from Server:", data);
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
