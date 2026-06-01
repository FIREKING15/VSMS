<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
$db = getDB(); $msg = ''; $prefill = isset($_GET['sid'])?(int)$_GET['sid']:0;

if ($_SERVER['REQUEST_METHOD']==='POST' && $_POST['action']==='add_bill') {
    $ok = oraExec($db,
        "MERGE INTO bills b USING DUAL ON (b.service_id=:sid)
         WHEN MATCHED THEN UPDATE SET labour_total=:l,parts_total=:p,tax_percent=:t,payment_mode=:m,payment_status=:ps
         WHEN NOT MATCHED THEN INSERT (service_id,issue_date,labour_total,parts_total,tax_percent,payment_mode,payment_status)
         VALUES (:sid,TO_DATE(:dt,'YYYY-MM-DD'),:l,:p,:t,:m,:ps)",
        ['sid'=>(int)$_POST['service_id'],'dt'=>$_POST['issue_date'],
         'l'=>(float)$_POST['labour_total'],'p'=>(float)$_POST['parts_total'],
         't'=>(float)$_POST['tax_percent'],'m'=>$_POST['payment_mode'],'ps'=>$_POST['payment_status']]
    );
    $msg = $ok ? '<div class="alert alert-success">Bill saved.</div>'
               : '<div class="alert alert-error">Save failed.</div>';
}
if (isset($_GET['delete'])) {
    oraExec($db,"DELETE FROM bills WHERE bill_id=:id",['id'=>(int)$_GET['delete']]);
    $msg = '<div class="alert alert-success">Deleted.</div>';
}

$unbilled = oraQuery($db,
    "SELECT sr.service_id,'#'||sr.service_id||' — '||v.number_plate||' ('||sr.service_type||')' AS label, sr.labour_charge
     FROM service_records sr
     LEFT JOIN bills b ON sr.service_id=b.service_id
     JOIN vehicles v ON sr.vehicle_id=v.vehicle_id
     WHERE b.bill_id IS NULL ORDER BY sr.service_date DESC");

$bills = oraQuery($db,"SELECT * FROM vw_billing_summary ORDER BY service_date DESC");
?>
<div class="page-header"><h1>BILLING</h1>
  <button class="btn btn-primary" onclick="document.getElementById('af').classList.toggle('hidden')">+ Create Bill</button>
</div>
<div class="page-content">
<?=$msg?>
<div id="af" class="form-card hidden" style="margin-bottom:24px">
  <div class="section-title">Create / Update Bill</div>
  <form method="POST"><input type="hidden" name="action" value="add_bill">
    <div class="form-grid">
      <div class="form-group full"><label>Service Record *</label>
        <select name="service_id" id="svc_sel" required>
          <option value="">— Select Unbilled Service —</option>
          <?php foreach ($unbilled as $u): ?>
          <option value="<?=$u['service_id']?>" data-labour="<?=$u['labour_charge']?>" <?=$prefill==$u['service_id']?'selected':''?>><?=h($u['label'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group"><label>Issue Date *</label><input type="date" name="issue_date" value="<?=date('Y-m-d')?>" required></div>
      <div class="form-group"><label>Labour Total (₹)</label><input type="number" id="lt" name="labour_total" step="0.01" value="0" oninput="calc()"></div>
      <div class="form-group"><label>Parts Total (₹)</label><input type="number" id="pt" name="parts_total" step="0.01" value="0" oninput="calc()"></div>
      <div class="form-group"><label>Tax %</label><input type="number" id="tx" name="tax_percent" step="0.01" value="18" oninput="calc()"></div>
      <div class="form-group"><label>Grand Total (preview)</label><input id="gp" readonly style="background:var(--bg);color:var(--accent);font-weight:bold"></div>
      <div class="form-group"><label>Payment Mode</label>
        <select name="payment_mode"><option>Cash</option><option>Card</option><option>UPI</option><option>Bank Transfer</option></select>
      </div>
      <div class="form-group"><label>Payment Status</label>
        <select name="payment_status"><option>Unpaid</option><option>Paid</option><option>Partial</option></select>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save Bill</button>
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('af').classList.add('hidden')">Cancel</button>
    </div>
  </form>
</div>
<div class="table-wrap"><table>
  <thead><tr><th>Bill#</th><th>Customer</th><th>Plate</th><th>Service</th><th>Date</th><th>Labour</th><th>Parts</th><th>Tax</th><th>Grand Total</th><th>Mode</th><th>Status</th><th>Actions</th></tr></thead>
  <tbody>
    <?php foreach ($bills as $r): ?>
    <tr>
      <td class="text-muted"><?=h($r['bill_id'])?></td>
      <td><?=h($r['customer_name'])?></td>
      <td><code><?=h($r['number_plate'])?></code></td>
      <td><?=h($r['service_type'])?></td>
      <td><?=h($r['service_date'])?></td>
      <td>₹<?=format_inr($r['labour_total'],2)?></td>
      <td>₹<?=format_inr($r['parts_total'],2)?></td>
      <td><?=h($r['tax_percent'])?>%</td>
      <td class="text-accent"><strong>₹<?=format_inr($r['grand_total'],2)?></strong></td>
      <td><?=h($r['payment_mode'])?></td>
      <td><span class="badge badge-<?=strtolower($r['payment_status'])?>"><?=h($r['payment_status'])?></span></td>
      <td class="actions" style="white-space:nowrap;">
        <a href="invoice.php?id=<?=$r['bill_id']?>" target="_blank" class="btn btn-sm btn-primary">Invoice</a>
        <a href="?delete=<?=$r['bill_id']?>" class="btn btn-sm btn-danger confirm-delete">Del</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table></div>
</div>
<script>
function calc(){
  const l=parseFloat(document.getElementById('lt').value)||0,
        p=parseFloat(document.getElementById('pt').value)||0,
        t=parseFloat(document.getElementById('tx').value)||0;
  document.getElementById('gp').value='₹'+((l+p)*(1+t/100)).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}
document.getElementById('svc_sel')?.addEventListener('change',function(){
  const opt=this.options[this.selectedIndex];
  document.getElementById('lt').value=parseFloat(opt.dataset.labour||0).toFixed(2);
  calc();
});
calc();
</script>
<?php require_once 'includes/footer.php'; ?>
