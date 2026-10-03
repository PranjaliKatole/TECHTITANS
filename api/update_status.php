<?php
session_start();
require 'db.php';
require 'helpers.php';
if (empty($_SESSION['uid'])) respond(['success'=>false,'error'=>'Login required'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'error'=>'POST only'], 405);

$id = (int)($_POST['id'] ?? 0);
$new = $_POST['status'] ?? '';
$remark = trim($_POST['remark'] ?? '');
$assign = trim($_POST['assigned_to'] ?? '');
if (!in_array($new, ['Reported','Verified','Assigned','In Progress','Resolved']))
    respond(['success'=>false,'error'=>'Invalid status'], 400);

try {
    $st = $pdo->prepare("SELECT status FROM issues WHERE id=?");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) respond(['success'=>false,'error'=>'Issue not found'], 404);

    $after = null;
    if ($new === 'Resolved') $after = saveImage($_FILES['after_image'] ?? null);

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE issues SET status=?, assigned_to=COALESCE(NULLIF(?,''),assigned_to),
                   after_image_path=COALESCE(?,after_image_path),
                   resolved_at=IF(?='Resolved', NOW(), NULL) WHERE id=?")
        ->execute([$new, $assign, $after, $new, $id]);
    $pdo->prepare("INSERT INTO status_log (issue_id,old_status,new_status,remark,changed_by) VALUES (?,?,?,?,?)")
        ->execute([$id, $row['status'], $new, $remark ?: "Status changed to $new", $_SESSION['name']]);
    $pdo->commit();
    respond(['success'=>true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(['success'=>false,'error'=>$e->getMessage()], 500);
}