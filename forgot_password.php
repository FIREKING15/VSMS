<?php
require_once 'includes/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    
    if ($username && $phone && $new_password) {
        $db = getDB();
        
        if (!preg_match('/^\d{10}$/', $phone)) {
            $error = 'Phone number must be exactly 10 digits.';
        } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{4,}$/', $new_password)) {
            $error = 'Password must be 4+ chars, with upper, lower, number, and special character.';
        } else {
            // Verify identity using username and phone number
            $user = oraQuery($db, "SELECT employee_id FROM employees WHERE username = :usr AND phone = :phn", [
            'usr' => $username,
            'phn' => $phone
        ]);
        
        if (count($user) > 0) {
            // Identity verified, update password
            $passHash = password_hash($new_password, PASSWORD_DEFAULT);
            $userId = $user[0]['employee_id'];
            
            $ok = oraExec($db, "UPDATE employees SET password_hash = :pwd WHERE employee_id = :id", [
                'pwd' => $passHash,
                'id' => $userId
            ]);
            
            if ($ok) {
                $success = 'Password successfully reset! You can now log in with your new password.';
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
        } else {
            $error = 'Verification failed. The username and phone number combination does not exist.';
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
  <title>VSMS — Forgot Password</title>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { display: flex; justify-content: center; align-items: center; height: 100vh; background: var(--bg); margin: 0; padding: 20px; }
    .login-card { background: var(--surface); padding: 40px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 100%; max-width: 450px; border: 1px solid var(--border); }
    .login-card h2 { margin-top: 0; color: var(--accent); font-family: 'Rajdhani', sans-serif; text-align: center; margin-bottom: 5px; }
    .login-card p.subtitle { color: var(--muted); text-align: center; margin-bottom: 25px; font-size: 14px; }
    .login-card .form-group { margin-bottom: 20px; }
  </style>
</head>
<body>
<div class="login-card">
  <h2>RESET PASSWORD</h2>
  <p class="subtitle">Please enter your username and registered phone number to verify your identity.</p>
  
  <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?= h($success) ?></div>
    <div style="text-align: center; margin-top: 20px;">
        <a href="login.php" class="btn btn-primary" style="text-decoration: none;">Go to Login</a>
    </div>
  <?php else: ?>
  
  <form method="POST">
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" required autofocus>
    </div>
    
    <div class="form-group">
      <label>Registered Phone No.</label>
      <input type="text" name="phone" placeholder="e.g. 9876543210" pattern="\d{10}" title="Must be exactly 10 digits" minlength="10" maxlength="10" required>
    </div>

    <div class="form-group" style="margin-top: 30px; border-top: 1px solid var(--border); padding-top: 20px;">
      <label>New Password</label>
      <input type="password" name="new_password" pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{4,}$" title="Password must be 4+ characters, with upper, lower, number, and special character." required>
    </div>
    
    <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 15px; margin-top: 10px;">Reset Password</button>
    <div style="text-align: center; font-size: 14px; margin-top: 10px;">
        <a href="login.php" style="color: var(--accent); text-decoration: none; font-weight: 500;">&larr; Back to Login</a>
    </div>
  </form>
  
  <?php endif; ?>
</div>
</body>
</html>
