<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if ($username && $password) {
        $db = getDB();
        $user = oraQuery($db, "SELECT * FROM employees WHERE username = :usr", ['usr' => $username]);
        
        if (count($user) > 0 && password_verify($password, $user[0]['password_hash'])) {
            $_SESSION['user_id'] = $user[0]['employee_id'];
            $_SESSION['username'] = $user[0]['username'];
            $_SESSION['full_name'] = $user[0]['full_name'];
            $_SESSION['role'] = $user[0]['role'];
            header("Location: index.php");
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>VSMS — Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body { display: flex; justify-content: center; align-items: center; height: 100vh; background: var(--bg); margin: 0; }
    .login-card { background: var(--surface); padding: 40px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); width: 100%; max-width: 400px; border: 1px solid var(--border); }
    .login-card h2 { margin-top: 0; color: var(--accent); font-family: 'Rajdhani', sans-serif; text-align: center; }
    .login-card .form-group { margin-bottom: 20px; }
  </style>
</head>
<body>
<div class="login-card">
  <h2>VSMS LOGIN</h2>
  <?php if ($error): ?><div class="alert alert-error"><?= h($error) ?></div><?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Username</label>
      <input type="text" name="username" required autofocus>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" required>
    </div>
    <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 15px;">Sign In</button>
    <div style="text-align: center; font-size: 14px; margin-top: 10px; display: flex; justify-content: space-between;">
        <a href="register.php" style="color: var(--accent); text-decoration: none; font-weight: 500;">Create an account</a>
        <a href="forgot_password.php" style="color: var(--text); text-decoration: none; font-weight: 500;">Forgot Password?</a>
    </div>
  </form>
</div>
</body>
</html>
