<?php
require 'config.php'; require_login();
$id=(int)($_GET['id']??0);
if($id){$stmt=$pdo->prepare("DELETE FROM tickets WHERE id=?");$stmt->execute([$id]);flash('Request deleted.');}
redirect('index.php');
