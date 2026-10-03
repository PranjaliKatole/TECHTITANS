<?php
require 'db.php';
try {
    // SELECT * so this keeps working whether or not the newer columns exist yet
    $rows = $pdo->query("SELECT * FROM issues ORDER BY priority_score DESC")->fetchAll();
    respond(['success' => true, 'issues' => $rows]);
} catch (Exception $e) {
    respond(['success' => false, 'error' => 'Could not load issues'], 500);
}