<?php
require_once 'api/authMiddleware.php';
require_once 'api/commonFunc.php';
$pageTitle= "Student Voting Status";
require_once 'api/header.php';

init_session();

require_once __DIR__ . '/database/dbConnection.php';
$user = $_SESSION['user'];

// Admin check
if (! checkIfUserIsAdmin($pdo, $user)) {
  logUnauthorizedAccess($pdo, $user, basename(__FILE__));
  header("Location: index.php");
  exit();
}

//get all the academic years from common Function php
try{
  $academicYears = getAllAcademicYears($pdo);
}catch (PDOException $e) {
  die("Error fetching lookup data: " . $e->getMessage());
}

// If a year is selected, gather students + categories + vote flag
$votedStudents    = [];
$notVotedStudents = [];
$yearLabel        = '';
if (!empty($_GET['year'])) {
    $yearId = (int)$_GET['year'];
    foreach ($academicYears as $y) {
        if ($y['YearID'] == $yearId) {
            $yearLabel = $y['Academic_year'];
            break;
        }
    }
    $sql = "
      SELECT
        s.StudentID,
        s.StudentFullName,
        s.Mail           AS Email,
        s.SuNET_Username AS Username,
        s.CGPA           AS GPA,
        COALESCE(
          GROUP_CONCAT(DISTINCT c.CategoryDescription ORDER BY c.CategoryDescription SEPARATOR ', '),
          ''
        ) AS Categories,
        EXISTS(
          SELECT 1 FROM Votes_Table v
           WHERE v.VoterID = s.id
             AND v.AcademicYear = :yr
        ) AS VotedFlag
      FROM Student_Table s
      LEFT JOIN Student_Category_Relation scr
        ON s.id = scr.student_id
      LEFT JOIN Category_Table c
        ON scr.CategoryID = c.CategoryID
     WHERE s.YearID = :yr
     GROUP BY s.id
     ORDER BY s.StudentID
    ";
    $stm = $pdo->prepare($sql);
    $stm->execute([':yr' => $yearId]);
    $all = $stm->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all as $r) {
        if ($r['VotedFlag']) {
            $votedStudents[] = $r;
        } else {
            $notVotedStudents[] = $r;
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<style>
  body {
    background:#f9f9f9;
    padding-top:70px;
  }
  .container {
    max-width:900px;
    margin:auto;
  }
  h3.title {
    text-align:center;
    margin-bottom:1.5rem;
  }
  .form-section {
    display:flex;
    flex-wrap:wrap;
    justify-content:center;
    align-items:center;
    gap:1rem;
    margin-bottom:20px;
  }
  .dropdown-select {
    background:#fff!important;
    color:#333!important;
    border:1px solid #ccc!important;
    border-radius:6px!important;
    padding:10px 20px!important;
    min-width:200px;
  }
  .btn-custom {
    background:#45748a!important;
    color:#fff!important;
    border:none!important;
    padding:10px 20px!important;
    font-size:14px;
    border-radius:5px!important;
    display:flex;
    align-items:center;
    gap:5px;
  }
  .btn-custom:hover { background:#365a6b!important; }
  .action-container {
    position:fixed;
    bottom:20px;
    right:20px;
    display:flex;
    gap:10px;
  }
  .return-button {
    background:#45748a!important;
    color:#fff!important;
    border:none!important;
    padding:10px 20px!important;
    font-size:14px;
    border-radius:5px!important;
  }
  .return-button:hover { background:#365a6b!important; }
  /* remove manual table-body scroll overrides */
</style>
<body>
<?php $backLink = "reportPage.php"; include 'navbar.php'; ?>

<div class="container mt-4">
  <h3 class="title">Student Voting Status</h3>
  <form method="get" class="form-section">
    <select name="year" class="dropdown-select" required>
      <option value="" disabled <?= empty($_GET['year'])?'selected':'' ?>>Select Academic Year</option>
      <?php foreach($academicYears as $y): ?>
        <option value="<?= $y['YearID']?>" <?= (isset($_GET['year'])&&$_GET['year']==$y['YearID'])?'selected':''?>>
          <?= htmlspecialchars($y['Academic_year'])?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn-custom">
      <i class="fa fa-eye"></i> View Students
    </button>
  </form>

  <?php if(!empty($_GET['year'])): ?>
    <ul class="nav nav-tabs" id="voteTabs" role="tablist">
      <li class="nav-item">
        <button class="nav-link active" id="voted-tab"
                data-bs-toggle="tab" data-bs-target="#voted"
                type="button" role="tab">Voted Students
        </button>
      </li>
      <li class="nav-item">
        <button class="nav-link" id="not-voted-tab"
                data-bs-toggle="tab" data-bs-target="#notVoted"
                type="button" role="tab">Not Voted Students
        </button>
      </li>
    </ul>

    <div class="tab-content mt-3">
      <div class="tab-pane fade show active" id="voted" role="tabpanel">
        <table id="votedTable" class="display nowrap" style="width:100%"></table>
      </div>
      <div class="tab-pane fade" id="notVoted" role="tabpanel">
        <table id="notVotedTable" class="display nowrap" style="width:100%"></table>
        <div class="mt-3 text-end">
          <button id="notifyBtn" class="btn-custom d-none">
            <i class="fa fa-envelope"></i> Notify Students
          </button>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<div class="action-container">
  <button class="return-button" onclick="location.href='reportPage.php'">
    <i class="fa fa-arrow-left"></i> Return to Reports Page
  </button>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
const votedData    = <?= json_encode($votedStudents) ?>;
const notVotedData = <?= json_encode($notVotedStudents) ?>;
const yearLabel    = <?= json_encode($yearLabel) ?> || 'Year';

$(function(){
  const cols = [
    { title:"Student ID",   data:"StudentID"   },
    { title:"Name",         data:"StudentFullName" },
    { title:"Email",        data:"Email"       },
    { title:"Username",     data:"Username"    },
    { title:"GPA",          data:"GPA"         },
    { title:"Categories",   data:"Categories"  }
  ];

  $('#votedTable').DataTable({
    data: votedData,
    columns: cols,
    dom: 'Bfrtip',
    buttons: [{
      extend:'excelHtml5',
      text:'Export Excel',
      className:'btn-custom',
      filename:`Voted_${yearLabel}`
    }],
    scrollY:'50vh',
    scrollX:true,
    scrollCollapse:true,
    paging:false
  });

  $('#notVotedTable').DataTable({
    data: notVotedData,
    columns: cols,
    dom: 'Bfrtip',
    buttons: [{
      extend:'excelHtml5',
      text:'Export Excel',
      className:'btn-custom',
      filename:`NotVoted_${yearLabel}`
    }],
    scrollY:'50vh',
    scrollX:true,
    scrollCollapse:true,
    paging:false
  });

  $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e){
    $('#notifyBtn').toggleClass('d-none', e.target.id !== 'not-voted-tab');

    // Fix alignment on tab change
    if (e.target.id === 'not-voted-tab') {
      $('#notVotedTable').DataTable().columns.adjust().draw();
    } else if (e.target.id === 'voted-tab') {
      $('#votedTable').DataTable().columns.adjust().draw();
    }
  });

  $('#notifyBtn').on('click', async function(){
    if (!notVotedData.length) return alert('No students to notify.');
    if (!confirm(`Send notification to ${notVotedData.length} students?`)) return;
    try {
      const res = await fetch('notifyStudents.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({ students:notVotedData, year:Number(<?= json_encode($_GET['year'] ?? 0) ?>) })
      });
      const o = await res.json();
      if (res.ok) {
        alert(`Sent: ${o.sent}, Failed: ${o.failed.length}`);
      } else {
        alert(`Error: ${o.error||'Unknown'}`);
      }
    } catch(err){
      console.error(err);
      alert('Notification request failed.');
    }
  });
});
</script>
</body>
</html>
