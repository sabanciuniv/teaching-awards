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
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Voting Category</title>
  <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
  <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <style>
    body {
      background-color: #f9f9f9;
      font-family: Arial, sans-serif;
      margin: 0;
      display: flex;
      align-items: center;
      flex-direction: column;
      padding-top: 70px;
    }
    .content-wrapper {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      box-sizing: border-box;
      text-align: center;
    }
    .title {
      font-size: 2rem;
      font-weight: bold;
      margin-bottom: 20px;
      color: #000;
    }
    .categories-container {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
      justify-content: center;
      margin: 0 auto;
      max-width: 800px;
    }
    .category-card {
      flex: 0 0 250px;
      text-align: center;
      cursor: pointer;
      border-radius: 8px;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s, border-color 0.2s;
      color: #fff;
      height: 75px;
      background-color: var(--bs-secondary-bg);
      border: 3px solid transparent;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    .category-card.completed {
      border-color: #4caf50;
    }
    .category-card:hover {
      transform: translateY(-5px);
    }
    .category-card.completed .checkmark {
      display: block;
    }
    .checkmark {
      display: none;
      position: absolute;
      bottom: 10px;
      right: 10px;
      font-size: 1.5rem;
      color: #4caf50;
    }
    .card-body {
      margin: 0;
      padding: 0;
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <div class="content-wrapper">
    <div class="title">Select a Voting Category</div>
    <div class="categories-container" id="categories-container">
      <!-- Categories will be dynamically loaded here -->
    </div>
  </div>

  <!-- Vote Details Modal -->
  <div class="modal fade" id="voteModal" tabindex="-1" aria-labelledby="voteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="voteModalLabel">Your Vote Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="modalBody">
          <!-- Vote details will be loaded here -->
        </div>
      </div>
    </div>
  </div>

  <script>
    // 1) Fetch categories on page load
    async function fetchCategories() {
      try {
        const response = await fetch('api/getAllowedCategories.php');
        const data = await response.json();

        if (data.status === "success" && data.categories && data.categories.length > 0) {
          renderCategories(data.categories);
        } else {
          console.error("No categories found:", data.message);
          document.getElementById('categories-container').innerHTML = "<p>No available voting categories.</p>";
        }
      } catch (error) {
        console.error("Fetch Error:", error);
        document.getElementById('categories-container').innerHTML = "<p>Error loading categories.</p>";
      }
    }

    // 2) Render category cards
    function renderCategories(categories) {
      const container = document.getElementById('categories-container');
      container.innerHTML = '';

      categories.forEach(category => {
        const card = document.createElement('div');
        card.className = 'card category-card bg-secondary';

        // If the user has voted, mark the card as completed
        if (parseInt(category.isVoted) === 1) {
          card.classList.add('completed');
          const checkmark = document.createElement('div');
          checkmark.className = 'checkmark';
          checkmark.innerHTML = '<i class="fa fa-check"></i>';
          card.appendChild(checkmark);
        }

        // Clicking a voted category => show popup
        // Clicking a non-voted category => redirect to voting screen
        card.onclick = () => {
          if (parseInt(category.isVoted) === 1) {
            showVotePopup(category.CategoryCode);
          } else {
            window.location.href = `voteScreen_${category.CategoryCode}.php`;
          }
        };

        // Display the category description in the card
        const cardBody = document.createElement('div');
        cardBody.className = 'card-body';
        cardBody.textContent = category.CategoryDescription;
        card.appendChild(cardBody);
        container.appendChild(card);
      });
    }

    // 3) Show the vote details popup (modal)
    function showVotePopup(categoryCode) {
      fetch(`api/getVoteDetails.php?category=${categoryCode}`)
        .then(response => response.json())
        .then(data => {
          document.getElementById('modalBody').innerHTML = data.voteDetails || '<p>No details available.</p>';
          var voteModal = new bootstrap.Modal(document.getElementById('voteModal'));
          voteModal.show();
        })
        .catch(error => {
          console.error('Error fetching vote details:', error);
          document.getElementById('modalBody').innerHTML = '<p>Error loading vote details.</p>';
          var voteModal = new bootstrap.Modal(document.getElementById('voteModal'));
          voteModal.show();
        });
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', fetchCategories);
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
