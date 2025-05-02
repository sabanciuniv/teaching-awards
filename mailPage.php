<?php
// mailPage.php

require_once 'api/authMiddleware.php';
require_once __DIR__ . '/database/dbConnection.php';
$config = require __DIR__ . '/config.php';

// start session & enforce login
require_once 'api/commonFunc.php';
init_session();
// only admins may proceed
enforceAdminAccess($pdo);
set_time_limit(0);
ignore_user_abort(true);

// PHPMailer
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ----------------------------------------------------
// A0) AJAX: Get count of students for Opening Mail
// ----------------------------------------------------
if (
  $_SERVER['REQUEST_METHOD'] === 'GET'
 && isset($_GET['action']) && $_GET['action'] === 'openingMailCount'
) {
  header('Content-Type: application/json; charset=utf-8');
  // safe: always uses the current year ID
  $yearID = getCurrentAcademicYearID($pdo) ?: 0;
  $stmt   = $pdo->prepare("SELECT COUNT(*) FROM Student_Table WHERE YearID = :y");
  $stmt->execute([':y'=>$yearID]);
  echo json_encode(['total'=>(int)$stmt->fetchColumn()]);
  exit;
}

// ----------------------------------------------------
// A) AJAX: Save template edits
// ----------------------------------------------------
if (
    $_SERVER['REQUEST_METHOD']==='POST'
 && isset($_GET['action']) && $_GET['action']==='saveTemplate'
 && isset($_POST['templateID'],$_POST['MailHeader'],$_POST['MailBody'])
) {
    header('Content-Type: application/json; charset=utf-8');
    $id   = (int)$_POST['templateID'];
    $hdr  = trim($_POST['MailHeader']);
    $body = $_POST['MailBody'];
    if (!$id || $hdr==='') {
        echo json_encode(['success'=>false,'error'=>'Missing fields']);
        exit;
    }
    try {
        $pdo->prepare("
          UPDATE MailTemplate_Table
             SET MailHeader = :hdr
               , MailBody   = :body
           WHERE TemplateID = :id
        ")->execute([
            ':hdr'=>$hdr,':body'=>$body,':id'=>$id
        ]);
        echo json_encode(['success'=>true]);
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
    }
    exit;
}

// ----------------------------------------------------
// B) AJAX: Send Opening Mail
// ----------------------------------------------------
if (
  $_SERVER['REQUEST_METHOD']==='POST'
 && isset($_GET['action']) && $_GET['action']==='sendOpeningMail'
) {
  header('Content-Type: application/json; charset=utf-8');

  // grab both the YearID and the Academic_year
  $yearInfo = fetchCurrentAcademicYear($pdo);
  if (!$yearInfo) {
      http_response_code(500);
      echo json_encode(['error'=>'No academic year found']);
      exit;
  }
  $yearID    = (int)$yearInfo['YearID'];
  $startYear = (int)$yearInfo['Academic_year'];
  $yearLabel = $startYear . '-' . ($startYear+1);

  // load OpeningMail template
  $tpl = $pdo->prepare("
    SELECT TemplateID,MailHeader,MailBody
      FROM MailTemplate_Table
     WHERE MailType='OpeningMail'
     LIMIT 1
  ");
  $tpl->execute();
  $tpl = $tpl->fetch(PDO::FETCH_ASSOC);
  if (!$tpl) {
      http_response_code(404);
      echo json_encode(['error'=>"No 'OpeningMail' template found"]);
      exit;
  }

  // fetch students
  $students = $pdo->prepare("
    SELECT StudentFullName,Mail
      FROM Student_Table
     WHERE YearID=:y
  ");
  $students->execute([':y'=>$yearID]);
  $students = $students->fetchAll(PDO::FETCH_ASSOC);

  // PHPMailer setup
  try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $config['mail']['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['mail']['username'];
    $mail->Password   = $config['mail']['password'];
    // map encryption setting
    if ($config['mail']['encryption'] === 'ssl') {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    } else {
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    }
    $mail->Port         = $config['mail']['port'];
    $mail->setFrom(
        $config['mail']['from_address'], 
        $config['mail']['from_name']
    );
    $mail->SMTPKeepAlive = true;
    $mail->isHTML(true);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'SMTP init failed: ' . $e->getMessage()]);
    exit;
}

  // send loop
  $sent = 0;
  $failed = [];
  foreach ($students as $stu) {
    $addr = $stu['Mail'] ?? '';
    if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) {
      $failed[] = ['email'=>$addr,'reason'=>'Invalid email'];
      continue;
    }
    try {
      $mail->clearAddresses();
      $mail->addAddress($addr,$stu['StudentFullName']);
      $mail->Subject = $tpl['MailHeader'];

      // personalization tokens
      $body = str_replace(
        ['@name_surname','@year'],
        [$stu['StudentFullName'],$yearLabel],
        $tpl['MailBody']
      );
      $mail->Body    = $body;
      $mail->AltBody = strip_tags($body);

      $mail->send();
      $sent++;

      // log it
      $pdo->prepare("
        INSERT INTO MailLog_Table
          (Sender,StudentEmail,StudentName,TemplateID,MailContent,YearID)
        VALUES
          (:s,:e,:n,:tid,:c,:y)
      ")->execute([
        ':s'=>$_SESSION['user'],
        ':e'=>$addr,
        ':n'=>$stu['StudentFullName'],
        ':tid'=>$tpl['TemplateID'],
        ':c'=>$body,
        ':y'=>$yearID
      ]);
    } catch(Exception $e) {
      $failed[] = ['email'=>$addr,'reason'=>$e->getMessage()];
    }
  }

  $mail->smtpClose();
  echo json_encode([
    'sent'=>$sent,
    'total'=>count($students),
    'failed'=>$failed
  ]);
  exit;
}

// ----------------------------------------------------
// C) Normal page: Auth + fetch templates + logs
// ----------------------------------------------------
$check = $pdo->prepare("
  SELECT 1
    FROM Admin_Table
   WHERE AdminSuUsername=:u
     AND checkRole<>'Removed'
     AND Role IN('IT_Admin','Admin')
   LIMIT 1
");
$check->execute([':u'=>$_SESSION['user']]);
if (!$check->fetch()) {
  header("Location:index.php");
  exit;
}

// fetch templates
$mailTemplates = $pdo
  ->query("SELECT TemplateID,MailType,MailHeader,MailBody FROM MailTemplate_Table ORDER BY TemplateID")
  ->fetchAll(PDO::FETCH_ASSOC);

// current year ID (for display in the log modal)
$currentYearID = getCurrentAcademicYearID($pdo) ?: 0;

// fetch logs
$mailLogs = $pdo->prepare("
  SELECT 
    l.LogID,
    l.Sender,
    l.StudentEmail,
    l.StudentName,
    t.MailType,
    t.MailHeader,
    l.MailContent AS MailBody,
    l.SentTime
  FROM MailLog_Table l
  LEFT JOIN MailTemplate_Table t
    ON l.TemplateID = t.TemplateID
  WHERE l.YearID = :y
  ORDER BY l.SentTime DESC
");
$mailLogs->execute([':y'=>$currentYearID]);
$mailLogs = $mailLogs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Mail Templates &amp; Log</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
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
    body { background:#f9f9f9; padding-top:70px; overflow:auto;}
    .container{ max-width:90%; margin:auto; }
    .title{ text-align:center; margin-bottom:1rem;}
    .btn-custom{ background:#45748a!important;color:#fff!important;border:none!important;
                 padding:.5rem 1rem;border-radius:4px;cursor:pointer;}
    .btn-custom:hover{ background:#365a6b!important; }
    .action-container{ position:fixed; bottom:20px; right:20px;
                       display:flex; flex-direction:column; gap:8px; }
    #sendOpeningBtn{ position:fixed; bottom:20px; left:20px; z-index:1000; }
    .close-modal-btn {
      color: red;
      background: none;
      border: none;
      font-size: 1.5rem;
      line-height: 1;
      cursor: pointer;
    }
    .btn-close {
      background: none !important;
      background-image: none !important;
      border: none !important;
      box-shadow: none !important;
      width: 1em;
      height: 1em;
      padding: 0;
      position: relative;
    }
    .btn-close::before {
      content: "×";
      color: #ff0000;
      font-size: 1.4rem;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
    }
    .btn-close:hover::before,
    .btn-close:focus::before {
      opacity: 0.8;
    }
  </style>
</head>
<body>
<?php $backLink = "adminDashboard.php"; include 'navbar.php'; ?>

<div class="container">
  <h2 class="title">Mail Templates</h2>
  <table id="templatesTable" class="table table-striped">
    <thead>
      <tr><th>Header</th><th>Body</th><th>Action</th></tr>
    </thead>
    <tbody>
    <?php foreach($mailTemplates as $t): ?>
      <tr
        data-id="<?= $t['TemplateID'] ?>"
        data-type="<?= htmlspecialchars($t['MailType'],ENT_QUOTES) ?>"
        data-header="<?= htmlspecialchars($t['MailHeader'],ENT_QUOTES) ?>"
        data-body="<?= htmlspecialchars($t['MailBody'],ENT_QUOTES) ?>"
      >
        <td><?= htmlspecialchars($t['MailHeader']) ?></td>
        <td><?= htmlspecialchars(strip_tags($t['MailBody'])) ?></td>
        <td><button class="btn btn-custom edit-btn"><i class="fa fa-edit"></i> Edit</button></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
  <div class="modal-header">
    <h5 class="modal-title">Edit Mail Template</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
  </div>
  <div class="modal-body">
    <form id="editForm">
      <input type="hidden" id="TemplateID" name="templateID">
      <div class="mb-3">
        <label class="form-label">Mail Type</label>
        <input type="text" id="MailType" name="MailType" class="form-control" readonly>
      </div>
      <div class="mb-3">
        <label class="form-label">Mail Subject</label>
        <input type="text" id="MailHeader" name="MailHeader" class="form-control">
      </div>
      <div class="mb-3">
        <label class="form-label">Mail Body</label>
        <div id="MailBodyEditor" style="height:200px;background:#fff;"></div>
        <small class="form-text text-muted">
          Use <code>@name_surname</code> and <code>@year</code> in your template.
        </small>
      </div>
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
    <button id="saveBtn" class="btn btn-custom">Save</button>
  </div>
</div></div></div>

<!-- Send Opening Mail -->
<button id="sendOpeningBtn" class="btn btn-custom">
  <i class="fa fa-paper-plane"></i> Send Opening Mail
</button>

<!-- Action Buttons -->
<div class="action-container">
  <button id="viewLogBtn" class="btn btn-custom"><i class="fa fa-list"></i> View Mail Log</button>
  <button onclick="location.href='adminDashboard.php'" class="btn btn-secondary">
    <i class="fa fa-arrow-left"></i> Return to Dashboard
  </button>
</div>

<!-- Mail Log Modal -->
<div class="modal fade" id="logModal" tabindex="-1"><div class="modal-dialog modal-xl"><div class="modal-content">
  <div class="modal-header">
    <h5 class="modal-title">Mail Log (Year <?= $currentYearID ?>)</h5>
    <button type="button" class="close-modal-btn" data-bs-dismiss="modal">&times;</button>
  </div>
  <div class="modal-body">
    <table id="logTable" class="table table-striped" style="width:100%">
      <thead>
        <tr>
          <th>LogID</th><th>Sender</th><th>StudentEmail</th><th>StudentName</th>
          <th>MailType</th><th>Mail Subject</th><th>Mail Body</th><th>SentTime</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div></div></div>

<!-- JS libs -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script>
  // Quill editor
  let quill = new Quill('#MailBodyEditor',{ theme:'snow' });

  // Templates table
  $('#templatesTable').DataTable({
    dom:'Bfrtip',
    buttons:[{ extend:'excelHtml5', className:'btn btn-sm btn-outline-primary' }]
  });

  // Edit button
  $('.edit-btn').click(function(){
    let tr = $(this).closest('tr');
    $('#TemplateID').val(tr.data('id'));
    $('#MailType').val(tr.data('type'));
    $('#MailHeader').val(tr.data('header'));
    quill.root.innerHTML = tr.data('body');
    new bootstrap.Modal($('#editModal')).show();
  });

  // Save edits
  $('#saveBtn').click(()=>{
    $.post('?action=saveTemplate',{
      templateID: $('#TemplateID').val(),
      MailHeader: $('#MailHeader').val(),
      MailBody:   quill.root.innerHTML
    },res=>{
      if(res.success) location.reload();
      else alert('Error: '+res.error);
    },'json');
  });

  // Send Opening Mail with count & confirmation
  $('#sendOpeningBtn').click(async () => {
    // fetch how many
    let rc = await fetch('mailPage.php?action=openingMailCount');
    let tc = await rc.text();
    let jc;
    try {
      jc = JSON.parse(tc);
    } catch (e) {
      console.error('Bad JSON for count:', tc);
      return alert('Server error fetching student count. Check console.');
    }
    if (!rc.ok) {
      return alert('Error fetching student count: ' + (jc.error||rc.status));
    }
    if (!confirm(`Are you sure you want to send Opening Mail to ${jc.total} students?`)) return;

    // actually send
    let rs = await fetch('mailPage.php?action=sendOpeningMail',{method:'POST'});
    let ts = await rs.text();
    let js;
    try {
      js = JSON.parse(ts);
    } catch (e) {
      console.error('Bad JSON from sendOpeningMail:', ts);
      return alert('Server error sending mails. Check console.');
    }
    if (!rs.ok) {
      return alert('Error sending mails: ' + (js.error||rs.statusText));
    }
    alert(`Sent ${js.sent}/${js.total}\nFailed: ${js.failed.length}`);
  });

  // View Mail Log
  const logs = <?= json_encode($mailLogs) ?>;
  $('#viewLogBtn').click(()=>{
    $('#logTable').DataTable({
      data: logs,
      destroy: true,
      dom: 'Bfrtip',
      order: [[7,'desc']],
      buttons:[{ extend:'excelHtml5', className:'btn btn-sm btn-outline-primary' }],
      columns:[
        {data:'LogID'}, {data:'Sender'}, {data:'StudentEmail'}, {data:'StudentName'},
        {data:'MailType'}, {data:'MailHeader'}, {data:'MailBody'}, {data:'SentTime'}
      ]
    });
    new bootstrap.Modal($('#logModal')).show();
  });
</script>
</body>
</html>
