<?php
require 'db.php';
try {
    $total    = (int)$pdo->query("SELECT COUNT(*) FROM issues")->fetchColumn();
    $resolved = (int)$pdo->query("SELECT COUNT(*) FROM issues WHERE status='Resolved'")->fetchColumn();
    $overdue  = (int)$pdo->query("SELECT COUNT(*) FROM issues WHERE status!='Resolved' AND created_at < NOW() - INTERVAL 7 DAY")->fetchColumn();
    $avgHrs   = $pdo->query("SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) FROM issues WHERE status='Resolved'")->fetchColumn();
    $totalReports = (int)$pdo->query("SELECT COALESCE(SUM(report_count),0) FROM issues")->fetchColumn();

    respond([
        'success' => true,
        'total' => $total,
        'resolved' => $resolved,
        'open' => $total - $resolved,
        'overdue' => $overdue,
        'total_reports' => $totalReports,
        'duplicates_merged' => $totalReports - $total,
        'avg_resolution_hours' => $avgHrs ? round($avgHrs, 1) : 0,
        'by_category' => $pdo->query("SELECT category, COUNT(*) AS n FROM issues GROUP BY category")->fetchAll(),
        'by_status'   => $pdo->query("SELECT status, COUNT(*) AS n FROM issues GROUP BY status")->fetchAll(),
        'by_severity' => $pdo->query("SELECT severity, COUNT(*) AS n FROM issues GROUP BY severity")->fetchAll(),
        'last_7_days' => $pdo->query("SELECT DATE(created_at) AS d, COUNT(*) AS n FROM issues
                                      WHERE created_at >= NOW() - INTERVAL 7 DAY GROUP BY DATE(created_at) ORDER BY d")->fetchAll(),
    ]);
} catch (Exception $e) {
    respond(['success' => false, 'error' => 'Could not load stats'], 500);
}