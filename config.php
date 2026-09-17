<?php
declare(strict_types=1);
session_start();

$dbFile = __DIR__ . '/data/campuscare.sqlite';
$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$pdo->exec("CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'admin'
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS tickets (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  student_name TEXT NOT NULL,
  student_email TEXT NOT NULL,
  category TEXT NOT NULL,
  priority TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'Open',
  subject TEXT NOT NULL,
  description TEXT NOT NULL,
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
)");

$count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($count === 0) {
  $stmt = $pdo->prepare("INSERT INTO users(email,password_hash,role) VALUES(?,?,?)");
  $stmt->execute(['admin@campuscare.local', password_hash('Admin123!', PASSWORD_DEFAULT), 'admin']);
}

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function redirect(string $path): never { header("Location: $path"); exit; }
function require_login(): void {
  if (empty($_SESSION['user'])) redirect('login.php');
}
function flash(?string $message = null, string $type='ok'): ?array {
  if ($message !== null) { $_SESSION['flash'] = [$message,$type]; return null; }
  if (!empty($_SESSION['flash'])) { $f=$_SESSION['flash']; unset($_SESSION['flash']); return $f; }
  return null;
}
?>
