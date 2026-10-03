<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
try {
    $ok = !empty($_SESSION['uid']);
    echo json_encode([
        'logged_in' => $ok,
        'name' => $ok ? ($_SESSION['name'] ?? '') : null,
        'role' => $ok ? ($_SESSION['role'] ?? '') : null,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['logged_in' => false, 'error' => 'Session error']);
}