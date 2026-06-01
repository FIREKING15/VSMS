<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB(); $msg = ''; $fvid = isset($_GET['vid'])?(int)$_GET['vid']:0;
$statuses = ['Pending','In Progress','Completed','Cancelled'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if ($_POST['action']==='add') {
        $before = ''; $after = '';
        if (!empty($_FILES['img_before']['name'])) {
            $before = 'uploads/' . time() . '_' . basename($_FILES['img_before']['name']);
            move_uploaded_file($_FILES['img_before']['tmp_name'], $before);
        }
        if (!empty($_FILES['img_after']['name'])) {
            $after = 'uploads/' . time() . '_' . basename($_FILES['img_after']['name']);
            move_uploaded_file($_FILES['img_after']['tmp_name'], $after);
        }
        $ok = oraExec($db,
            "INSERT INTO service_records (vehicle_id,employee_id,service_date,service_type,description,status,labour_charge,image_before,image_after)
             VALUES (:vid,:eid,TO_DATE(:dt,'YYYY-MM-DD'),:stype,:descr,:status,:labour,:imgb,:imga)",
            ['vid'=>(int)$_POST['vehicle_id'],'eid'=>(int)$_POST['employee_id'],
             'dt'=>$_POST['service_date'],'stype'=>$_POST['service_type'],
             'descr'=>$_POST['description'],'status'=>$_POST['status'],
             'labour'=>(float)$_POST['labour_charge'],
             'imgb'=>$before, 'imga'=>$after]
        );
        $msg = $ok ? '<div class="alert alert-success">Service record created.</div>'
                   : '<div class="alert alert-error">Insert failed.</div>';
    }
    if ($_POST['action']==='upd') {
        $s = $_POST['new_status'];
        $id = (int)$_POST['service_id'];
        oraExec($db,"UPDATE service_records SET status=:s WHERE service_id=:id", ['s'=>$s,'id'=>$id]);
        if ($s === 'Completed') {
            require_once 'includes/notifications.php';
            sendServiceCompletionNotification($id, $db);
            $msg = '<div class="alert alert-success">Status updated and notification sent.</div>';
        }
    }
}
if (isset($_GET['delete'])) {
    oraExec($db,"DELETE FROM service_records WHERE service_id=:id",['id'=>(int)$_GET['delete']]);
    $msg = '<div class="alert alert-success">Deleted.</div>';
}

$vehicles  = oraQuery($db,"SELECT v.vehicle_id, v.number_plate||' — '||v.make||' '||v.model AS label FROM vehicles v ORDER BY v.number_plate");
$employees = oraQuery($db,"SELECT employee_id,full_name,role FROM employees ORDER BY full_name");
$w = $fvid ? "WHERE sr.vehicle_id=$fvid" : '';
$rows = oraQuery($db,
    "SELECT sr.service_id,sr.service_type,sr.service_date,sr.status,sr.labour_charge,sr.image_before,sr.image_after,
            v.number_plate, v.make||' '||v.model AS vehicle,
            c.full_name AS cname, e.full_name AS ename
     FROM service_records sr
     JOIN vehicles v  ON sr.vehicle_id =v.vehicle_id
     JOIN customers c ON v.customer_id =c.customer_id
     JOIN employees e ON sr.employee_id=e.employee_id
     $w ORDER BY sr.service_date DESC");
?>
<div class="page-header"><h1>SERVICE RECORDS</h1>
  <div style="display:flex;gap:8px">
    <?php if($fvid): ?><a href="service_records.php" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
    <button class="btn btn-primary" onclick="document.getElementById('af').classList.toggle('hidden')">+ New Service</button>
  </div>
</div>
<div class="page-content">
<?= $msg ?>
<div id="af" class="form-card hidden" style="margin-bottom:24px">
  <div class="section-title">Create Service Record</div>
  <form method="POST" enctype="multipart/form-data"><input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="form-group full"><label>Vehicle *</label>
        <select name="vehicle_id" required><option value="">— Select —</option>
          <?php foreach ($vehicles as $v): ?><option value="<?=$v['vehicle_id']?>" <?=$fvid==$v['vehicle_id']?'selected':''?>><?=h($v['label'])?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Employee *</label>
        <select name="employee_id" required><option value="">— Select —</option>
          <?php foreach ($employees as $e): ?><option value="<?=$e['employee_id']?>"><?=h($e['full_name'])?> (<?=h($e['role'])?>)</option><?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Date *</label><input type="date" name="service_date" value="<?=date('Y-m-d')?>" required></div>
      <div class="form-group"><label>Service Type *</label><input name="service_type" required></div>
      <div class="form-group"><label>Status</label>
        <select name="status"><?php foreach($statuses as $s): ?><option><?=$s?></option><?php endforeach; ?></select>
      </div>
      <div class="form-group"><label>Labour Charge (₹)</label><input type="number" name="labour_charge" value="0" step="0.01"></div>
      <div class="form-group"><label>Before Image</label><input type="file" name="img_before" accept="image/*"></div>
      <div class="form-group"><label>After Image</label><input type="file" name="img_after" accept="image/*"></div>
      <div class="form-group full"><label>Description</label><textarea name="description"></textarea></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save</button>
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('af').classList.add('hidden')">Cancel</button>
    </div>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>#</th><th>Customer</th><th>Vehicle</th><th>Type</th><th>Employee</th><th>Date</th><th>Photos</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): $sc=strtolower(str_replace(' ','-',$r['status'])); ?>
    <tr>
      <td class="text-muted"><?=h($r['service_id'])?></td>
      <td><?=h($r['cname'])?></td>
      <td><?=h($r['vehicle'])?><br><code><?=h($r['number_plate'])?></code></td>
      <td><?=h($r['service_type'])?></td><td><?=h($r['ename'])?></td>
      <td><?=h($r['service_date'])?><br>₹<?=format_inr($r['labour_charge'],2)?></td>
      <td style="font-size:11px">
        <?php if($r['image_before']): ?><a href="<?=h($r['image_before'])?>" target="_blank">Before</a><?php endif; ?>
        <?php if($r['image_after']): ?><br><a href="<?=h($r['image_after'])?>" target="_blank">After</a><?php endif; ?>
      </td>
      <td><span class="badge badge-<?=$sc?>"><?=h($r['status'])?></span></td>
      <td class="actions">
        <form method="POST" style="display:inline">
          <input type="hidden" name="action" value="upd">
          <input type="hidden" name="service_id" value="<?=$r['service_id']?>">
          <select name="new_status" onchange="this.form.submit()" style="padding:3px 6px;font-size:12px;background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:3px">
            <?php foreach ($statuses as $s): ?><option <?=$r['status']==$s?'selected':''?>><?=$s?></option><?php endforeach; ?>
          </select>
        </form>
        <a href="billing.php?sid=<?=$r['service_id']?>" class="btn btn-sm btn-secondary">Bill</a>
        <a href="order_parts.php?sid=<?=$r['service_id']?>" class="btn btn-sm btn-blue">Order Parts</a>
        <a href="?delete=<?=$r['service_id']?>" class="btn btn-sm btn-danger confirm-delete">Del</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div>
</div>
<?php require_once 'includes/footer.php'; ?>
