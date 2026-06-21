<?php
$config = require __DIR__ . '/app/config.php';
$dsn = 'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['name'] . ';charset=' . $config['db']['charset'];
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach (['assets','asset_values','asset_fields'] as $table) {
    echo '--- ', $table, PHP_EOL;
    foreach ($pdo->query('DESCRIBE ' . $table) as $row) {
        echo $row['Field'], ' | ', $row['Type'], PHP_EOL;
    }
}
