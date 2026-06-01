<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB(); $msg = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if ($_POST['action']==='add') {
        $ok = oraExec($db,
            "INSERT INTO spare_parts (part_name,part_code,unit_price,stock_qty) VALUES (:name,:code,:price,:qty)",
            ['name'=>$_POST['part_name'],'code'=>$_POST['part_code'],
             'price'=>(float)$_POST['unit_price'],'qty'=>(int)$_POST['stock_qty']]);
        $msg = $ok ? '<div class="alert alert-success">Part added.</div>'
                   : '<div class="alert alert-error">Insert failed — code may exist.</div>';
    }
    if ($_POST['action']==='restock') {
        oraExec($db,"UPDATE spare_parts SET stock_qty=stock_qty+:q WHERE part_id=:id",
            ['q'=>(int)$_POST['add_qty'],'id'=>(int)$_POST['part_id']]);
        $msg = '<div class="alert alert-success">Stock updated.</div>';
    }
}
if (isset($_GET['delete'])) {
    oraExec($db,"DELETE FROM spare_parts WHERE part_id=:id",['id'=>(int)$_GET['delete']]);
    $msg = '<div class="alert alert-success">Deleted.</div>';
}

$rows = oraQuery($db,"SELECT * FROM spare_parts ORDER BY part_name");
?>
<div class="page-header"><h1>SPARE PARTS &amp; INVENTORY</h1>
  <button class="btn btn-primary" onclick="document.getElementById('af').classList.toggle('hidden')">+ Add Part</button>
</div>
<div class="page-content">
<?=$msg?>
<div id="af" class="form-card hidden" style="margin-bottom:24px">
  <div class="section-title">Add Spare Part</div>
  <form method="POST"><input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="form-group"><label>Part Name *</label><input name="part_name" required></div>
      <div class="form-group"><label>Part Code</label><input name="part_code"></div>
      <div class="form-group"><label>Unit Price (₹) *</label><input type="number" name="unit_price" step="0.01" min="0" required></div>
      <div class="form-group"><label>Stock Qty *</label><input type="number" name="stock_qty" min="0" value="0" required></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save</button>
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('af').classList.add('hidden')">Cancel</button>
    </div>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>ID</th><th>Part Name</th><th>Code</th><th>Price</th><th>Stock</th><th>Status</th><th>Restock</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td class="text-muted"><?=h($r['part_id'])?></td>
      <td><?=h($r['part_name'])?></td>
      <td><code><?=h($r['part_code']??'—')?></code></td>
      <td>₹<?=format_inr($r['unit_price'],2)?></td>
      <td><?=$r['stock_qty']?></td>
      <td>
        <?php if($r['stock_qty']<=0): ?><span class="badge badge-cancelled">Out of Stock</span>
        <?php elseif($r['stock_qty']<10): ?><span class="badge badge-pending">Low Stock</span>
        <?php else: ?><span class="badge badge-completed">Available</span>
        <?php endif; ?>
      </td>
      <td>
        <form method="POST" style="display:flex;gap:4px;align-items:center">
          <input type="hidden" name="action" value="restock">
          <input type="hidden" name="part_id" value="<?=$r['part_id']?>">
          <input type="number" name="add_qty" value="10" min="1" style="width:55px;padding:4px;font-size:12px">
          <button type="submit" class="btn btn-sm btn-secondary">+Add</button>
        </form>
      </td>
      <td><a href="?delete=<?=$r['part_id']?>" class="btn btn-sm btn-danger confirm-delete">Del</a></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div>
</div>
<?php require_once 'includes/footer.php'; ?>
