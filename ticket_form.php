<?php
require 'config.php'; require_login();
$id = (int)($_GET['id'] ?? 0);
$ticket = ['student_name'=>'','student_email'=>'','category'=>'Academic','priority'=>'Medium','status'=>'Open','subject'=>'','description'=>''];
if ($id) {
  $stmt=$pdo->prepare("SELECT * FROM tickets WHERE id=?"); $stmt->execute([$id]); $ticket=$stmt->fetch();
  if(!$ticket) { flash('Request not found.','error'); redirect('index.php'); }
}
$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
  $ticket = [
    'student_name'=>trim($_POST['student_name']??''),
    'student_email'=>trim($_POST['student_email']??''),
    'category'=>$_POST['category']??'Academic',
    'priority'=>$_POST['priority']??'Medium',
    'status'=>$_POST['status']??'Open',
    'subject'=>trim($_POST['subject']??''),
    'description'=>trim($_POST['description']??'')
  ];
  if($ticket['student_name']==='') $errors[]='Student name is required.';
  if(!filter_var($ticket['student_email'], FILTER_VALIDATE_EMAIL)) $errors[]='A valid email is required.';
  if($ticket['subject']==='') $errors[]='Subject is required.';
  if($ticket['description']==='') $errors[]='Description is required.';
  if(!$errors){
    if($id){
      $stmt=$pdo->prepare("UPDATE tickets SET student_name=?,student_email=?,category=?,priority=?,status=?,subject=?,description=?,updated_at=CURRENT_TIMESTAMP WHERE id=?");
      $stmt->execute([$ticket['student_name'],$ticket['student_email'],$ticket['category'],$ticket['priority'],$ticket['status'],$ticket['subject'],$ticket['description'],$id]);
      flash('Request updated.');
    }else{
      $stmt=$pdo->prepare("INSERT INTO tickets(student_name,student_email,category,priority,status,subject,description) VALUES(?,?,?,?,?,?,?)");
      $stmt->execute([$ticket['student_name'],$ticket['student_email'],$ticket['category'],$ticket['priority'],$ticket['status'],$ticket['subject'],$ticket['description']]);
      flash('Request created.');
    }
    redirect('index.php');
  }
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=$id?'Edit':'New'?> Request</title><link rel="stylesheet" href="assets/style.css"></head>
<body><div class="container"><div class="nav"><div class="brand"><?=$id?'Edit':'New'?> Support Request</div><a class="btn secondary" href="index.php">Back</a></div>
<div class="card"><?php if($errors): ?><div class="flash error"><?=e(implode(' ', $errors))?></div><?php endif; ?>
<form method="post">
<div class="grid grid-2"><div><label>Student name</label><input name="student_name" value="<?=e($ticket['student_name'])?>" required></div>
<div><label>Student email</label><input type="email" name="student_email" value="<?=e($ticket['student_email'])?>" required></div></div>
<div class="grid grid-3">
<div><label>Category</label><select name="category"><?php foreach(['Academic','Technical','Billing','Registration','Other'] as $v): ?><option <?=$ticket['category']===$v?'selected':''?>><?=$v?></option><?php endforeach; ?></select></div>
<div><label>Priority</label><select name="priority"><?php foreach(['Low','Medium','High'] as $v): ?><option <?=$ticket['priority']===$v?'selected':''?>><?=$v?></option><?php endforeach; ?></select></div>
<div><label>Status</label><select name="status"><?php foreach(['Open','In Progress','Resolved'] as $v): ?><option <?=$ticket['status']===$v?'selected':''?>><?=$v?></option><?php endforeach; ?></select></div>
</div>
<label>Subject</label><input name="subject" value="<?=e($ticket['subject'])?>" required>
<label>Description</label><textarea name="description" required><?=e($ticket['description'])?></textarea>
<br><button type="submit"><?=$id?'Save Changes':'Create Request'?></button>
</form></div></div></body></html>
