<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB(); $msg = ''; $fcid = isset($_GET['cid'])?(int)$_GET['cid']:0;

if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='add') {
    $ok = oraExec($db,
        "INSERT INTO vehicles (customer_id,number_plate,make,model,year_made,color) VALUES (:cid,:plate,:make,:model,:yr,:color)",
        ['cid'=>(int)$_POST['customer_id'],'plate'=>$_POST['number_plate'],'make'=>$_POST['make'],
         'model'=>$_POST['model'],'yr'=>(int)$_POST['year_made'],'color'=>$_POST['color']]
    );
    $msg = $ok ? '<div class="alert alert-success">Vehicle registered.</div>'
               : '<div class="alert alert-error">Error — plate may already exist.</div>';
}
if (isset($_GET['delete'])) {
    oraExec($db,"DELETE FROM vehicles WHERE vehicle_id=:id",['id'=>(int)$_GET['delete']]);
    $msg = '<div class="alert alert-success">Vehicle deleted.</div>';
}

$customers = oraQuery($db,"SELECT customer_id,full_name FROM customers ORDER BY full_name");
$w = $fcid ? "WHERE v.customer_id=$fcid" : '';
$rows = oraQuery($db,"SELECT v.*,c.full_name AS cname FROM vehicles v JOIN customers c ON v.customer_id=c.customer_id $w ORDER BY v.vehicle_id DESC");
?>
<div class="page-header"><h1>VEHICLES</h1>
  <div style="display:flex;gap:8px">
    <?php if($fcid): ?><a href="vehicles.php" class="btn btn-secondary btn-sm">Clear Filter</a><?php endif; ?>
    <button class="btn btn-primary" onclick="document.getElementById('af').classList.toggle('hidden')">+ Register Vehicle</button>
  </div>
</div>
<div class="page-content">
<?= $msg ?>
<div id="af" class="form-card hidden" style="margin-bottom:24px">
  <div class="section-title">Register New Vehicle</div>
  <form method="POST"><input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="form-group full"><label>Owner *</label>
        <select name="customer_id" required>
          <option value="">— Select Customer —</option>
          <?php foreach ($customers as $c): ?>
          <option value="<?=$c['customer_id']?>" <?=$fcid==$c['customer_id']?'selected':''?>><?=h($c['full_name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Number Plate *</label><input name="number_plate" placeholder="DL01AB1234" required></div>
      <div class="form-group"><label>Make *</label><input name="make" required></div>
      <div class="form-group"><label>Model *</label><input name="model" required></div>
      <div class="form-group"><label>Year</label><input type="number" name="year_made" min="1990" max="<?=date('Y')?>"></div>
      <div class="form-group"><label>Color</label><input name="color"></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save</button>
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('af').classList.add('hidden')">Cancel</button>
    </div>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>ID</th><th>Owner</th><th>Plate</th><th>Make</th><th>Model</th><th>Year</th><th>Color</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td class="text-muted"><?=h($r['vehicle_id'])?></td><td><?=h($r['cname'])?></td>
      <td><code><?=h($r['number_plate'])?></code></td><td><?=h($r['make'])?></td>
      <td><?=h($r['model'])?></td><td><?=h($r['year_made']??'—')?></td><td><?=h($r['color']??'—')?></td>
      <td class="actions">
        <a href="service_records.php?vid=<?=$r['vehicle_id']?>" class="btn btn-sm btn-secondary">Services</a>
        <a href="?delete=<?=$r['vehicle_id']?>" class="btn btn-sm btn-danger confirm-delete">Delete</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div>
</div>
<?php require_once 'includes/footer.php'; ?>
