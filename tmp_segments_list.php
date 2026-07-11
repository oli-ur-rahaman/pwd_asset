<?php
require __DIR__ . '/app/lib/bootstrap.php';
$pdo = db();
$rows = $pdo->query('SELECT * FROM segments ORDER BY id DESC LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
