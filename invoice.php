<?php
require_once 'includes/db.php';
session_start();
if (!isset($_SESSION['user_id'])) die('Unauthorized Access');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) die('Invalid ID provided');

$db = getDB();
$billData = oraQuery($db, "SELECT * FROM vw_billing_summary WHERE bill_id = :id", ['id'=>$id]);
if (empty($billData)) die('Bill not found in the database. Please verify the bill ID.');
$bill = $billData[0];

// Fetch deeper linked data for customer and vehicle aesthetics
$details = oraQuery($db, "
    SELECT c.full_name, c.phone, c.email, c.address, v.make, v.model, v.number_plate, sr.service_type, sr.description
    FROM bills b
    JOIN service_records sr ON b.service_id = sr.service_id
    JOIN vehicles v ON sr.vehicle_id = v.vehicle_id
    JOIN customers c ON v.customer_id = c.customer_id
    WHERE b.bill_id = :id
", ['id'=>$id])[0];

// Load custom business settings
$settings = [];
if (file_exists('includes/shop_settings.json')) {
    $settings = json_decode(file_get_contents('includes/shop_settings.json'), true);
}
$shopName = $settings['shop_name'] ?? 'Prime Auto Repair';
$shopAddress = $settings['shop_address'] ?? "123 Mechanic Avenue, Suite 100\nMetropolis City, IN 400012";
$shopPhone = $settings['shop_phone'] ?? '+91 9988776655';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice #<?= $bill['bill_id'] ?> — VSMS</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'DM Sans', sans-serif; background: #eaeff2; color: #111; margin: 0; padding: 40px; font-size: 14px; }
    .invoice-box { max-width: 800px; margin: auto; padding: 50px; background:#fff; border: 1px solid #d4dce0; box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08); border-radius: 8px;}
    table { width: 100%; border-collapse: collapse; margin-top: 30px; }
    table th, table td { padding: 14px; text-align: left; border-bottom: 1px solid #eee; }
    table th { background: #f8fafb; color: #556270; font-weight: 600; text-transform:uppercase; font-size:12px; letter-spacing:1px;}
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 3px solid #1a2331; padding-bottom: 20px; }
    .header h1 { margin: 0; font-size: 32px; color: #1a2331; text-transform: uppercase; letter-spacing: 2px; }
    .header p { color: #555; margin: 5px 0 0; }
    .totals { width: 300px; margin-left: auto; margin-top: 30px; }
    .totals div { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
    .totals div.grand { font-size: 22px; font-weight: bold; color: #1a2331; border-bottom: none; border-top: 2px solid #1a2331; padding-top: 15px; }
    .badge { padding:4px 8px; border-radius:4px; font-weight:bold; font-size:12px; text-transform:uppercase; }
    .badge-paid { background:#dcfce7; color:#166534; }
    .badge-unpaid { background:#fee2e2; color:#991b1b; }
    .badge-partial { background:#fef08a; color:#854d0e; }
    .btn-print { display: flex; justify-content:center; align-items:center; width: 180px; margin: 30px auto; padding: 14px; background: #2563eb; color: white; border-radius: 6px; border: none; cursor: pointer; font-size:16px; font-weight:600; box-shadow:0 4px 10px rgba(37,99,235,0.3); transition:all 0.2s;}
    .btn-print:hover { background: #1d4ed8; }
    @media print { .btn-print, .no-print { display: none !important; } .invoice-box { border: none; box-shadow: none; padding: 0; } body { padding: 0; background:#fff;} }
  </style>
</head>
<body>

<div class="invoice-box">
  <div class="header">
    <div>
      <h1>TAX INVOICE</h1>
      <p>Invoice #: <strong>INV-<?= str_pad($bill['bill_id'], 5, '0', STR_PAD_LEFT) ?></strong></p>
      <p>Issue Date: <?= h($bill['issue_date']) ?></p>
    </div>
    <div style="text-align: right;">
      <h2 style="margin: 0; font-size: 22px; color:#1a2331;"><?= h($shopName) ?></h2>
      <p style="line-height:1.6"><?= nl2br(h($shopAddress)) ?><br>📞 <?= h($shopPhone) ?></p>
    </div>
  </div>

  <div style="display: flex; justify-content: space-between; margin-bottom: 30px;">
    <div>
      <h3 style="color: #8892a0; font-size: 12px; text-transform: uppercase; margin-bottom: 8px;">Billed To:</h3>
      <p style="margin: 0; font-weight: 700; font-size: 18px; color:#111;"><?= h($details['full_name']) ?></p>
      <p style="margin: 5px 0 0; color: #555;">📞 <?= h($details['phone']) ?></p>
      <?php if($details['email']): ?><p style="margin: 5px 0 0; color: #555;">✉️ <?= h($details['email']) ?></p><?php endif; ?>
    </div>
    <div style="text-align: right; background:#f8fafb; padding:15px; border-radius:6px; border:1px solid #eee;">
      <h3 style="color: #8892a0; font-size: 12px; text-transform: uppercase; margin-bottom: 8px;">Vehicle Serviced:</h3>
      <p style="margin: 0; font-weight: bold; font-size: 16px; color:#111;"><?= h($details['make']) ?> <?= h($details['model']) ?></p>
      <p style="margin: 5px 0 0; color: #555; background:#fff; padding:4px 8px; border:1px solid #ccc; display:inline-block; border-radius:4px; font-family:monospace; font-weight:bold; letter-spacing:1px; margin-top:8px;"><?= h($details['number_plate']) ?></p>
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th>Service Description</th>
        <th>Category</th>
        <th style="text-align: right;">Amount (₹)</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>
            <strong><?= h($details['service_type']) ?></strong><br>
            <span style="font-size:12px; color:#666;">Professional labour mechanics charge</span>
        </td>
        <td>Labour</td>
        <td style="text-align: right; font-weight:500;"><?= format_inr($bill['labour_total'], 2) ?></td>
      </tr>
      <?php if ($bill['parts_total'] > 0): ?>
      <tr>
        <td>
            <strong>Inventory Spare Parts</strong><br>
            <span style="font-size:12px; color:#666;">Authorized replacement parts requested</span>
        </td>
        <td>Parts</td>
        <td style="text-align: right; font-weight:500;"><?= format_inr($bill['parts_total'], 2) ?></td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="totals">
    <div><span>Subtotal:</span> <span style="font-weight:500;">₹<?= format_inr($bill['labour_total'] + $bill['parts_total'], 2) ?></span></div>
    <div><span>GST Tax (<?= format_inr($bill['tax_percent'], 1) ?>%):</span> <span style="font-weight:500;">₹<?= format_inr((($bill['labour_total'] + $bill['parts_total']) * $bill['tax_percent']) / 100, 2) ?></span></div>
    <div class="grand"><span>Grand Total:</span> <span style="color:#2563eb;">₹<?= format_inr($bill['grand_total'], 2) ?></span></div>
  </div>

  <div style="margin-top: 60px; padding-top: 20px; border-top: 1px solid #eee; color: #555; font-size: 13px; text-align: center; line-height:1.6;">
    <div style="margin-bottom:15px;">
        Payment Status: <span class="badge badge-<?=strtolower($bill['payment_status'])?>"><?= h($bill['payment_status']) ?></span> &nbsp;|&nbsp; Payment Method: <strong><?= h($bill['payment_mode']) ?></strong>
    </div>
    Thank you for choosing <?= h($shopName) ?>! <br>All parts and labor carry a 30-day limited guarantee.
  </div>
</div>

<button class="btn-print" onclick="window.print()">🖨️ Print Invoice</button>

</body>
</html>
