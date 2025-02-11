<?php
session_start();
require_once 'api/authMiddleware.php';
if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit();
}

// Dynamic category and term setup
$category = 'C'; // Adjust as needed (Birinci Sınıf Eğitim Asistanı Ödülü category)
$term = isset($_GET['term']) ? htmlspecialchars($_GET['term']) : '202101';

// Construct API URL
$api_url = "http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/fetchFirstYearTAs.php?term=$term";

// Fetch data from the API
$response = file_get_contents($api_url);
$data = json_decode($response, true);

// Handle API response
if ($data === null || $data['status'] !== 'success') {
    $instructors = [];
    $error_message = "Failed to load TAs.";
} else {
    $instructors = $data['data'];
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
            align-items: center;     /* Horizontally center content */
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
    </style>
</head>

<body>
    <!-- Background Placeholder -->
    <div class="background-placeholder"></div>

    <?php $backLink = "voteCategory.php"; include 'navbar.php'; ?>

    <!-- Content Section -->
    <div class="content container">
        <div class="award-category bg-secondary text-white">
            Birinci Sınıf Eğitim Asistanı Ödülü
        </div>

        <div id="ta-container" class="row justify-content-center">
            <?php if (!empty($instructors)): ?>
                <?php foreach ($instructors as $index => $ta): ?>
                    <div class="col-md-3">
                        <div class="card">
                            <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="TA Photo">
                            <h6><?= htmlspecialchars($ta['TAName'] ?? 'Unknown') ?></h6>
                            <p><?= htmlspecialchars($ta['CourseName'] ?? 'Unknown Course') ?> TA</p>
                            <div class="dropdown">
                                <button 
                                    class="btn btn-secondary dropdown-toggle rank-btn"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    id="rank-btn-<?= $index ?>"
                                    data-candidate-id="<?= htmlspecialchars($ta['TAID'] ?? '') ?>">
                                    Rank here
                                </button>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item rank-option" data-rank="1" data-index="<?= $index ?>" href="#">1st place</a>
                                    <a class="dropdown-item rank-option" data-rank="2" data-index="<?= $index ?>" href="#">2nd place</a>
                                    <a class="dropdown-item rank-option" data-rank="3" data-index="<?= $index ?>" href="#">3rd place</a>
                                </div>
                            </div>
                            <div id="selected-rank-<?= $index ?>" class="mt-2"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-danger">Failed to load TAs. Please try again later.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Submit Button -->
    <button class="submit-btn btn-secondary" onclick="submitVote()">Submit</button>

    <!-- JS (Bootstrap) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom Rank Logic -->
    <script>
        // Must pick rank1, then rank2, then rank3 in that order.
        // If removing rank1 => remove ranks1,2,3
        // If removing rank2 => remove ranks2,3
        // If removing rank3 => remove only rank3

        // selectedRanks = { "index": "1" | "2" | "3" }
        let selectedRanks = {};

        // Attach event listeners to all rank-option items
        document.querySelectorAll('.rank-option').forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                let rank = parseInt(this.getAttribute('data-rank'));
                let index = this.getAttribute('data-index');

                // How many ranks have been assigned so far?
                let assignedCount = Object.keys(selectedRanks).length;
                let nextRank = assignedCount + 1;

                // Enforce the order: must pick the (assignedCount + 1)-th rank
                if (rank !== nextRank) {
                    alert("You must pick rank " + nextRank + " first!");
                    return;
                }

                // Assign rank to that TA
                selectedRanks[index] = rank.toString();
                updateUI();
            });
        });

        function updateUI() {
            // Update each button label & "selected-rank" area
            document.querySelectorAll('.rank-btn').forEach((btn, index) => {
                let selDiv = document.getElementById(`selected-rank-${index}`);
                selDiv.innerHTML = '';

                if (selectedRanks[index]) {
                    let r = selectedRanks[index];
                    btn.textContent = `Rank ${r}`;
                    selDiv.innerHTML = `
                        <span>Rank: ${r}</span>
                        <button class="btn btn-danger btn-sm ms-2 remove-rank" onclick="removeRank(${index})">X</button>
                    `;
                } else {
                    btn.textContent = "Rank here";
                }
            });

            // Figure out how many ranks assigned
            let assignedCount = Object.keys(selectedRanks).length;
            let nextRank = assignedCount + 1;

            // Disable or enable rank options based on nextRank or if 3 are assigned
            document.querySelectorAll('.rank-option').forEach(option => {
                let thisRank = parseInt(option.getAttribute('data-rank'));
                if (assignedCount >= 3) {
                    // Already assigned 3 => disable everything
                    option.classList.add('disabled');
                } else {
                    // Enable only if it's exactly the next rank
                    if (thisRank === nextRank) {
                        option.classList.remove('disabled');
                    } else {
                        option.classList.add('disabled');
                    }
                }
            });
        }

        // Remove rank logic:
        // - removing rank3 => remove rank3 only
        // - removing rank2 => remove rank2 and rank3
        // - removing rank1 => remove rank1, rank2, rank3
        function removeRank(index) {
            let rankRemovedStr = selectedRanks[index];
            if (!rankRemovedStr) return; // no rank to remove

            let rankRemoved = parseInt(rankRemovedStr);
            // Remove all TAs with rank >= rankRemoved
            for (const [taIndex, rStr] of Object.entries(selectedRanks)) {
                if (parseInt(rStr) >= rankRemoved) {
                    delete selectedRanks[taIndex];
                }
            }

            updateUI();
        }

        // Initialize once at page load
        updateUI();

        // Submit the vote
        function submitVote() {
            console.log("Selected Ranks:", selectedRanks);

            const categoryId = 'D'; 
            const academicYear = '2021'; // Adjust if needed
            let votes = [];

            // Build votes array from selectedRanks
            for (const [index, rank] of Object.entries(selectedRanks)) {
                const btn = document.querySelector(`#rank-btn-${index}`);
                if (!btn) continue;
                const candidateID = btn.getAttribute('data-candidate-id') || "";
                if (candidateID.trim() !== "") {
                    votes.push({ candidateID, rank });
                } else {
                    console.error(`Missing candidateID for index ${index}`);
                }
            }

            if (votes.length === 0) {
                alert("Please rank at least one candidate.");
                return;
            }

            console.log("Votes being sent:", votes);

            fetch("submitVote.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    categoryID: categoryId,
                    academicYear: academicYear,
                    votes: votes
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log("Server Response:", data);
                if (data.status === "success") {
                    window.location.href = `thankYou.php?context=vote&completedCategoryId=${categoryId}`;
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error("Error submitting votes:", error);
            });
        }
    </script>
</body>
</html>
