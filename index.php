<?php
require 'config.php'; require_login();
$status = trim($_GET['status'] ?? '');
$q = trim($_GET['q'] ?? '');
$sql = "SELECT * FROM tickets WHERE 1=1";
$params = [];
if ($status !== '') { $sql .= " AND status=?"; $params[]=$status; }
if ($q !== '') { $sql .= " AND (student_name LIKE ? OR subject LIKE ? OR student_email LIKE ?)"; $like="%$q%"; array_push($params,$like,$like,$like); }
$sql .= " ORDER BY id DESC";
$stmt=$pdo->prepare($sql); $stmt->execute($params); $tickets=$stmt->fetchAll();
$metrics = [
  'Total'=>(int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn(),
  'Open'=>(int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status='Open'")->fetchColumn(),
  'Resolved'=>(int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE status='Resolved'")->fetchColumn()
];
$f=flash();
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>CampusCare</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="container">
<div class="nav"><div><div class="brand">CampusCare</div><div class="muted">Department support dashboard</div></div><div class="actions"><a class="btn" href="ticket_form.php">+ New Request</a><a class="btn secondary" href="logout.php">Logout</a></div></div>
<?php if($f): ?><div class="flash <?=$f[1]==='error'?'error':''?>"><?=e($f[0])?></div><?php endif; ?>
<div class="grid grid-3">
<?php foreach($metrics as $k=>$v): ?><div class="card"><div class="muted"><?=e($k)?></div><div class="metric"><?=$v?></div></div><?php endforeach; ?>
</div>
<div class="card">
<form method="get" class="grid grid-2">
<div><label>Search</label><input name="q" value="<?=e($q)?>" placeholder="Student, email, or subject"></div>
<div><label>Status</label><select name="status"><option value="">All statuses</option><?php foreach(['Open','In Progress','Resolved'] as $s): ?><option <?=$status===$s?'selected':''?>><?=e($s)?></option><?php endforeach; ?></select></div>
<div class="actions"><button>Apply filters</button><a class="btn secondary" href="index.php">Reset</a></div>
</form>
</div>
<div class="card"><h2>Support Requests</h2>
<table><thead><tr><th>ID</th><th>Student</th><th>Subject</th><th>Category</th><th>Priority</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php if(!$tickets): ?><tr><td colspan="7" class="muted">No requests yet.</td></tr><?php endif; ?>
<?php foreach($tickets as $t): ?><tr>
<td>#<?=$t['id']?></td><td><?=e($t['student_name'])?><br><span class="muted"><?=e($t['student_email'])?></span></td>
<td><?=e($t['subject'])?></td><td><?=e($t['category'])?></td><td><?=e($t['priority'])?></td><td><span class="badge"><?=e($t['status'])?></span></td>
<td class="actions"><a class="btn secondary" href="ticket_form.php?id=<?=$t['id']?>">Edit</a><a class="btn danger" href="delete_ticket.php?id=<?=$t['id']?>" onclick="return confirm('Delete this request?')">Delete</a></td>
</tr><?php endforeach; ?></tbody></table></div>
</div></body></html>
