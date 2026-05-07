<?php
require_once __DIR__ . '/../includes/init.php';
$pdo = db();
$stmt = $pdo->query("SHOW CREATE TABLE foods");
print_r($stmt->fetch());
