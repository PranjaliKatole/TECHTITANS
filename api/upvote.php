<?php
session_start();
require 'db.php';
require 'helpers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'error'=>'POST only'], 405);
$id = (int)($_POST['id'] ?? 0);
if ($id < 1) respond(['success'=>false,'error'=>'Invalid ID'], 400);
if (!empty($_SESSION['up'][$id])) respond(['success'=>false,'error'=>'You already upvoted this issue'], 409);
try {
    $st = $pdo->prepare("SELECT priority_score FROM issues WHERE id=? AND status!='Resolved'");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) respond(['success'=>false,'error'=>'Issue not found or already resolved'], 404);
    $score = min(100, $row['priority_score'] + 2);
    $pdo->prepare("UPDATE issues SET upvotes=upvotes+1, priority_score=?, severity=? WHERE id=?")
        ->execute([$score, severityFromScore($score), $id]);
    $_SESSION['up'][$id] = true;
    $n = $pdo->prepare("SELECT upvotes FROM issues WHERE id=?");
    $n->execute([$id]);
    respond(['success'=>true,'upvotes'=>(int)$n->fetchColumn()]);
} catch (Exception $e) {
    respond(['success'=>false,'error'=>'Upvote failed'], 500);
}