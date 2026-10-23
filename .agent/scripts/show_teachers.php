<?php
require __DIR__ . '/../../config.php';
$pdo = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8mb4', DB_USER, DB_PASS);
$rows = $pdo->query("SELECT name, email FROM users WHERE role = 'teacher'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo 'Nome: ' . $r['name'] . ' | Email: ' . $r['email'] . PHP_EOL;
}
if (empty($rows)) echo 'Nenhum professor cadastrado.' . PHP_EOL;
