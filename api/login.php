<?php
session_start();
require 'db.php';
$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';
$st = $pdo->prepare("SELECT * FROM users WHERE email=? AND role IN ('admin','worker')");
$st->execute([$email]);
$u = $st->fetch();
if (!$u || !password_verify($pass, $u['password_hash']))
    respond(['success'=>false,'error'=>'Invalid email or password'], 401);
$_SESSION['uid'] = $u['id'];
$_SESSION['name'] = $u['name'];
respond(['success'=>true,'name'=>$u['name']]);