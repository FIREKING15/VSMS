<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB(); $msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='add') {
    if (!preg_match('/^\d{10}$/', trim($_POST['phone']))) {
        $msg = '<div class="alert alert-error">Phone number must be exactly 10 digits.</div>';
    } else {
        $ok = oraExec($db,
            "INSERT INTO customers (full_name,phone,email,address) VALUES (:name,:phone,:email,:addr)",
            ['name'=>$_POST['full_name'],'phone'=>$_POST['phone'],'email'=>$_POST['email'],'addr'=>$_POST['address']]
        );
        $msg = $ok ? '<div class="alert alert-success">Customer added.</div>'
                   : '<div class="alert alert-error">Insert failed — phone may already exist.</div>';
    }
}
if (isset($_GET['delete'])) {
    oraExec($db,"DELETE FROM customers WHERE customer_id=:id",['id'=>(int)$_GET['delete']]);
    $msg = '<div class="alert alert-success">Customer deleted.</div>';
}

$rows = oraQuery($db,
    "SELECT c.customer_id, c.full_name, c.phone, c.email, c.address,
            (SELECT COUNT(v.vehicle_id) FROM vehicles v WHERE v.customer_id = c.customer_id) AS vehicle_count
     FROM customers c
     ORDER BY c.customer_id DESC");
?>
<div class="page-header"><h1>CUSTOMERS</h1>
  <button class="btn btn-primary" onclick="document.getElementById('af').classList.toggle('hidden')">+ Add Customer</button>
</div>
<div class="page-content">
<?= $msg ?>
<div id="af" class="form-card hidden" style="margin-bottom:24px">
  <div class="section-title">New Customer</div>
  <form method="POST"><input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="form-group"><label>Full Name *</label><input name="full_name" required></div>
      <div class="form-group"><label>Phone *</label><input type="text" name="phone" pattern="\d{10}" title="Must be exactly 10 digits" minlength="10" maxlength="10" required></div>
      <div class="form-group"><label>Email</label><input type="email" name="email"></div>
      <div class="form-group"><label>Address</label><input name="address"></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save</button>
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('af').classList.add('hidden')">Cancel</button>
    </div>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th><th>Vehicles</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td class="text-muted"><?=h($r['customer_id'])?></td>
      <td><?=h($r['full_name'])?></td><td><?=h($r['phone'])?></td>
      <td><?=h($r['email']??'—')?></td><td><?=h($r['address']??'—')?></td>
      <td><?=$r['vehicle_count']?></td>
      <td class="actions">
        <a href="vehicles.php?cid=<?=$r['customer_id']?>" class="btn btn-sm btn-secondary">Vehicles</a>
        <a href="?delete=<?=$r['customer_id']?>" class="btn btn-sm btn-danger confirm-delete">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div>
</div>
<?php require_once 'includes/footer.php'; ?>
