<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB(); $msg = '';
$roles = ['Mechanic','Electrician','Painter','Supervisor','Receptionist'];

if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='add') {
    if (!empty($_POST['phone']) && !preg_match('/^\d{10}$/', trim($_POST['phone']))) {
        $msg = '<div class="alert alert-error">Phone number must be exactly 10 digits.</div>';
    } else {
        $username = trim($_POST['username'] ?? '');
        $passHash = password_hash('user123', PASSWORD_DEFAULT);
        $ok = oraExec($db,
            "INSERT INTO employees (username,password_hash,full_name,role,phone,salary,hired_on) VALUES (:usr,:pwd,:name,:role,:phone,:sal,TO_DATE(:dt,'YYYY-MM-DD'))",
            ['usr'=>$username, 'pwd'=>$passHash, 'name'=>$_POST['full_name'],'role'=>$_POST['role'],'phone'=>$_POST['phone'],
             'sal'=>(float)$_POST['salary'],'dt'=>$_POST['hired_on']]);
        $msg = $ok ? '<div class="alert alert-success">Employee added. Default password is <b>user123</b>.</div>'
                   : '<div class="alert alert-error">Insert failed. Username might be taken.</div>';
    }
}
if (isset($_GET['delete'])) {
    oraExec($db,"DELETE FROM employees WHERE employee_id=:id",['id'=>(int)$_GET['delete']]);
    $msg = '<div class="alert alert-success">Deleted.</div>';
}

$rows = oraQuery($db,
    "SELECT e.*,
            (SELECT COUNT(sr.service_id) FROM service_records sr WHERE sr.employee_id = e.employee_id) AS jobs 
     FROM employees e
     ORDER BY e.full_name");
?>
<div class="page-header"><h1>EMPLOYEES</h1>
  <button class="btn btn-primary" onclick="document.getElementById('af').classList.toggle('hidden')">+ Add Employee</button>
</div>
<div class="page-content">
<?=$msg?>
<div id="af" class="form-card hidden" style="margin-bottom:24px">
  <div class="section-title">Add Employee</div>
  <form method="POST"><input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="form-group"><label>Username *</label><input name="username" required></div>
      <div class="form-group"><label>Full Name *</label><input name="full_name" required></div>
      <div class="form-group"><label>Role *</label>
        <select name="role"><?php foreach($roles as $r): ?><option><?=$r?></option><?php endforeach; ?></select>
      </div>
      <div class="form-group"><label>Phone</label><input type="text" name="phone" pattern="\d{10}" title="Must be exactly 10 digits" minlength="10" maxlength="10"></div>
      <div class="form-group"><label>Salary (₹)</label><input type="number" name="salary" value="0" step="0.01"></div>
      <div class="form-group"><label>Hired On</label><input type="date" name="hired_on"></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save</button>
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('af').classList.add('hidden')">Cancel</button>
    </div>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Phone</th><th>Salary</th><th>Hired On</th><th>Jobs</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td class="text-muted"><?=h($r['employee_id'])?></td>
      <td><?=h($r['full_name'])?></td>
      <td><span class="badge badge-in-progress"><?=h($r['role'])?></span></td>
      <td><?=h($r['phone']??'—')?></td>
      <td>₹<?=format_inr($r['salary'],2)?></td>
      <td><?=h($r['hired_on']??'—')?></td>
      <td><?=$r['jobs']?></td>
      <td><a href="?delete=<?=$r['employee_id']?>" class="btn btn-sm btn-danger confirm-delete">Delete</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div>
</div>
<?php require_once 'includes/footer.php'; ?>
