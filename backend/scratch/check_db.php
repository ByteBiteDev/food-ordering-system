<?php
require_once __DIR__ . '/../includes/init.php';
$pdo = db();
$foods = $pdo->query("SELECT name, image FROM foods LIMIT 5")->fetchAll();
print_r($foods);
