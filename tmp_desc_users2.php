<?php
require __DIR__ . '/app/lib/bootstrap.php';
$pdo = db();
echo json_encode($pdo->query('DESCRIBE users')->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
