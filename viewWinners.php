<?php
require_once __DIR__ . '/database/dbConnection.php';
require_once 'api/commonFunc.php';
init_session();

// 1) Fetch all academic years that actually have published winners
$stmtYears = $pdo->prepare(<<<'SQL'
  SELECT DISTINCT a.YearID, a.Academic_year
    FROM Winners_Table w
    JOIN AcademicYear_Table a ON w.YearID = a.YearID
   WHERE w.readyDisplay = 'yes'
   ORDER BY a.Academic_year DESC
SQL
);
$stmtYears->execute();
$years = $stmtYears->fetchAll(PDO::FETCH_ASSOC);

// Determine which year is selected (via GET), default to the first in list
$validYearIDs = array_column($years, 'YearID');
$selectedYearID = isset($_GET['yearID']) && in_array((int)$_GET['yearID'], $validYearIDs, true)
  ? (int)$_GET['yearID']
  : ($years[0]['YearID'] ?? null);

// 2) Fetch category metadata (code + description)
$catStmt = $pdo->query("SELECT CategoryID, CategoryCode, CategoryDescription FROM Category_Table");
$allCatsRaw = $catStmt->fetchAll(PDO::FETCH_ASSOC);
$allCats = [];
foreach ($allCatsRaw as $c) {
    $allCats[$c['CategoryID']] = [
      'code' => $c['CategoryCode'],
      'desc' => $c['CategoryDescription'],
    ];
}

// 3) Fetch all winners for the selected year
$winnersByCat = [];
if ($selectedYearID) {
    $winStmt = $pdo->prepare(<<<'SQL'
      SELECT 
        w.WinnerID,
        w.CategoryID,
        w.WinnerName,
        w.Faculty,
        w.`Rank`,
        w.ImagePath,
        w.displayDate
      FROM Winners_Table w
     WHERE w.YearID = :y
       AND w.readyDisplay = 'yes'
     ORDER
       BY CASE w.`Rank`
              WHEN 'Rank-1' THEN 1
              WHEN 'Rank-2' THEN 2
              WHEN 'Rank-3' THEN 3
              ELSE 4
            END,
            w.CreatedAt DESC
SQL
    );
    $winStmt->execute([':y' => $selectedYearID]);
    $all = $winStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all as $r) {
        $cid = $r['CategoryID'];
        if (!isset($winnersByCat[$cid])) {
            $winnersByCat[$cid] = [
                'date' => $r['displayDate'],
                'wins' => [],
            ];
        }
        $winnersByCat[$cid]['wins'][] = $r;
    }

    $catIDs = array_keys($winnersByCat);
    usort($catIDs, function($a, $b) use ($allCats) {
        return strcmp($allCats[$a]['code'], $allCats[$b]['code']);
    });
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Award Winners</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
  <link href="assets/css/layout.min.css" rel="stylesheet" type="text/css">
  <link href="assets/global_assets/css/icons/icomoon/styles.min.css" rel="stylesheet" type="text/css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f9f9f9; padding: 2rem 0; overflow-x: hidden;      overflow-y: auto;    
    .container { max-width: 800px; margin: auto; }
    h1 { text-align: center; margin-bottom:1.5rem; font-weight:600; }
    .filter { display:flex; justify-content:center; align-items:center; gap:.5rem; margin-bottom:2rem; }
    .btn.dropdown-toggle {
      background:#fff!important; color:#333!important; border:1px solid #ccc!important;
      border-radius:6px!important; padding:.5rem 1rem; min-width:140px; text-align:left;
    }
    .btn-custom {
      background:#45748a!important; color:#fff!important; border:none!important;
      padding:.5rem 1.25rem!important; border-radius:6px!important; margin-left:.5rem;
    }
    .btn-custom:hover { background:#365a6b!important; }
    .dropdown-menu { display:none; background:#fff!important; border:1px solid #ccc!important;
      border-radius:6px!important; box-shadow:0 4px 6px rgba(0,0,0,0.1)!important;
    }
    .dropdown-menu.show { display:block!important; }
    .dropdown-item { color:#333!important; padding:.5rem 1rem!important; }
    .dropdown-item:hover { background:#f1f1f1!important; }
    .category-row {
      display:flex; align-items:center; background:#fff; padding:1rem;
      border-radius:.5rem; margin-bottom:.5rem; cursor:pointer; transition:background .2s;
    }
    .category-row:hover { background:#f1f1f1; }
    .category-img { width:80px; height:80px; object-fit:cover; border-radius:.5rem; margin-right:1rem; }
    .category-info h4 { margin:0; font-weight:500; }
    .category-info small { color:#666; display:block; }
    .category-extra { margin-left:auto; text-align:right; }
    .category-extra small { color:#007bff; text-decoration:underline; cursor:pointer; }
    .winners-collapse { display:none; margin-bottom:1rem; background:#fff; border-radius:.5rem; padding:1rem; }
    .winners-row { display:flex; flex-wrap:wrap; gap:1rem; }
    .winner-box { width:120px; text-align:center; }
    .winner-img {
      width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:.5rem;
    }
    .winner-name { margin:0; font-weight:bold; }
    .winner-faculty { margin:0; color:#666; font-size:.9rem; }
    .winner-rank { margin:0; color:#999; font-size:.8rem; }
    .action-container {
      position:fixed; bottom:20px; right:20px;
    }
    .btn-return {
      background:#45748a!important; color:#fff!important; border:none!important;
      padding:.5rem 1.25rem!important; border-radius:6px!important;
    }
    .btn-return:hover { background:#365a6b!important; }
  </style>
</head>
<body>

  <?php 
    $backLink = isset($_SESSION['previous_page']) && $_SESSION['previous_page']==='adminDashboard.php'
      ? "adminDashboard.php"
      : "index.php";
    unset($_SESSION['previous_page']);
    include 'navbar.php'; 
  ?>

  <div class="container">
    <h1>Award Winners</h1>

    <!-- YEAR FILTER -->
    <form method="get" class="filter">
      <input type="hidden" name="yearID" id="yearID" value="<?= htmlspecialchars($selectedYearID) ?>">
      <div class="btn-group">
        <button
          type="button"
          id="yearToggle"
          class="btn dropdown-toggle"
          data-bs-toggle="dropdown"
        >
          <?= $selectedYearID
               ? htmlspecialchars(
                   array_column(
                     $years, 'Academic_year',
                     'YearID'
                   )[$selectedYearID]
                 )
               : 'Select Year' ?>
        </button>
        <ul class="dropdown-menu">
          <?php foreach ($years as $y): ?>
            <li>
              <a
                class="dropdown-item"
                href="#"
                data-value="<?= $y['YearID'] ?>"
              ><?= htmlspecialchars($y['Academic_year']) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <button type="submit" class="btn btn-custom">View Winners</button>
    </form>

    <!-- WINNERS LIST -->
    <?php if ($selectedYearID && !empty($winnersByCat)): ?>
      <?php foreach ($catIDs as $catID):
          $info     = $winnersByCat[$catID];
          $desc     = $allCats[$catID]['desc'];
          $toggleID = "cat{$catID}";
      ?>
      <div class="category-row" data-toggle="<?= $toggleID ?>">
        <img src="additionalImages/sabanciFoto1.jpg" class="category-img" alt="">
        <div class="category-info">
          <h4><?= htmlspecialchars($desc) ?></h4>
        </div>
        <div class="category-extra">
          <small>See Winners</small>
        </div>
      </div>
      <div id="<?= $toggleID ?>" class="winners-collapse">
        <div class="winners-row">
          <?php foreach ($info['wins'] as $w): ?>
            <div class="winner-box">
              <img
                src="<?= htmlspecialchars($w['ImagePath'])?>"
                class="winner-img"
                alt="<?= htmlspecialchars($w['WinnerName']) ?>"
              >
              <p class="winner-name"><?= htmlspecialchars($w['WinnerName']) ?></p>
              <p class="winner-faculty"><?= htmlspecialchars($w['Faculty']) ?></p>
              <p class="winner-rank"><?= htmlspecialchars($w['Rank']) ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    <?php elseif ($selectedYearID): ?>
      <p class="text-center">No winners published for that year yet.</p>
    <?php endif; ?>
  </div>

  <!-- Return Button -->
  <div class="action-container">
    <button class="btn-return" onclick="window.location.href='<?= $backLink ?>'">
      <i class="icon-arrow-left12"></i> Return to Main Menu
    </button>
  </div>

  <!-- SCRIPTS -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script>
    // handle year dropdown selection
    $('#yearToggle').siblings('.dropdown-menu')
      .on('click', '.dropdown-item', function(e) {
        e.preventDefault();
        $('#yearToggle').text($(this).text());
        $('#yearID').val($(this).data('value'));
      });

    // toggle each category’s winners list
    $('.category-row').click(function(){
      const id = $(this).data('toggle');
      $('#' + id).slideToggle();
    });

    // manual dropdown show/hide
    document.addEventListener('DOMContentLoaded', function(){
      const toggle = document.getElementById('yearToggle');
      const menu   = toggle.nextElementSibling;
      toggle.addEventListener('click', function(e){
        e.preventDefault();
        menu.classList.toggle('show');
      });
      document.addEventListener('click', function(e){
        if (!toggle.contains(e.target) && !menu.contains(e.target)) {
          menu.classList.remove('show');
        }
      });
    });
  </script>
</body>
</html>
