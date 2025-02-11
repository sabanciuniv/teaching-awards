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

        <!-- Instructor/Candidate Cards -->
        <div class="row justify-content-center">
            <!-- Card 1 -->
            <div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Candidate Photo">
                    <h6>Name Surname</h6>
                    <p>MATH203 Instructor</p>
                    <div class="dropdown">
                        <button
                            class="btn btn-secondary dropdown-toggle rank-btn"
                            type="button"
                            data-bs-toggle="dropdown"
                            id="rank-btn-0"
                            data-candidate-id="candidate_1">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item rank-option" data-rank="1" data-index="0" href="#">1st place</a>
                            <a class="dropdown-item rank-option" data-rank="2" data-index="0" href="#">2nd place</a>
                            <a class="dropdown-item rank-option" data-rank="3" data-index="0" href="#">3rd place</a>
                        </div>
                    </div>
                    <div id="selected-rank-0" class="mt-2"></div>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Candidate Photo">
                    <h6>Name Surname</h6>
                    <p>MATH201 Instructor</p>
                    <div class="dropdown">
                        <button
                            class="btn btn-secondary dropdown-toggle rank-btn"
                            type="button"
                            data-bs-toggle="dropdown"
                            id="rank-btn-1"
                            data-candidate-id="candidate_2">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item rank-option" data-rank="1" data-index="1" href="#">1st place</a>
                            <a class="dropdown-item rank-option" data-rank="2" data-index="1" href="#">2nd place</a>
                            <a class="dropdown-item rank-option" data-rank="3" data-index="1" href="#">3rd place</a>
                        </div>
                    </div>
                    <div id="selected-rank-1" class="mt-2"></div>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Candidate Photo">
                    <h6>Name Surname</h6>
                    <p>CS201 Instructor</p>
                    <div class="dropdown">
                        <button
                            class="btn btn-secondary dropdown-toggle rank-btn"
                            type="button"
                            data-bs-toggle="dropdown"
                            id="rank-btn-2"
                            data-candidate-id="candidate_3">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item rank-option" data-rank="1" data-index="2" href="#">1st place</a>
                            <a class="dropdown-item rank-option" data-rank="2" data-index="2" href="#">2nd place</a>
                            <a class="dropdown-item rank-option" data-rank="3" data-index="2" href="#">3rd place</a>
                        </div>
                    </div>
                    <div id="selected-rank-2" class="mt-2"></div>
                </div>
            </div>

            <!-- Card 4 -->
            <div class="col-md-3">
                <div class="card">
                    <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="Candidate Photo">
                    <h6>Name Surname</h6>
                    <p>CS201 Instructor</p>
                    <div class="dropdown">
                        <button
                            class="btn btn-secondary dropdown-toggle rank-btn"
                            type="button"
                            data-bs-toggle="dropdown"
                            id="rank-btn-3"
                            data-candidate-id="candidate_4">
                            Rank here
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item rank-option" data-rank="1" data-index="3" href="#">1st place</a>
                            <a class="dropdown-item rank-option" data-rank="2" data-index="3" href="#">2nd place</a>
                            <a class="dropdown-item rank-option" data-rank="3" data-index="3" href="#">3rd place</a>
                        </div>
                    </div>
                    <div id="selected-rank-3" class="mt-2"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Submit Button -->
    <button class="submit-btn btn-secondary" onclick="submitVote()">Submit</button>

    <!-- JavaScript (Bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript for Ranking & Removal Logic -->
    <script>
        // Track selected ranks: { "cardIndex": "1"|"2"|"3" }
        let selectedRanks = {};

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

        // Submit function (demo version)
        // You could adapt this if you want to send data to the server via fetch
        function submitVote() {
            // For demonstration, log the selected ranks and simply redirect
            console.log("Selected Ranks:", selectedRanks);

            // Optionally, send them to a server or do something else
            // fetch(...)

            // For now, just redirect
            const categoryId = 'B'; // arbitrary ID
            window.location.href = `thankYou.php?context=vote&completedCategoryId=${categoryId}`;
        }
    </script>

</body>
</html>
