<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB(); $msg = '';

// Update status
if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='upd_status') {
    $sid  = (int)$_POST['order_id'];
    $st   = $_POST['new_status'];
    $rcv  = $st === 'Received' ? "TO_DATE('".date('Y-m-d')."','YYYY-MM-DD')" : "received_date";
    oraExec($db,"UPDATE parts_orders SET order_status=:s WHERE order_id=:id",['s'=>$st,'id'=>$sid]);
    if ($st === 'Received') {
        oraExec($db,"UPDATE parts_orders SET received_date=SYSDATE WHERE order_id=:id",['id'=>$sid]);
    }
    $msg = '<div class="alert alert-success">Order status updated.</div>';
}

// Delete
if (isset($_GET['delete'])) {
    oraExec($db,"DELETE FROM parts_orders WHERE order_id=:id",['id'=>(int)$_GET['delete']]);
    $msg = '<div class="alert alert-success">Order deleted.</div>';
}

// Filter
$fstatus = $_GET['status'] ?? '';
$w = $fstatus ? "WHERE order_status='$fstatus'" : '';
$rows = oraQuery($db,"SELECT * FROM vw_parts_orders $w ORDER BY order_date DESC");

$statuses = ['Ordered','Shipped','Received','Cancelled'];
$stats = [];
foreach ($statuses as $s) {
    $r = oraQuery($db,"SELECT COUNT(*) AS n, NVL(SUM(total_price),0) AS tot FROM parts_orders WHERE order_status=:s",['s'=>$s]);
    $stats[$s] = $r[0];
}
?>

<div class="page-header">
  <h1>PARTS ORDERS</h1>
  <a href="order_parts.php" class="btn btn-blue">🛒 New Order</a>
</div>

<div class="page-content">
<?=$msg?>

<!-- Stats -->
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);margin-bottom:24px">
  <?php
  $sc_map = ['Ordered'=>'','Shipped'=>'blue','Received'=>'green','Cancelled'=>'red'];
  foreach ($statuses as $s):
  ?>
  <div class="stat-card <?=$sc_map[$s]?>">
    <div class="stat-value"><?=$stats[$s]['n']?></div>
    <div class="stat-label"><?=$s?></div>
    <div style="font-size:11px;color:var(--muted);margin-top:4px">₹<?=format_inr($stats[$s]['tot'],0)?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Filter -->
<div class="filter-bar" style="margin-bottom:16px">
  <form method="GET" style="display:flex;gap:8px">
    <select name="status">
      <option value="">All Statuses</option>
      <?php foreach ($statuses as $s): ?><option value="<?=$s?>" <?=$fstatus==$s?'selected':''?>><?=$s?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <a href="parts_orders.php" class="btn btn-secondary btn-sm">Reset</a>
  </form>
</div>

<div class="table-wrap"><table>
  <thead>
    <tr><th>#</th><th>Part Name</th><th>OEM #</th><th>Category</th><th>Supplier</th><th>Qty</th><th>Unit</th><th>Total</th><th>Ordered By</th><th>Order Date</th><th>Expected</th><th>Received</th><th>Linked Job</th><th>Status</th><th>Actions</th></tr>
  </thead>
  <tbody>
    <?php foreach ($rows as $r):
      $sc = strtolower($r['order_status']);
    ?>
    <tr>
      <td class="text-muted"><?=h($r['order_id'])?></td>
      <td><strong><?=h($r['part_name'])?></strong></td>
      <td><?=$r['part_number']?'<code>'.h($r['part_number']).'</code>':'—'?></td>
      <td><span class="badge badge-in-progress" style="font-size:10px"><?=h($r['category'])?></span></td>
      <td><span class="supplier-tag"><?=h($r['supplier_name'])?></span></td>
      <td><?=h($r['quantity'])?></td>
      <td>₹<?=format_inr($r['unit_price'],2)?></td>
      <td class="text-accent"><strong>₹<?=format_inr($r['total_price'],2)?></strong></td>
      <td><?=h($r['ordered_by'])?></td>
      <td><?=h($r['order_date'])?></td>
      <td><?=h($r['expected_date']??'—')?></td>
      <td><?=$r['received_date']?'<span class="text-green">'.h($r['received_date']).'</span>':'—'?></td>
      <td><?=$r['service_id']?'<a href="service_records.php?vid=0">#'.h($r['service_id']).'</a>':'<span class="text-muted">Stock</span>'?></td>
      <td>
        <form method="POST" style="display:inline">
          <input type="hidden" name="action"   value="upd_status">
          <input type="hidden" name="order_id" value="<?=$r['order_id']?>">
          <select name="new_status" onchange="this.form.submit()" style="padding:3px 6px;font-size:12px;background:var(--surface2);border:1px solid var(--border);color:var(--text);border-radius:3px">
            <?php foreach ($statuses as $s): ?><option <?=$r['order_status']==$s?'selected':''?>><?=$s?></option><?php endforeach; ?>
          </select>
        </form>
      </td>
      <td>
        <a href="?delete=<?=$r['order_id']?>" class="btn btn-sm btn-danger confirm-delete">Del</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div>

</div>
<?php require_once 'includes/footer.php'; ?>
