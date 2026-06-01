<?php
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
require_once 'includes/auth.php';

// RBAC Configuration Matrix
$pageAccess = [
    'index'             => [], 
    'customers'         => ['Supervisor', 'Receptionist'],
    'vehicles'          => ['Supervisor', 'Receptionist', 'Mechanic', 'Electrician', 'Painter'],
    'service_records'   => [], 
    'employees'         => ['Supervisor'],
    'spare_parts'       => ['Supervisor', 'Mechanic', 'Electrician', 'Painter'],
    'billing'           => ['Supervisor', 'Receptionist'],
    'parts_catalog'     => ['Supervisor', 'Mechanic', 'Electrician', 'Painter'],
    'order_parts'       => ['Supervisor', 'Mechanic', 'Electrician', 'Painter'],
    'parts_orders'      => ['Supervisor', 'Mechanic', 'Electrician', 'Painter'],
    'reports'           => ['Supervisor'],
    'settings'          => ['Supervisor']
];

if ($currentPage !== 'login' && $currentPage !== 'register' && $currentPage !== 'forgot_password') {
    requireLogin();
    
    // RBAC Security Check
    if (isset($pageAccess[$currentPage])) {
        if (!hasRole($pageAccess[$currentPage])) {
            die('<!DOCTYPE html><html><head><title>Access Denied</title><link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@600&family=DM+Sans:wght@400&display=swap" rel="stylesheet"><link rel="stylesheet" href="css/style.css"></head>
                 <body style="display:flex;justify-content:center;align-items:center;height:100vh;background:var(--bg);color:var(--text);font-family:\'DM Sans\',sans-serif;">
                 <div style="text-align:center;background:var(--surface);padding:50px;border-radius:8px;border:1px solid var(--border);max-width:400px;box-shadow:0 10px 30px rgba(0,0,0,0.5);">
                 <h1 style="color:var(--red);margin-top:0;font-family:\'Rajdhani\',sans-serif;font-size:32px;">ACCESS DENIED</h1>
                 <p style="color:var(--muted);margin-bottom:30px;line-height:1.5;">You do not have the required role permissions to view this page or perform this action.</p>
                 <a href="index.php" class="btn btn-primary" style="text-decoration:none;display:inline-block;">Return to Dashboard</a>
                 </div></body></html>');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VSMS — <?= ucwords(str_replace('_',' ',$currentPage)) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="sidebar">
  <div class="logo">
    <span class="logo-gear">⚙</span>
    <span class="logo-text">VSMS</span>
  </div>
  <ul class="nav-links">
    <li><a href="index.php"              class="<?=$currentPage==='index'            ?'active':''?>"><span>◈</span> Dashboard</a></li>
    
    <?php if (hasRole($pageAccess['customers'])): ?>
    <li><a href="customers.php"          class="<?=$currentPage==='customers'        ?'active':''?>"><span>◉</span> Customers</a></li>
    <?php endif; ?>
    
    <?php if (hasRole($pageAccess['vehicles'])): ?>
    <li><a href="vehicles.php"           class="<?=$currentPage==='vehicles'         ?'active':''?>"><span>◈</span> Vehicles</a></li>
    <?php endif; ?>
    
    <li><a href="service_records.php"    class="<?=$currentPage==='service_records'  ?'active':''?>"><span>◉</span> Services</a></li>
    
    <?php if (hasRole($pageAccess['employees'])): ?>
    <li><a href="employees.php"          class="<?=$currentPage==='employees'        ?'active':''?>"><span>◈</span> Employees</a></li>
    <?php endif; ?>
    
    <?php if (hasRole($pageAccess['spare_parts'])): ?>
    <li><a href="spare_parts.php"        class="<?=$currentPage==='spare_parts'      ?'active':''?>"><span>◉</span> Spare Parts</a></li>
    <?php endif; ?>
    
    <?php if (hasRole($pageAccess['billing'])): ?>
    <li><a href="billing.php"            class="<?=$currentPage==='billing'          ?'active':''?>"><span>◈</span> Billing</a></li>
    <?php endif; ?>
    
    <?php if (hasRole($pageAccess['parts_catalog'])): ?>
    <li class="nav-section">PROCUREMENT</li>
    <li><a href="parts_catalog.php"      class="<?=$currentPage==='parts_catalog'    ?'active':''?>"><span>◉</span> Parts Catalog</a></li>
    <li><a href="order_parts.php"        class="<?=$currentPage==='order_parts'      ?'active':''?>"><span>◈</span> Order Parts</a></li>
    <li><a href="parts_orders.php"       class="<?=$currentPage==='parts_orders'     ?'active':''?>"><span>◉</span> My Orders</a></li>
    <?php endif; ?>
    
    <?php if (hasRole($pageAccess['reports'])): ?>
    <li class="nav-section">REPORTS</li>
    <li><a href="reports.php"            class="<?=$currentPage==='reports'          ?'active':''?>"><span>◈</span> Reports</a></li>
    <?php endif; ?>
    
    <?php if (hasRole($pageAccess['settings'])): ?>
    <li class="nav-section">CONFIGURATION</li>
    <li><a href="settings.php"           class="<?=$currentPage==='settings'         ?'active':''?>"><span>⚙</span> Settings</a></li>
    <?php endif; ?>
  </ul>
  <div class="sidebar-footer">
    <div style="font-size: 13px; color: #fff; margin-bottom: 5px;">
      User: <?= htmlspecialchars($_SESSION['username'] ?? '') ?>
      <br><span style="color: var(--accent); font-size: 11px"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span>
    </div>
    <a href="logout.php" style="color: #aaa; text-decoration: none; font-size: 12px;">Logout</a>
    <hr style="border:0; border-top: 1px solid #333; margin: 10px 0;">
    Oracle · B.Tech CSE · DBMS
  </div>
</nav>

<div class="main">
