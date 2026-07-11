<?php
require __DIR__ . '/app/lib/bootstrap.php';
$pdo = db();
echo json_encode($pdo->query('SELECT * FROM users LIMIT 3')->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
