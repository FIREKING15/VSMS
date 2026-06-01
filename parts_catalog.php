<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB(); $msg = '';

// Add to catalog
if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='add') {
    $ok = oraExec($db,
        "INSERT INTO parts_catalog (supplier_name,supplier_url,part_name,part_number,category,compatible_make,compatible_model,unit_price,image_url)
         VALUES (:sup,:url,:pname,:pnum,:cat,:cmake,:cmodel,:price,:img)",
        ['sup'=>$_POST['supplier_name'],'url'=>$_POST['supplier_url'],
         'pname'=>$_POST['part_name'],'pnum'=>$_POST['part_number'],
         'cat'=>$_POST['category'],'cmake'=>$_POST['compatible_make'],
         'cmodel'=>$_POST['compatible_model'],'price'=>(float)$_POST['unit_price'],
         'img'=>$_POST['image_url']]);
    $msg = $ok ? '<div class="alert alert-success">Part added to catalog.</div>'
               : '<div class="alert alert-error">Insert failed.</div>';
}
if (isset($_GET['delete'])) {
    oraExec($db,"DELETE FROM parts_catalog WHERE catalog_id=:id",['id'=>(int)$_GET['delete']]);
    $msg = '<div class="alert alert-success">Removed from catalog.</div>';
}

// Filters
$fcat   = $_GET['cat']   ?? '';
$fmake  = $_GET['make']  ?? '';
$fsup   = $_GET['sup']   ?? '';
$where  = "WHERE is_active=1";
if ($fcat)  $where .= " AND LOWER(category)=LOWER('".addslashes($fcat)."')";
if ($fmake) $where .= " AND (LOWER(compatible_make)=LOWER('".addslashes($fmake)."') OR compatible_make IS NULL)";
if ($fsup)  $where .= " AND LOWER(supplier_name) LIKE LOWER('%".addslashes($fsup)."%')";

$items = oraQuery($db,"SELECT * FROM parts_catalog $where ORDER BY category,part_name");
$cats  = oraQuery($db,"SELECT DISTINCT category FROM parts_catalog WHERE is_active=1 ORDER BY category");
$makes = oraQuery($db,"SELECT DISTINCT compatible_make FROM parts_catalog WHERE is_active=1 AND compatible_make IS NOT NULL ORDER BY compatible_make");
$sups  = oraQuery($db,"SELECT DISTINCT supplier_name FROM parts_catalog WHERE is_active=1 ORDER BY supplier_name");

// Category emoji map
$catIcons = ['Engine'=>'🔧','Brakes'=>'🛑','Electrical'=>'⚡','Filters'=>'🌬','Ignition'=>'🔥','Cooling'=>'❄','Wipers'=>'🌧','Clutch'=>'⚙','Exhaust'=>'💨','Suspension'=>'🔩'];

function catIcon($c, $map) { return $map[$c] ?? '🔩'; }
?>

<div class="page-header">
  <h1>PARTS CATALOG</h1>
  <div style="display:flex;gap:8px">
    <a href="order_parts.php" class="btn btn-blue">🛒 Order Parts</a>
    <button class="btn btn-primary" onclick="document.getElementById('af').classList.toggle('hidden')">+ Add to Catalog</button>
  </div>
</div>

<div class="page-content">
<?=$msg?>

<!-- FILTERS -->
<div class="filter-bar">
  <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
    <select name="cat">
      <option value="">All Categories</option>
      <?php foreach ($cats as $c): ?><option value="<?=h($c['category'])?>" <?=$fcat==$c['category']?'selected':''?>><?=h($c['category'])?></option><?php endforeach; ?>
    </select>
    <select name="make">
      <option value="">All Makes</option>
      <?php foreach ($makes as $m): ?><option value="<?=h($m['compatible_make'])?>" <?=$fmake==$m['compatible_make']?'selected':''?>><?=h($m['compatible_make'])?></option><?php endforeach; ?>
    </select>
    <select name="sup">
      <option value="">All Suppliers</option>
      <?php foreach ($sups as $s): ?><option value="<?=h($s['supplier_name'])?>" <?=$fsup==$s['supplier_name']?'selected':''?>><?=h($s['supplier_name'])?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    <a href="parts_catalog.php" class="btn btn-secondary btn-sm">Reset</a>
  </form>
</div>

<!-- ADD FORM -->
<div id="af" class="form-card hidden" style="margin-bottom:24px">
  <div class="section-title">Add Part to Catalog</div>
  <form method="POST"><input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="form-group"><label>Supplier Name *</label><input name="supplier_name" placeholder="Maruti Suzuki Genuine Parts" required></div>
      <div class="form-group"><label>Supplier URL</label><input name="supplier_url" type="url" placeholder="https://..."></div>
      <div class="form-group"><label>Part Name *</label><input name="part_name" required></div>
      <div class="form-group"><label>OEM Part Number</label><input name="part_number"></div>
      <div class="form-group"><label>Category *</label><input name="category" list="catlist" required>
        <datalist id="catlist"><option>Engine</option><option>Brakes</option><option>Electrical</option><option>Filters</option><option>Ignition</option><option>Cooling</option><option>Wipers</option><option>Clutch</option><option>Suspension</option></datalist>
      </div>
      <div class="form-group"><label>Unit Price (₹) *</label><input type="number" name="unit_price" step="0.01" min="0" required></div>
      <div class="form-group"><label>Compatible Make</label><input name="compatible_make" placeholder="Maruti, Honda, Universal..."></div>
      <div class="form-group"><label>Compatible Models</label><input name="compatible_model" placeholder="Swift, Baleno, City..."></div>
      <div class="form-group full"><label>Image URL</label><input name="image_url" type="url"></div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save to Catalog</button>
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('af').classList.add('hidden')">Cancel</button>
    </div>
  </form>
</div>

<!-- CATALOG GRID -->
<p class="text-muted" style="margin-bottom:14px;font-size:12px"><?=count($items)?> parts found</p>
<div class="catalog-grid">
  <?php foreach ($items as $p):
    $icon = catIcon($p['category'], $catIcons);
  ?>
  <div class="catalog-card">
    <div class="catalog-card-img">
      <?php if (!empty($p['image_url'])): ?>
        <img src="<?=h($p['image_url'])?>" alt="" style="max-height:110px;max-width:90%;object-fit:contain" onerror="this.style.display='none';this.nextSibling.style.display='block'">
        <span style="display:none;font-size:40px"><?=$icon?></span>
      <?php else: ?>
        <span><?=$icon?></span>
      <?php endif; ?>
    </div>
    <div class="catalog-card-body">
      <div class="catalog-card-name"><?=h($p['part_name'])?></div>
      <?php if ($p['part_number']): ?>
      <div class="catalog-card-part">OEM: <?=h($p['part_number'])?></div>
      <?php endif; ?>
      <div class="catalog-card-meta">
        <span class="badge badge-in-progress" style="font-size:10px"><?=h($p['category'])?></span>
        <?php if ($p['compatible_make']): ?>
        &nbsp;<span class="text-muted"><?=h($p['compatible_make'])?></span>
        <?php endif; ?>
      </div>
      <?php if ($p['compatible_model']): ?>
      <div class="catalog-card-meta text-muted" style="font-size:11px">Fits: <?=h($p['compatible_model'])?></div>
      <?php endif; ?>
      <div class="catalog-card-price">₹<?=format_inr($p['unit_price'],2)?></div>
    </div>
    <div class="catalog-card-footer">
      <a href="order_parts.php?catalog_id=<?=$p['catalog_id']?>" class="btn btn-blue btn-sm" style="flex:1;justify-content:center">🛒 Order</a>
      <?php if ($p['supplier_url']): ?>
      <a href="<?=h($p['supplier_url'])?>" target="_blank" class="btn btn-secondary btn-sm" title="Visit Supplier">↗</a>
      <?php endif; ?>
      <a href="?delete=<?=$p['catalog_id']?>" class="btn btn-danger btn-sm confirm-delete" title="Remove">✕</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>

</div>
<?php require_once 'includes/footer.php'; ?>
