<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit;
    }
}

function hasRole($allowedRoles = []) {
    if (!isset($_SESSION['role'])) return false;
    if (empty($allowedRoles)) return true; // Empty array means allowed for all
    return in_array($_SESSION['role'], $allowedRoles);
}
?>
