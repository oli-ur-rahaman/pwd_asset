<?php
$config = require __DIR__ . '/app/config.php';
$dsn = 'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['name'] . ';charset=' . $config['db']['charset'];
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$sql = "SELECT av.asset_id, af.label, av.value_text FROM asset_values av JOIN asset_fields af ON af.id = av.field_id WHERE av.value_text IS NOT NULL AND av.value_text <> '' ORDER BY av.id DESC LIMIT 5000";
$stmt = $pdo->query($sql);
$count = 0;
foreach ($stmt as $row) {
    $text = (string)$row['value_text'];
    if (preg_match('/[^\x00-\x7F]/u', $text)) {
        echo 'asset_id=', $row['asset_id'], ' | label=', $row['label'], PHP_EOL;
        echo $text, PHP_EOL;
        echo "-----", PHP_EOL;
        $count++;
        if ($count >= 5) {
            break;
        }
    }
}
if ($count === 0) {
    echo 'NO_NON_ASCII_FOUND', PHP_EOL;
}
