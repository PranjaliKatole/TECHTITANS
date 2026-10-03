<?php
require 'db.php';
$id = (int)($_GET['id'] ?? 0);
if ($id < 1) respond(['success'=>false,'error'=>'Invalid ID'], 400);
$st = $pdo->prepare("SELECT * FROM issues WHERE id=?");
$st->execute([$id]);
$issue = $st->fetch();
if (!$issue) respond(['success'=>false,'error'=>'Issue not found'], 404);
$tl = $pdo->prepare("SELECT old_status,new_status,remark,changed_by,changed_at FROM status_log WHERE issue_id=? ORDER BY id");
$tl->execute([$id]);
respond(['success'=>true,'issue'=>$issue,'timeline'=>$tl->fetchAll()]);