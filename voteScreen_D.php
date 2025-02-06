<?php
session_start();
require_once 'api/authMiddleware.php';
if (!isset($_SESSION['user'])) {
    // Redirect if the user is not logged in
    header("Location: login.php");
    exit();
}

// Dynamic category and term setup
$category = 'C'; // Adjust dynamically for the Temel Geliştirme Yılı Öğretim Görevlisi Ödülü category
$term = isset($_GET['term']) ? htmlspecialchars($_GET['term']) : '202301';

// Construct API URL
$api_url = "http://pro2-dev.sabanciuniv.edu/odul/ENS491-492/api/fetchFirstYearTAs.php?term=$term";

// Fetch data from the API
$response = file_get_contents($api_url);
$data = json_decode($response, true);

// Handle API response
if ($data === null || $data['status'] !== 'success') {
    $instructors = [];
    $error_message = "Failed to load instructors.";
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
            overflow-x: hidden; /* Prevents horizontal scrolling */
        }
        
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background-color: #f5f7fa;
            padding-top: 80px;
        }

        /* Navbar */
        /* Logo and title in the navbar */
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
            Birinci Sınıf Eğitim Asistanı Ödülü
        </div>

        <!-- Instructor Cards -->
        <div id="ta-container" class="row justify-content-center">
            
        </div>
    <!-- Submit Button -->
    <button class="submit-btn btn-secondary" onclick="submitVote()">Submit</button>

    <!-- JavaScript -->
    <script>
        function redirectToThankYouPage() {
            const categoryId = 'D'; // Adjust dynamically if needed
            window.location.href = `thankYou.php?context=vote&completedCategoryId=${categoryId}`;
        }

        let selectedRanks = {}; // To track selected ranks dynamically
        function attachRankListeners() {
            document.querySelectorAll('.rank-option').forEach(item => {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    const rank = this.getAttribute('data-rank');
                    const index = this.getAttribute('data-index');

                    // Check if the rank is already selected for another candidate
                    if (Object.values(selectedRanks).includes(rank)) {
                        alert(`Rank ${rank} is already selected for another instructor.`);
                        return;
                    }

                    // Assign the rank to the selected instructor
                    selectedRanks[index] = rank;
                    updateUI();
                });
            });
        }

        function updateUI() {
            // Update the UI to reflect selected ranks
            document.querySelectorAll('.rank-btn').forEach((btn, index) => {
                const selectedDiv = document.getElementById(`selected-rank-${index}`);
                const rank = selectedRanks[index];
                if (rank) {
                    btn.textContent = `Rank ${rank}`;
                    selectedDiv.innerHTML = `
                        <span>Rank: ${rank}</span>
                        <button class="btn btn-danger btn-sm ms-2 remove-rank" onclick="removeRank(${index})">X</button>
                    `;
                } else {
                    btn.textContent = `Rank here`;
                    selectedDiv.innerHTML = '';
                }
            });

            // Disable selected ranks in other dropdowns
            document.querySelectorAll('.rank-option').forEach(option => {
                const rankValue = option.getAttribute('data-rank');
                option.classList.toggle('disabled', Object.values(selectedRanks).includes(rankValue));
            });
        }

        function removeRank(index) {
            // Remove the selected rank for the given index
            delete selectedRanks[index];
            updateUI();
        }

        async function fetchTAs() {
        try {
            const response = await fetch('api/fetchFirstYearTAs.php');
            const data = await response.json();
            const container = document.getElementById('ta-container');

            if (!container) {
                console.error("Container with ID 'ta-container' not found.");
                return;
            }

            if (data.status === 'success') {
                container.innerHTML = ''; // Clear existing content
                data.data.forEach((ta, index) => {
                    const card = `
                        <div class="col-md-3">
                            <div class="card">
                                <img src="https://i.pinimg.com/originals/e7/13/89/e713898b573d71485de160a7c29b755d.png" alt="TA Photo">
                                <h6>${ta.TAName}</h6>
                                <p>${ta.CourseName} TA</p>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle rank-btn"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            id="rank-btn-${index}"
                                            data-candidate-id="${ta.TAID}">
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
                    container.innerHTML += card;
                });

                attachRankListeners(); // Attach event listeners
            } else {
                container.innerHTML = `<p>${data.message}</p>`;
            }
        } catch (error) {
            console.error('Error fetching TAs:', error);
            const container = document.getElementById('ta-container');
            if (container) {
                container.innerHTML = '<p>Failed to load TAs. Please try again later.</p>';
            }
        }
    }

    function submitVote() {
        console.log("Selected Ranks:", selectedRanks); // Debugging step

        const categoryId = 'D'; // Adjust dynamically
        const academicYear = '2021'; // Adjust dynamically
        const votes = Object.entries(selectedRanks).map(([index, rank]) => {
            const candidateButton = document.querySelector(`#rank-btn-${index}`);
            return {
                candidateID: candidateButton?.getAttribute("data-candidate-id"),
                rank,
            };
        });

        console.log("Votes Data being sent:", { categoryID: categoryId, academicYear, votes }); // Debugging

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
                votes: votes,
            }),
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



    fetchTAs();

    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
