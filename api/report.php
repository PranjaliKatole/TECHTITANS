<?php
require 'db.php';
require 'helpers.php';
require 'rules.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'error'=>'POST only'], 405);

$desc  = trim($_POST['description'] ?? '');
$lat   = $_POST['latitude']  ?? '';
$lng   = $_POST['longitude'] ?? '';
$addr  = trim($_POST['address'] ?? '');
$uid   = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;
$name  = mb_substr(trim($_POST['reporter_name'] ?? ''), 0, 100) ?: null;
$phone = preg_replace('/\D/', '', $_POST['reporter_phone'] ?? '');

if (strlen($desc) < 5) respond(['success'=>false,'error'=>'Please describe the issue (min 5 characters)'], 400);
if ($phone !== '' && strlen($phone) !== 10) respond(['success'=>false,'error'=>'Phone must be 10 digits'], 400);
if (!is_numeric($lat) || !is_numeric($lng) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180)
    respond(['success'=>false,'error'=>'Valid location is required'], 400);
$lat = (float)$lat; $lng = (float)$lng;
$phone = $phone ?: null;

$allowed = array_keys(ISSUE_TYPES);
$autoCat = empty($_POST['category']) || !in_array($_POST['category'], $allowed);
$category = $autoCat ? categorize($desc) : $_POST['category'];
$type = validType($category, trim($_POST['issue_type'] ?? ''));
$w = typeWeight($category, $type);

try {
    $img = saveImage($_FILES['image'] ?? null);

    $sql = "SELECT id, report_count, description, status, created_at, issue_type,
            (6371000 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS(latitude)) *
            COS(RADIANS(longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(latitude))))) AS distance_m
            FROM issues
            WHERE category = ? AND status != 'Resolved'
            HAVING distance_m < 50
            ORDER BY distance_m LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$lat, $lng, $lat, $category]);
    $dup = $st->fetch();

    $pdo->beginTransaction();

    if ($dup) {
        $issueId = (int)$dup['id'];
        $count = $dup['report_count'] + 1;
        $days = (int)((time() - strtotime($dup['created_at'])) / 86400);
        $w = max($w, typeWeight($category, $dup['issue_type']));
        $score = min(100, calcScore($category, $dup['description'].' '.$desc, $count, $days) + $w);
        $sev = severityFromScore($score);
        $due = dueFor($sev, strtotime($dup['created_at']));

        $pdo->prepare("UPDATE issues SET report_count=?, priority_score=?, severity=?,
                       issue_type=COALESCE(issue_type, ?), due_at=LEAST(COALESCE(due_at, ?), ?) WHERE id=?")
            ->execute([$count, $score, $sev, $type, $due, $due, $issueId]);
        $pdo->prepare("INSERT INTO status_log (issue_id, old_status, new_status, remark, changed_by) VALUES (?,?,?,?,?)")
            ->execute([$issueId, $dup['status'], $dup['status'], "Duplicate report merged (total reports: $count)", 'System']);
        $merged = true;
        $dept = deptFor($category);
    } else {
        $score = min(100, calcScore($category, $desc, 1, 0) + $w);
        $sev = severityFromScore($score);
        $due = dueFor($sev);
        $dept = deptFor($category);
        $title = ($type ?: $category) . ': ' . mb_substr($desc, 0, 60);

        $pdo->prepare("INSERT INTO issues (title, description, category, issue_type, latitude, longitude, address, image_path, severity, priority_score, department, due_at)
                       VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([$title, $desc, $category, $type, $lat, $lng, $addr, $img, $sev, $score, $dept, $due]);
        $issueId = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO status_log (issue_id, old_status, new_status, remark, changed_by) VALUES (?,?,?,?,?)")
            ->execute([$issueId, null, 'Reported', "Issue reported by citizen. Routed to $dept (due " . date('d M H:i', strtotime($due)) . ")", 'System']);
        $merged = false;
    }

    $pdo->prepare("INSERT INTO reports (issue_id, user_id, description, image_path, reporter_name, reporter_phone, issue_type) VALUES (?,?,?,?,?,?,?)")
        ->execute([$issueId, $uid, $desc, $img, $name, $phone, $type]);
    $pdo->commit();

    respond([
        'success'=>true, 'merged'=>$merged, 'issue_id'=>$issueId, 'category'=>$category,
        'issue_type'=>$type, 'auto_categorised'=>$autoCat, 'severity'=>$sev, 'priority_score'=>$score,
        'department'=>$dept, 'due_at'=>$due,
        'message'=>$merged ? "Merged with existing issue #$issueId" : "New issue #$issueId created"
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(['success'=>false,'error'=>$e->getMessage()], 500);
}