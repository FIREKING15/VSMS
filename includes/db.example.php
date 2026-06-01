<?php
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            $val = trim($val, '"\'');
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

define('ORA_USER', getenv('DB_USER') ?: 'YOUR_DATABASE_USERNAME');
define('ORA_PASS', getenv('DB_PASS') ?: 'YOUR_DATABASE_PASSWORD');
define('ORA_DSN',  getenv('DB_DSN') ?: 'localhost/XE');

class OracleDB {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $this->conn = oci_connect(ORA_USER, ORA_PASS, ORA_DSN, 'AL32UTF8');
        if (!$this->conn) {
            $e = oci_error();
            die('<div style="color:red;padding:20px;font-family:monospace">
                  <b>Oracle Connection Failed:</b><br>' . htmlspecialchars($e['message']) .
                '</div>');
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function query($sql, $binds = []) {
        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) {
            $e = oci_error($this->conn);
            die('Parse error: ' . $e['message']);
        }
        foreach ($binds as $k => $v) {
            oci_bind_by_name($stmt, $k, $binds[$k]);
        }
        oci_execute($stmt, OCI_DEFAULT);
        $rows = [];
        while ($row = oci_fetch_assoc($stmt)) {
            foreach ($row as $k => $v) {
                if (is_object($v) && get_class($v) === 'OCILob') {
                    $row[$k] = $v->load();
                    $v->free();
                }
            }
            $rows[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);
        return $rows;
    }

    public function execute($sql, $binds = []) {
        $stmt = oci_parse($this->conn, $sql);
        if (!$stmt) {
            return false;
        }
        $refs = [];
        foreach ($binds as $k => $v) {
            $refs[$k] = $v;
            oci_bind_by_name($stmt, ':' . $k, $refs[$k]);
        }
        $ok = oci_execute($stmt, OCI_DEFAULT);
        if ($ok) {
            oci_commit($this->conn);
        }
        oci_free_statement($stmt);
        return $ok;
    }
}

function getDB() {
    return OracleDB::getInstance()->getConnection();
}

function oraQuery($conn, $sql, $binds = []) {
    return OracleDB::getInstance()->query($sql, $binds);
}

function oraExec($conn, $sql, $binds = []) {
    return OracleDB::getInstance()->execute($sql, $binds);
}

function h($v) { 
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); 
}

function format_inr($amount, $decimals = 2) {
    if (!is_numeric($amount)) {
        return '0.00';
    }
    $amount = round($amount, $decimals);
    $amount_parts = explode('.', (string)$amount);
    $whole = $amount_parts[0];
    
    $is_negative = false;
    if (substr($whole, 0, 1) === '-') {
        $is_negative = true;
        $whole = substr($whole, 1);
    }

    if (strlen($whole) > 3) {
        $last_three = substr($whole, -3);
        $rest = substr($whole, 0, -3);
        $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
        $whole = $rest . ',' . $last_three;
    }
    
    $res = $is_negative ? '-' . $whole : $whole;
    
    if ($decimals > 0) {
        $fraction = isset($amount_parts[1]) ? str_pad($amount_parts[1], $decimals, '0', STR_PAD_RIGHT) : str_repeat('0', $decimals);
        $fraction = substr($fraction, 0, $decimals);
        $res .= '.' . $fraction;
    }
    return $res;
}
