<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB(); $msg = '';

$prefillCatalog  = isset($_GET['catalog_id']) ? (int)$_GET['catalog_id'] : 0;
$prefillService  = isset($_GET['sid'])         ? (int)$_GET['sid']        : 0;

// Place Order
if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='place_order') {
    $cid  = (int)$_POST['catalog_id'];
    $sid  = $_POST['service_id'] ? (int)$_POST['service_id'] : 'NULL';
    $qty  = (int)$_POST['quantity'];
    $by   = trim($_POST['ordered_by']);
    $exp  = $_POST['expected_date'];
    $note = trim($_POST['notes']);

    // Get price from catalog
    $cat = oraQuery($db,"SELECT unit_price FROM parts_catalog WHERE catalog_id=:id",['id'=>$cid]);
    if (empty($cat)) {
        $msg = '<div class="alert alert-error">Invalid catalog item.</div>';
    } else {
        $price = $cat[0]['unit_price'];
        $sid_val = $_POST['service_id'] ? (int)$_POST['service_id'] : null;
        $stmt = oci_parse($db,
            "INSERT INTO parts_orders (catalog_id,service_id,ordered_by,quantity,unit_price,expected_date,notes)
             VALUES (:cid,:sid,:ordby,:qty,:price,TO_DATE(:exp,'YYYY-MM-DD'),:note)");
        oci_bind_by_name($stmt,':cid',  $cid);
        oci_bind_by_name($stmt,':sid',  $sid_val);
        oci_bind_by_name($stmt,':ordby',   $by);
        oci_bind_by_name($stmt,':qty',  $qty);
        oci_bind_by_name($stmt,':price',$price);
        oci_bind_by_name($stmt,':exp',  $exp);
        oci_bind_by_name($stmt,':note', $note);
        $ok = oci_execute($stmt, OCI_DEFAULT);
        if ($ok) { oci_commit($db); $msg = '<div class="alert alert-success">✅ Order placed successfully!</div>'; }
        else     { $e = oci_error($stmt); $msg = '<div class="alert alert-error">Failed: '.h($e['message']).'</div>'; }
        oci_free_statement($stmt);
    }
}

// Data
$catalog  = oraQuery($db,"SELECT * FROM parts_catalog WHERE is_active=1 ORDER BY category,part_name");
$services = oraQuery($db,
    "SELECT sr.service_id,'#'||sr.service_id||' — '||v.number_plate||' ('||sr.service_type||')' AS label
     FROM service_records sr JOIN vehicles v ON sr.vehicle_id=v.vehicle_id
     WHERE sr.status IN ('Pending','In Progress') ORDER BY sr.service_date DESC");
$employees = oraQuery($db,"SELECT full_name FROM employees ORDER BY full_name");

// Pre-selected catalog item detail
$selectedPart = null;
if ($prefillCatalog) {
    $tmp = oraQuery($db,"SELECT * FROM parts_catalog WHERE catalog_id=:id",['id'=>$prefillCatalog]);
    $selectedPart = $tmp[0] ?? null;
}

// Category icons
$catIcons = ['Engine'=>'🔧','Brakes'=>'🛑','Electrical'=>'⚡','Filters'=>'🌬','Ignition'=>'🔥','Cooling'=>'❄','Wipers'=>'🌧','Clutch'=>'⚙'];
?>

<div class="page-header">
  <h1>ORDER PARTS</h1>
  <a href="parts_catalog.php" class="btn btn-secondary">← Back to Catalog</a>
</div>

<div class="page-content">
<?=$msg?>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">

  <!-- ORDER FORM -->
  <div class="form-card">
    <div class="section-title">Place Parts Order</div>
    <form method="POST"><input type="hidden" name="action" value="place_order">
      <div class="form-grid">

        <!-- Part selector -->
        <div class="form-group full">
          <label>Select Part from Catalog *</label>
          <select name="catalog_id" id="part_sel" required onchange="updatePartInfo()">
            <option value="">— Choose a Part —</option>
            <?php
            $lastCat = '';
            foreach ($catalog as $p):
              if ($p['category'] !== $lastCat):
                if ($lastCat !== '') echo '</optgroup>';
                echo '<optgroup label="'.h($p['category']).' '.($catIcons[$p['category']]??'🔩').'">';
                $lastCat = $p['category'];
              endif;
            ?>
            <option value="<?=$p['catalog_id']?>"
              data-price="<?=$p['unit_price']?>"
              data-supplier="<?=h($p['supplier_name'])?>"
              data-pnum="<?=h($p['part_number']??'')?>"
              data-make="<?=h($p['compatible_make']??'')?>"
              data-url="<?=h($p['supplier_url']??'')?>"
              <?=$prefillCatalog==$p['catalog_id']?'selected':''?>>
              <?=h($p['part_name'])?> — ₹<?=format_inr($p['unit_price'],2)?>
              <?=$p['compatible_make']?' ['.$p['compatible_make'].']':''?>
            </option>
            <?php endforeach; if ($lastCat) echo '</optgroup>'; ?>
          </select>
        </div>

        <!-- Part info preview box -->
        <div class="form-group full" id="part_preview" style="display:none">
          <div style="background:var(--surface2);border:1px solid var(--border);border-radius:3px;padding:14px;display:flex;gap:16px;align-items:center">
            <div style="font-size:36px" id="prev_icon">🔧</div>
            <div style="flex:1">
              <div id="prev_supplier" style="font-size:11px;color:var(--blue);margin-bottom:4px"></div>
              <div id="prev_pnum"     style="font-family:monospace;font-size:11px;color:var(--muted)"></div>
              <div id="prev_make"     style="font-size:12px;color:var(--muted)"></div>
            </div>
            <div>
              <div id="prev_price" style="font-family:var(--fh);font-size:26px;font-weight:700;color:var(--accent)"></div>
              <div id="prev_total" style="font-size:12px;color:var(--muted);text-align:right"></div>
            </div>
          </div>
          <div id="prev_url" style="margin-top:6px;font-size:12px"></div>
        </div>

        <!-- Quantity -->
        <div class="form-group">
          <label>Quantity *</label>
          <input type="number" name="quantity" id="qty_inp" value="1" min="1" required oninput="updateTotal()">
        </div>

        <!-- Linked service -->
        <div class="form-group">
          <label>Link to Service Job (optional)</label>
          <select name="service_id">
            <option value="">— None (stock replenishment) —</option>
            <?php foreach ($services as $s): ?>
            <option value="<?=$s['service_id']?>" <?=$prefillService==$s['service_id']?'selected':''?>><?=h($s['label'])?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Ordered by -->
        <div class="form-group">
          <label>Ordered By *</label>
          <input name="ordered_by" list="emplist" required>
          <datalist id="emplist">
            <?php foreach ($employees as $e): ?><option><?=h($e['full_name'])?></option><?php endforeach; ?>
          </datalist>
        </div>

        <!-- Expected date -->
        <div class="form-group">
          <label>Expected Delivery Date</label>
          <input type="date" name="expected_date" value="<?=date('Y-m-d', strtotime('+7 days'))?>">
        </div>

        <!-- Notes -->
        <div class="form-group full">
          <label>Notes</label>
          <textarea name="notes" placeholder="e.g. For Swift brake job, urgent delivery"></textarea>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-blue" style="font-size:15px;padding:10px 24px">🛒 Place Order</button>
        <a href="parts_catalog.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <!-- SIDEBAR: Selected part info + recent orders -->
  <div>
    <?php if ($selectedPart): ?>
    <div class="catalog-card" style="margin-bottom:16px">
      <div class="catalog-card-img" style="height:100px">
        <span style="font-size:48px"><?=$catIcons[$selectedPart['category']]??'🔩'?></span>
      </div>
      <div class="catalog-card-body">
        <div class="catalog-card-name"><?=h($selectedPart['part_name'])?></div>
        <?php if ($selectedPart['part_number']): ?>
        <div class="catalog-card-part">OEM: <?=h($selectedPart['part_number'])?></div>
        <?php endif; ?>
        <div><span class="supplier-tag"><?=h($selectedPart['supplier_name'])?></span></div>
        <?php if ($selectedPart['compatible_model']): ?>
        <div class="catalog-card-meta">Fits: <?=h($selectedPart['compatible_model'])?></div>
        <?php endif; ?>
        <div class="catalog-card-price">₹<?=format_inr($selectedPart['unit_price'],2)?></div>
      </div>
      <?php if ($selectedPart['supplier_url']): ?>
      <div class="catalog-card-footer">
        <a href="<?=h($selectedPart['supplier_url'])?>" target="_blank" class="btn btn-secondary btn-sm" style="flex:1;justify-content:center">
          ↗ View on Supplier Website
        </a>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="section-title">Recent Orders</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Part</th><th>Qty</th><th>Status</th></tr></thead>
        <tbody>
          <?php
          $recent = oraQuery($db,"SELECT * FROM (SELECT part_name,quantity,order_status FROM vw_parts_orders ORDER BY order_date DESC) WHERE ROWNUM <= 6");
          foreach ($recent as $o):
            $sc = strtolower($o['order_status']);
          ?>
          <tr>
            <td style="font-size:12px"><?=h($o['part_name'])?></td>
            <td><?=$o['quantity']?></td>
            <td><span class="badge badge-<?=$sc?>" style="font-size:10px"><?=h($o['order_status'])?></span></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</div>

<script>
const catIcons = {Engine:'🔧',Brakes:'🛑',Electrical:'⚡',Filters:'🌬',Ignition:'🔥',Cooling:'❄',Wipers:'🌧',Clutch:'⚙'};

function updatePartInfo() {
  const sel = document.getElementById('part_sel');
  const opt = sel.options[sel.selectedIndex];
  const prev = document.getElementById('part_preview');
  if (!opt.value) { prev.style.display='none'; return; }
  prev.style.display='block';
  document.getElementById('prev_supplier').textContent = opt.dataset.supplier;
  document.getElementById('prev_pnum').textContent     = opt.dataset.pnum ? 'Part #: '+opt.dataset.pnum : '';
  document.getElementById('prev_make').textContent     = opt.dataset.make ? 'For: '+opt.dataset.make    : '';
  document.getElementById('prev_price').textContent    = '₹'+parseFloat(opt.dataset.price).toLocaleString('en-IN');
  const pu = document.getElementById('prev_url');
  pu.innerHTML = opt.dataset.url ? `<a href="${opt.dataset.url}" target="_blank" style="color:var(--blue)">↗ View on supplier website</a>` : '';
  updateTotal();
}

function updateTotal() {
  const sel = document.getElementById('part_sel');
  const opt = sel.options[sel.selectedIndex];
  const qty = parseInt(document.getElementById('qty_inp').value)||1;
  if (!opt.value) return;
  const total = (parseFloat(opt.dataset.price)*qty).toFixed(2);
  document.getElementById('prev_total').textContent = 'Total: ₹'+parseFloat(total).toLocaleString('en-IN');
}

// Auto-trigger on page load if prefill
window.addEventListener('DOMContentLoaded', updatePartInfo);
</script>

<?php require_once 'includes/footer.php'; ?>
