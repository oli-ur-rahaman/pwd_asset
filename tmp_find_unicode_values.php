<?php
require __DIR__ . '/app/lib/bootstrap.php';
$sql = "SELECT f.field_key, f.label, v.value_text FROM asset_values v JOIN asset_fields f ON f.id = v.field_id WHERE v.value_text REGEXP '[^ -~]' LIMIT 5";
foreach (db()->query($sql) as $row) { echo $row['field_key'], ' | ', $row['label'], PHP_EOL; echo $row['value_text'], PHP_EOL; }
