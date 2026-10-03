<?php
session_start();
session_destroy();
require 'db.php';
respond(['success' => true]);