<?php
require 'db.php';
try {
    $rows = $pdo->query("SELECT id,title,description,category,latitude,longitude,status,severity,priority_score,report_count,assigned_to,created_at
                         FROM issues ORDER BY priority_score DESC")->fetchAll();
    respond(['success' => true, 'issues' => $rows]);
} catch (Exception $e) {
    respond(['success' => false, 'error' => 'Could not load issues'], 500);
}