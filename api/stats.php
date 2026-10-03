<?php
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

try {
    // ---- Auto-escalation: only when an admin/worker is logged in ----
    $escalated = 0;
    $role = $_SESSION['role'] ?? '';
    if ($role === 'admin' || $role === 'worker') {
        try {
            $pdo->beginTransaction();
            $rows = $pdo->query(
                "SELECT id, status FROM issues
                 WHERE status != 'Resolved'
                   AND created_at < NOW() - INTERVAL 7 DAY
                   AND NOT EXISTS (
                       SELECT 1 FROM status_log s
                       WHERE s.issue_id = issues.id AND s.remark LIKE 'Auto-escalated%'
                   )"
            )->fetchAll();

            $upd = $pdo->prepare(
                "UPDATE issues
                 SET priority_score = LEAST(100, priority_score + 10),
                     severity = CASE
                         WHEN priority_score >= 80 THEN 'Critical'
                         WHEN priority_score >= 60 THEN 'High'
                         WHEN priority_score >= 40 THEN 'Medium'
                         ELSE 'Low' END
                 WHERE id = ?"
            );
            $log = $pdo->prepare(
                "INSERT INTO status_log (issue_id, old_status, new_status, remark, changed_by)
                 VALUES (?, ?, ?, 'Auto-escalated: overdue more than 7 days (+10 priority)', 'System')"
            );
            foreach ($rows as $r) {
                $upd->execute([$r['id']]);
                $log->execute([$r['id'], $r['status'], $r['status']]);
                $escalated++;
            }
            $pdo->commit();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $escalated = 0; // never break the stats because of escalation
        }
    }

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
        'escalated_now' => $escalated,
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