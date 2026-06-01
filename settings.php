<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$msg = '';
$settingsFile = 'includes/shop_settings.json';

// Get current settings
$settings = [];
if (file_exists($settingsFile)) {
    $settings = json_decode(file_get_contents($settingsFile), true);
}

// Ensure defaults
$settings['shop_name'] = $settings['shop_name'] ?? 'Prime Auto Repair';
$settings['shop_address'] = $settings['shop_address'] ?? '';
$settings['shop_phone'] = $settings['shop_phone'] ?? '';
$settings['shop_email'] = $settings['shop_email'] ?? '';
$settings['shop_tax_no'] = $settings['shop_tax_no'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $updated = [
        'shop_name' => trim($_POST['shop_name'] ?? ''),
        'shop_address' => trim($_POST['shop_address'] ?? ''),
        'shop_phone' => trim($_POST['shop_phone'] ?? ''),
        'shop_email' => trim($_POST['shop_email'] ?? ''),
        'shop_tax_no' => trim($_POST['shop_tax_no'] ?? '')
    ];
    
    if (file_put_contents($settingsFile, json_encode($updated, JSON_PRETTY_PRINT))) {
        $msg = '<div class="alert alert-success">Shop settings updated successfully!</div>';
        $settings = $updated; // Update view
    } else {
        $msg = '<div class="alert alert-error">Failed to save settings. Please check folder permissions.</div>';
    }
}
?>

<div class="page-header">
  <h1>SHOP SETTINGS</h1>
</div>

<div class="page-content">
  <?= $msg ?>
  
  <div class="form-card" style="max-width: 600px; margin: 0;">
    <div class="section-title">Business Information</div>
    <p style="color:var(--muted); font-size:14px; margin-top:-10px; margin-bottom:20px;">
      These details will be publicly printed on your customer invoices and reports.
    </p>
    
    <form method="POST">
      <div class="form-grid" style="grid-template-columns: 1fr;">
        <div class="form-group full">
          <label>Shop Name</label>
          <input type="text" name="shop_name" value="<?= h($settings['shop_name']) ?>" required>
        </div>
        
        <div class="form-group full">
          <label>Address</label>
          <textarea name="shop_address" rows="3" required><?= h($settings['shop_address']) ?></textarea>
        </div>
        
        <div class="form-group full">
          <label>Contact Phone</label>
          <input type="text" name="shop_phone" value="<?= h($settings['shop_phone']) ?>" required>
        </div>
        
        <div class="form-group full">
          <label>Contact Email</label>
          <input type="email" name="shop_email" value="<?= h($settings['shop_email']) ?>">
        </div>
        
        <div class="form-group full">
          <label>Tax ID / GST No.</label>
          <input type="text" name="shop_tax_no" value="<?= h($settings['shop_tax_no']) ?>">
        </div>
      </div>
      
      <div class="form-actions" style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">Save Configuration</button>
      </div>
    </form>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
