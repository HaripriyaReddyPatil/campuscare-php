<?php
require 'config.php';
if (!empty($_SESSION['user'])) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();
  if ($user && password_verify($password, $user['password_hash'])) {
    $_SESSION['user'] = ['id'=>$user['id'],'email'=>$user['email'],'role'=>$user['role']];
    redirect('index.php');
  }
  $error = 'Invalid email or password.';
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CampusCare Login</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="container login"><div class="card"><div class="brand">CampusCare</div><p class="muted">Student support request management portal</p>
<?php if($error): ?><div class="flash error"><?=e($error)?></div><?php endif; ?>
<form method="post">
<label>Email</label><input type="email" name="email" value="admin@campuscare.local" required>
<label>Password</label><input type="password" name="password" value="Admin123!" required>
<br><br><button type="submit">Sign in</button>
</form><p class="muted">Demo: admin@campuscare.local / Admin123!</p></div></div></body></html>
