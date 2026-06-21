<?php
require __DIR__ . '/app/lib/bootstrap.php';
$tables = db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $t) { echo $t, PHP_EOL; }
