<?php
require_once 'includes/db.php';
$db = getDB();

echo "Starting migrations...\n";

// Add username and password to employees
$q1 = "ALTER TABLE employees ADD (username VARCHAR2(50) UNIQUE, password_hash VARCHAR2(255))";
$stmt = oci_parse($db, $q1);
if (@oci_execute($stmt, OCI_DEFAULT)) {
    echo "Added username/password to employees.\n";
} else {
    $e = oci_error($stmt);
    echo "Error or already exists (employees): " . $e['message'] . "\n";
}

// Add default admin to first employee if exists (or set for all if username is null)
$defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
$q_update = "UPDATE employees SET username = 'admin', password_hash = :pass WHERE rownum = 1 AND username IS NULL";
$stmt2 = oci_parse($db, $q_update);
oci_bind_by_name($stmt2, ':pass', $defaultPass);
if (oci_execute($stmt2, OCI_DEFAULT)) {
    echo "Updated first employee as admin (if applicable).\n";
}
oci_commit($db);

// Remaining employees get arbitrary usernames to maintain uniqueness
$q_update_others = "UPDATE employees SET username = 'employee_' || employee_id, password_hash = :pass WHERE username IS NULL";
$stmt3 = oci_parse($db, $q_update_others);
oci_bind_by_name($stmt3, ':pass', $defaultPass);
if (oci_execute($stmt3, OCI_DEFAULT)) {
    echo "Updated remaining employees.\n";
}
oci_commit($db);

// Add image fields to service_records
$q2 = "ALTER TABLE service_records ADD (image_before VARCHAR2(255), image_after VARCHAR2(255))";
$stmt4 = oci_parse($db, $q2);
if (@oci_execute($stmt4, OCI_DEFAULT)) {
    echo "Added image_before/image_after to service_records.\n";
} else {
    $e = oci_error($stmt4);
    echo "Error or already exists (service_records): " . $e['message'] . "\n";
}

echo "Migrations complete.\n";
?>
