<?php
session_start();
require_once 'api/commonFunc.php';
require_once 'database/dbConnection.php';
$pageTitle = "Acknowledgments";
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

    <div class="ack-footer">
      Teaching Awards Web Application Project is developed by the students above with the helps of IT Department of Sabancı University.
    </div>
  </div>

  <?php require_once 'api/footer.php'; ?>
</body>
</html>
