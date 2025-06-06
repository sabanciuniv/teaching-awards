<?php
session_start();
require_once 'api/commonFunc.php';
require_once 'database/dbConnection.php';
$pageTitle = "Acknowledgements";
require_once 'api/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  
    <link href="assets/css/bootstrap.min.css"           rel="stylesheet">
  <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet">
  <link href="assets/css/components.min.css"          rel="stylesheet">
  <link href="assets/css/layout.min.css"              rel="stylesheet">
  <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css" rel="stylesheet">
  <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">


  <style>
    body {
      background-color: #f9f9f9;
      margin: 0;
      font-family: Arial, sans-serif;
      overflow-x: hidden;
      overflow-y: auto;
    }
    .container-ack {
  padding: 15rem 1rem 4rem; 
  text-align: center;
}
    .ack-title {
      font-size: 2.5rem;
      font-weight: 800;
      color: #004f9e;
      margin-bottom: 3rem;
      text-transform: uppercase;
    }
    .ack-first {
      font-size: 3.5rem;
      font-weight: 800;
      color: #004f9e;
      line-height: 1.2;
    }
    .ack-last {
      font-size: 2rem;
      font-weight: 800;
      color: #004f9e;
      margin-top: .5rem;
    }
    .ack-footer {
      font-size: 1.1rem;
      color: rgb(54,56,70);
      margin-top: 4rem;
    }
  </style>

</head>
<body>
<?php $backLink = "index.php"; include 'navbar.php'; ?>

  <div class="container-ack">
    <div class="ack-title">ENS491/492 Graduation Project</div>

    <div class="row justify-content-center">
      <div class="col-12 col-md-4">
        <div class="ack-first">Ilgın Simay</div>
        <div class="ack-last">Özcan</div>
      </div>
      <div class="col-12 col-md-4">
        <div class="ack-first">Tankut Kayra</div>
        <div class="ack-last">Özerk</div>
      </div>
      <div class="col-12 col-md-4">
        <div class="ack-first">Damla</div>
        <div class="ack-last">Aydın</div>
      </div>
    </div>

  <div class="ack-footer" style="line-height: 1.9; max-width: 900px; margin: 4rem auto 0; text-align: center; font-size: 1.15rem;">
  <p>
  </p>  
  We would like to express our sincere gratitude to everyone who supported, guided, and created the opportunity for the development of the ENS491/492 Graduation Project: the Teaching Awards Web Application.
  
  <p>
    <br>
  We extend our special thanks to <strong>Mustafa Yörükoğlu</strong> and <strong>Can Çakmakçı</strong> for their generous guidance throughout the process. They provided technical insights, valuable feedback, and hands-on support whenever needed. Their assistance was also instrumental in the successful deployment of the system.
  </p>

    <p>
    We are deeply thankful to  <strong>Serkan Keskin</strong>, whose approval and support enabled us to undertake this project in the first place. We are also grateful to  <strong>Hüsnü Yenigün</strong> for his mentorship and encouragement throughout the project, and <strong>Deniz İnan</strong> for her guidance on the correct interpretation of SU Teaching Awards procedures and her feedback on our system.
    </p>
    <br>
    <p>
    Our appreciation goes to the IT Department of Sabancı University for offering Computer Science students the opportunity to work on a real-world system. Applying the skills we have acquired during our studies to a meaningful and practical project has been a vital part of our learning experience. This aligns perfectly with the goals of the ENS491/492 Graduation Project course.

Thank you to everyone who supported us along the way and helped make this project a valuable learning journey.
    </p>
  </div>



  <?php require_once 'api/footer.php'; ?>
</body>
</html>
