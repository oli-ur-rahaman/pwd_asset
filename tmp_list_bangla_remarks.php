<?php
require __DIR__ . '/app/lib/bootstrap.php';
$sql = "SELECT v.id, LEFT(v.value_text, 300) AS snippet FROM asset_values v JOIN asset_fields f ON f.id = v.field_id WHERE f.field_key = 'remarks_additional_information' AND v.value_text REGEXP '[?-?]' LIMIT 10";
foreach (db()->query($sql) as $row) {
  echo 'ID:', $row['id'], PHP_EOL;
  echo $row['snippet'], PHP_EOL, '---', PHP_EOL;
}
