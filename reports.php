<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB();

$revenueMonthly = oraQuery($db,
    "SELECT * FROM (SELECT TO_CHAR(b.issue_date,'YYYY-MM') AS month,
            COUNT(*) AS bills,
            SUM(b.grand_total) AS revenue,
            SUM(CASE WHEN b.payment_status='Paid' THEN b.grand_total ELSE 0 END) AS collected
     FROM bills b
     GROUP BY TO_CHAR(b.issue_date,'YYYY-MM')
     ORDER BY month DESC)
     WHERE ROWNUM <= 12");

$topCustomers = oraQuery($db,
    "SELECT * FROM (SELECT c.full_name, SUM(b.grand_total) AS total_spend, COUNT(b.bill_id) AS services
     FROM bills b
     JOIN service_records sr ON b.service_id  = sr.service_id
     JOIN vehicles v         ON sr.vehicle_id  = v.vehicle_id
     JOIN customers c        ON v.customer_id  = c.customer_id
     WHERE b.payment_status = 'Paid'
     GROUP BY c.customer_id, c.full_name
     ORDER BY total_spend DESC)
     WHERE ROWNUM <= 10");

$serviceBreakdown = oraQuery($db,
    "SELECT * FROM (SELECT service_type, COUNT(*) AS cnt,
            SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END) AS done
     FROM service_records
     GROUP BY service_type
     ORDER BY cnt DESC)
     WHERE ROWNUM <= 10");

$empPerf = oraQuery($db,
    "SELECT e.full_name, e.role, COUNT(sr.service_id) AS jobs,
            SUM(CASE WHEN sr.status='Completed' THEN 1 ELSE 0 END) AS completed
     FROM employees e
     LEFT JOIN service_records sr ON e.employee_id = sr.employee_id
     GROUP BY e.employee_id, e.full_name, e.role
     ORDER BY jobs DESC");

$lowStock = oraQuery($db,
    "SELECT * FROM spare_parts WHERE stock_qty < 10 ORDER BY stock_qty ASC");

$orderSpend = oraQuery($db,
    "SELECT supplier_name,
            COUNT(*) AS orders,
            SUM(total_price) AS total_spent,
            SUM(CASE WHEN order_status='Received' THEN total_price ELSE 0 END) AS received_value
     FROM vw_parts_orders
     GROUP BY supplier_name
     ORDER BY total_spent DESC");

$totalRevenue = oraQuery($db,"SELECT NVL(SUM(grand_total),0) AS n FROM bills WHERE payment_status='Paid'")[0]['n'];
$totalOrders  = oraQuery($db,"SELECT NVL(SUM(total_price),0) AS n FROM parts_orders")[0]['n'];
?>

<div class="page-header">
  <h1>REPORTS &amp; ANALYTICS</h1>
  <button class="btn btn-secondary" onclick="exportTablesToCSV('VSMS_Reports.csv')">↓ Export to CSV</button>
</div>
  <span class="text-muted">Generated: <?= date('d M Y, H:i') ?></span>
</div>

<div class="page-content">

  <!-- Summary Cards -->
  <div class="stats-grid" style="margin-bottom:28px">
    <div class="stat-card green">
      <div class="stat-value">₹<?= format_inr($totalRevenue, 0) ?></div>
      <div class="stat-label">Total Revenue Collected</div>
    </div>
    <div class="stat-card blue">
      <div class="stat-value">₹<?= format_inr($totalOrders, 0) ?></div>
      <div class="stat-label">Total Parts Ordered</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= oraQuery($db,"SELECT COUNT(*) AS n FROM customers")[0]['n'] ?></div>
      <div class="stat-label">Total Customers</div>
    </div>
    <div class="stat-card">
      <div class="stat-value"><?= oraQuery($db,"SELECT COUNT(*) AS n FROM service_records WHERE status='Completed'")[0]['n'] ?></div>
      <div class="stat-label">Completed Services</div>
    </div>
  </div>

  <!-- Monthly Revenue -->
  <div class="section-title">Monthly Revenue</div>
  <div class="table-wrap" style="margin-bottom:28px">
    <table>
      <thead><tr><th>Month</th><th>Bills Generated</th><th>Total Billed</th><th>Collected</th><th>Outstanding</th></tr></thead>
      <tbody>
        <?php foreach ($revenueMonthly as $r): ?>
        <tr>
          <td><strong><?= h($r['month']) ?></strong></td>
          <td><?= h($r['bills']) ?></td>
          <td>₹<?= format_inr($r['revenue'], 2) ?></td>
          <td class="text-green">₹<?= format_inr($r['collected'], 2) ?></td>
          <td class="text-red">₹<?= format_inr($r['revenue'] - $r['collected'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px">

    <!-- Top Customers -->
    <div>
      <div class="section-title">Top Customers by Spend</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Customer</th><th>Services</th><th>Total Spend</th></tr></thead>
          <tbody>
            <?php foreach ($topCustomers as $r): ?>
            <tr>
              <td><?= h($r['full_name']) ?></td>
              <td><?= h($r['services']) ?></td>
              <td class="text-accent">₹<?= format_inr($r['total_spend'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Employee Performance -->
    <div>
      <div class="section-title">Employee Performance</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Employee</th><th>Role</th><th>Total Jobs</th><th>Completed</th></tr></thead>
          <tbody>
            <?php foreach ($empPerf as $r): ?>
            <tr>
              <td><?= h($r['full_name']) ?></td>
              <td><span class="badge badge-in-progress"><?= h($r['role']) ?></span></td>
              <td><?= h($r['jobs']) ?></td>
              <td class="text-green"><?= h($r['completed'] ?? 0) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Service Breakdown -->
  <div class="section-title">Service Type Breakdown</div>
  <div class="table-wrap" style="margin-bottom:28px">
    <table>
      <thead><tr><th>Service Type</th><th>Total</th><th>Completed</th><th>Completion Rate</th></tr></thead>
      <tbody>
        <?php foreach ($serviceBreakdown as $r):
          $rate = $r['cnt'] > 0 ? round($r['done'] / $r['cnt'] * 100) : 0;
        ?>
        <tr>
          <td><?= h($r['service_type']) ?></td>
          <td><?= h($r['cnt']) ?></td>
          <td><?= h($r['done']) ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:8px">
              <div style="flex:1;height:6px;background:var(--border);border-radius:3px">
                <div style="height:100%;width:<?= $rate ?>%;background:var(--green);border-radius:3px"></div>
              </div>
              <span style="font-size:12px;min-width:32px"><?= $rate ?>%</span>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Supplier Spend -->
  <div class="section-title">Parts Procurement by Supplier</div>
  <div class="table-wrap" style="margin-bottom:28px">
    <table>
      <thead><tr><th>Supplier</th><th>Orders Placed</th><th>Total Ordered Value</th><th>Received Value</th><th>Pending</th></tr></thead>
      <tbody>
        <?php foreach ($orderSpend as $r): ?>
        <tr>
          <td><span class="supplier-tag"><?= h($r['supplier_name']) ?></span></td>
          <td><?= h($r['orders']) ?></td>
          <td class="text-accent">₹<?= format_inr($r['total_spent'], 2) ?></td>
          <td class="text-green">₹<?= format_inr($r['received_value'], 2) ?></td>
          <td class="text-red">₹<?= format_inr($r['total_spent'] - $r['received_value'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Low Stock Alert -->
  <?php if ($lowStock): ?>
  <div class="section-title" style="color:var(--red)">⚠ Low Stock Alert</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Part</th><th>Code</th><th>Unit Price</th><th>Stock Remaining</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($lowStock as $r): ?>
        <tr>
          <td><?= h($r['part_name']) ?></td>
          <td><code><?= h($r['part_code'] ?? '—') ?></code></td>
          <td>₹<?= format_inr($r['unit_price'], 2) ?></td>
          <td><span class="badge <?= $r['stock_qty'] <= 0 ? 'badge-cancelled' : 'badge-pending' ?>"><?= h($r['stock_qty']) ?> left</span></td>
          <td><a href="order_parts.php" class="btn btn-blue btn-sm">🛒 Order</a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

</div>

<script>
function exportTablesToCSV(filename) {
    let csv = [];
    const tables = document.querySelectorAll("table");
    
    tables.forEach((table, index) => {
        csv.push('""'); // Empty line padding
        const title = table.parentElement.previousElementSibling;
        if (title && title.classList.contains('section-title')) {
            csv.push('"' + title.innerText + '"');
        }
        
        const rows = table.querySelectorAll("tr");
        for (let i = 0; i < rows.length; i++) {
            let row = [], cols = rows[i].querySelectorAll("td, th");
            for (let j = 0; j < cols.length; j++) 
                row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
            csv.push(row.join(","));
        }
    });
    
    let csvFile = new Blob([csv.join("\n")], {type: "text/csv"});
    let dl = document.createElement("a");
    dl.download = filename;
    dl.href = window.URL.createObjectURL(csvFile);
    dl.style.display = "none";
    document.body.appendChild(dl);
    dl.click();
}
</script>

<?php require_once 'includes/footer.php'; ?>
