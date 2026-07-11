<?php
require __DIR__ . '/app/lib/bootstrap.php';
$pdo = db();
$cols = $pdo->query('DESCRIBE segments')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
