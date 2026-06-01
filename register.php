<?php
require_once 'includes/db.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';
$roles = ['Mechanic','Electrician','Painter','Supervisor','Receptionist'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'Receptionist';
    
    if ($username && $password && $full_name) {
        $db = getDB();
        
        if (!preg_match('/^\d{10}$/', $phone)) {
            $error = 'Phone number must be exactly 10 digits.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{4,}$/', $password)) {
            $error = 'Password must be 4+ chars, with upper, lower, number, and special character.';
        } else {
            // Check if username already exists
        $existing = oraQuery($db, "SELECT employee_id FROM employees WHERE username = :usr", ['usr' => $username]);
        
        if (count($existing) > 0) {
            $error = 'Username is already taken.';
        } else {
            $passHash = password_hash($password, PASSWORD_DEFAULT);
            $salary = 0; // default for new registrations until admin sets it
            
            $ok = oraExec($db,
                "INSERT INTO employees (username, password_hash, full_name, role, phone, salary, hired_on) 
                 VALUES (:usr, :pwd, :name, :role, :phone, :sal, SYSDATE)",
                [
                    'usr' => $username, 
                    'pwd' => $passHash, 
                    'name' => $full_name,
                    'role' => $role,
                    'phone' => $phone,
                    'sal' => $salary
                ]
            );
            
            if ($ok) {
                $success = 'Account created successfully! You can now log in.';
            } else {
                $error = 'Failed to create account. Please try again.';
            }
        }
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>VSMS — Create Account</title>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { display: flex; justify-content: center; align-items: center; min-height: 100vh; background: var(--bg); margin: 0; padding: 20px; }
    .login-card { background: var(--surface); padding: 40px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 100%; max-width: 450px; border: 1px solid var(--border); }
    .login-card h2 { margin-top: 0; color: var(--accent); font-family: 'Rajdhani', sans-serif; text-align: center; }
    .login-card .form-group { margin-bottom: 20px; }
  </style>
</head>
<body>
<div class="login-card">
  <h2>CREATE ACCOUNT</h2>
  
  <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= h($success) ?></div>
    <div style="text-align: center; margin-top: 20px;">
        <a href="login.php" class="btn btn-primary" style="text-decoration: none;">Go to Login</a>
    </div>
  <?php else: ?>
  
  <form method="POST">
    <div class="form-group">
      <label>Full Name *</label>
      <input type="text" name="full_name" required autofocus>
    </div>
    
    <div class="form-group">
      <label>Username *</label>
      <input type="text" name="username" required>
    </div>
    
    <div class="form-group">
      <label>Phone Number *</label>
      <input type="text" name="phone" placeholder="e.g. 9876543210" pattern="\d{10}" title="Must be exactly 10 digits" minlength="10" maxlength="10" required>
    </div>
    
    <div class="form-group">
      <label>Role</label>
      <select name="role" required>
        <?php foreach($roles as $r): ?>
            <option value="<?=h($r)?>"><?=h($r)?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Password *</label>
      <input type="password" name="password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{4,}$" title="Password must be 4+ characters, with upper, lower, number, and special character." required>
    </div>
    
    <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 15px; margin-top: 10px;">Sign Up</button>
    <div style="text-align: center; font-size: 14px; margin-top: 10px;">
        <span style="color: var(--text);">Already have an account? </span>
        <a href="login.php" style="color: var(--accent); text-decoration: none; font-weight: 500;">Login here</a>
    </div>
  </form>
  
  <?php endif; ?>
</div>
</body>
</html>
