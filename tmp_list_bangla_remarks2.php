<?php
require __DIR__ . '/app/lib/bootstrap.php';
$stmt = db()->query("SELECT v.id, v.value_text FROM asset_values v JOIN asset_fields f ON f.id = v.field_id WHERE f.field_key = 'remarks_additional_information' AND v.value_text IS NOT NULL AND v.value_text <> '' LIMIT 200");
$count = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $text = (string)$row['value_text'];
    if (preg_match('/[^\x00-\x7F]/u', $text)) {
        echo 'ID:', $row['id'], PHP_EOL;
        echo mb_substr($text, 0, 300), PHP_EOL, '---', PHP_EOL;
        $count++;
        if ($count >= 10) {
            break;
        }
    }
}
