<?php
session_start();
require_once 'api/authMiddleware.php';

if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit();
}

// Get category and term from the URL or set default values
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

        /* Cards Section */
        .card {
            display: flex;
            flex-direction: column;
            justify-content: center; /* Vertically center content */
            align-items: center; /* Horizontally center content */
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            background-color: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .card img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%; /* Make the image circular */
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

    <!-- Submit Button -->
    <button class="submit-btn btn-secondary" onclick="submitVote()">Submit</button>

    <!-- JavaScript (Bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript for Ranking & Removal Logic -->
    <script>
        // Not strictly used in this flow, but kept if needed for any redirection
        function redirectToThankYouPage() {
            const categoryId = 'A1'; // Adjust dynamically if needed
            window.location.href = `thankYou.php?context=vote&completedCategoryId=${categoryId}`;
        }

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
            container.innerHTML = ""; // Clear existing content

            if (instructors.length === 0) {
                container.innerHTML = `<p class="text-warning">No instructors found.</p>`;
                return;
            }

            instructors.forEach((instructor, index) => {
                container.innerHTML += `
                    <div class="col-md-3">
                        <div class="card">
                            <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Instructor Photo">
                            <h6>${instructor.InstructorName || 'Unknown'}</h6>
                            <p>${instructor.CourseName || 'Unknown Course'}</p>
                            <div class="dropdown">
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
            });

        // Strict order: must choose rank 1 first, then 2, then 3
        document.querySelectorAll('.rank-option').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                let rank = parseInt(this.getAttribute('data-rank'));
                let index = this.getAttribute('data-index');

                // Determine how many ranks already assigned
                let assignedCount = Object.keys(selectedRanks).length;
                let nextRank = assignedCount + 1;

                // Enforce picking the next rank only
                if (rank !== nextRank) {
                    alert("You must pick rank " + nextRank + " first!");
                    return;
                }

                // Assign the rank to the selected card
                selectedRanks[index] = rank.toString();
                updateUI();
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


        // Submit the vote data
        async function submitVote() {
            console.log("Selected Ranks:", selectedRanks); // Debugging step

            const categoryId = 'B';
            const academicYear = await getAcademicYear(); // get academic year dynamically
            if(!academicYear)
            {
                alert("failed to get the academic year.");
                return;
            }
            let votes = [];

            Object.entries(selectedRanks).forEach(([index, rank]) => {
                let candidateButton = document.querySelector(`#rank-btn-${index}`);
                if (candidateButton) {
                    let candidateID = candidateButton.getAttribute("data-candidate-id");
                    console.log(`CandidateID for index ${index}:`, candidateID); // Debugging step

                    if (candidateID && candidateID.trim() !== "") {
                        votes.push({ candidateID, rank });
                    } else {
                        console.error(`Missing CandidateID for index ${index}`);
                    }
                }
            });

            console.log("Votes Data being sent:", votes); // Debugging step

            if (votes.length === 0) {
                alert("Please rank at least one candidate.");
                return;
            }

            // Send the vote data as JSON to submitVote.php
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
