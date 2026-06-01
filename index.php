<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB();

$stats = [
  'customers'  => oraQuery($db, "SELECT COUNT(*) AS n FROM customers")[0]['n'],
  'vehicles'   => oraQuery($db, "SELECT COUNT(*) AS n FROM vehicles")[0]['n'],
  'services'   => oraQuery($db, "SELECT COUNT(*) AS n FROM service_records")[0]['n'],
  'active'     => oraQuery($db, "SELECT COUNT(*) AS n FROM service_records WHERE status IN ('Pending','In Progress')")[0]['n'],
  'revenue'    => oraQuery($db, "SELECT NVL(SUM(grand_total),0) AS n FROM bills WHERE payment_status='Paid'")[0]['n'],
  'low_stock'  => oraQuery($db, "SELECT COUNT(*) AS n FROM spare_parts WHERE stock_qty < 10")[0]['n'],
  'orders'     => oraQuery($db, "SELECT COUNT(*) AS n FROM parts_orders WHERE order_status IN ('Ordered','Shipped')")[0]['n'],
];

$recent = oraQuery($db, "SELECT * FROM (SELECT * FROM vw_service_summary ORDER BY service_date DESC) WHERE ROWNUM <= 8");
$pending_orders = oraQuery($db, "SELECT * FROM (SELECT * FROM vw_parts_orders WHERE order_status IN ('Ordered','Shipped') ORDER BY order_date DESC) WHERE ROWNUM <= 5");
?>

<div class="page-header">
  <h1>DASHBOARD</h1>
  <span class="text-muted"><?= date('D, d M Y') ?></span>
</div>
<div class="page-content">

  <div class="stats-grid">
    <div class="stat-card">         <div class="stat-value"><?= $stats['customers'] ?></div><div class="stat-label">Customers</div></div>
    <div class="stat-card blue">    <div class="stat-value"><?= $stats['vehicles']  ?></div><div class="stat-label">Vehicles</div></div>
    <div class="stat-card green">   <div class="stat-value"><?= $stats['services']  ?></div><div class="stat-label">Total Services</div></div>
    <div class="stat-card">         <div class="stat-value"><?= $stats['active']    ?></div><div class="stat-label">Active / Pending</div></div>
    <div class="stat-card green">   <div class="stat-value">₹<?= format_inr($stats['revenue'],0) ?></div><div class="stat-label">Revenue Collected</div></div>
    <div class="stat-card red">     <div class="stat-value"><?= $stats['low_stock'] ?></div><div class="stat-label">Low Stock Parts</div></div>
    <div class="stat-card purple">  <div class="stat-value"><?= $stats['orders']    ?></div><div class="stat-label">Pending Orders</div></div>
  </div>

  <div class="section-title">Recent Services</div>
  <div class="table-wrap" style="margin-bottom:28px">
    <table>
      <thead><tr><th>#</th><th>Customer</th><th>Vehicle</th><th>Plate</th><th>Service</th><th>Employee</th><th>Date</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): $sc=strtolower(str_replace(' ','-',$r['status'])); ?>
        <tr>
          <td class="text-muted"><?= h($r['service_id']) ?></td>
          <td><?= h($r['customer_name']) ?></td>
          <td><?= h($r['vehicle']) ?></td>
          <td><code><?= h($r['number_plate']) ?></code></td>
          <td><?= h($r['service_type']) ?></td>
          <td><?= h($r['assigned_employee']) ?></td>
          <td><?= h($r['service_date']) ?></td>
          <td><span class="badge badge-<?= $sc ?>"><?= h($r['status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pending_orders): ?>
  <div class="section-title">Pending Parts Orders</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Part</th><th>Supplier</th><th>Qty</th><th>Total</th><th>Ordered By</th><th>Expected</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($pending_orders as $o): $sc=strtolower($o['order_status']); ?>
        <tr>
          <td class="text-muted"><?= h($o['order_id']) ?></td>
          <td><?= h($o['part_name']) ?></td>
          <td><span class="supplier-tag"><?= h($o['supplier_name']) ?></span></td>
          <td><?= h($o['quantity']) ?></td>
          <td class="text-accent">₹<?= format_inr($o['total_price'],2) ?></td>
          <td><?= h($o['ordered_by']) ?></td>
          <td><?= h($o['expected_date']) ?></td>
          <td><span class="badge badge-<?= $sc ?>"><?= h($o['order_status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</div>
<?php require_once 'includes/footer.php'; ?>
