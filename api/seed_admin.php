<?php
require 'db.php';
$hash = password_hash('admin123', PASSWORD_DEFAULT);
$pdo->prepare("INSERT IGNORE INTO users (name,email,password_hash,role) VALUES (?,?,?,'admin')")
    ->execute(['Admin', 'admin@civic.com', $hash]);
respond(['success'=>true,'msg'=>'Admin created: admin@civic.com / admin123']);