<?php
require 'db.php';
require 'helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success'=>false,'error'=>'POST only'], 405);

$desc = trim($_POST['description'] ?? '');
$lat  = $_POST['latitude']  ?? '';
$lng  = $_POST['longitude'] ?? '';
$addr = trim($_POST['address'] ?? '');
$uid  = !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null;

if (strlen($desc) < 5) respond(['success'=>false,'error'=>'Please describe the issue (min 5 characters)'], 400);
if (!is_numeric($lat) || !is_numeric($lng) || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180)
    respond(['success'=>false,'error'=>'Valid location is required'], 400);
$lat = (float)$lat; $lng = (float)$lng;

$allowed = ['Road/Pothole','Streetlight','Garbage','Water/Drainage','Other'];
$autoCat = empty($_POST['category']) || !in_array($_POST['category'], $allowed);
$category = $autoCat ? categorize($desc) : $_POST['category'];

try {
    $img = saveImage($_FILES['image'] ?? null);

    // Duplicate check: same category, not resolved, within 50 m
    $sql = "SELECT id, report_count, description, status, created_at,
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
        $score = calcScore($category, $dup['description'].' '.$desc, $count, $days);
        $sev = severityFromScore($score);

        $pdo->prepare("UPDATE issues SET report_count=?, priority_score=?, severity=? WHERE id=?")
            ->execute([$count, $score, $sev, $issueId]);
        $pdo->prepare("INSERT INTO status_log (issue_id, old_status, new_status, remark, changed_by) VALUES (?,?,?,?,?)")
            ->execute([$issueId, $dup['status'], $dup['status'], "Duplicate report merged (total reports: $count)", 'System']);
        $merged = true;
    } else {
        $score = calcScore($category, $desc, 1, 0);
        $sev = severityFromScore($score);
        $title = $category . ': ' . mb_substr($desc, 0, 60);

        $pdo->prepare("INSERT INTO issues (title, description, category, latitude, longitude, address, image_path, severity, priority_score)
                       VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$title, $desc, $category, $lat, $lng, $addr, $img, $sev, $score]);
        $issueId = (int)$pdo->lastInsertId();
        $pdo->prepare("INSERT INTO status_log (issue_id, old_status, new_status, remark, changed_by) VALUES (?,?,?,?,?)")
            ->execute([$issueId, null, 'Reported', 'Issue reported by citizen', 'System']);
        $merged = false;
    }

    $pdo->prepare("INSERT INTO reports (issue_id, user_id, description, image_path) VALUES (?,?,?,?)")
        ->execute([$issueId, $uid, $desc, $img]);
    $pdo->commit();

    respond([
        'success'  => true,
        'merged'   => $merged,
        'issue_id' => $issueId,
        'category' => $category,
        'auto_categorised' => $autoCat,
        'severity' => $sev,
        'priority_score' => $score,
        'message'  => $merged ? "Merged with existing issue #$issueId" : "New issue #$issueId created"
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(['success'=>false,'error'=>$e->getMessage()], 500);
}