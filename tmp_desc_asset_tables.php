<?php
require __DIR__ . '/app/lib/bootstrap.php';
foreach (['asset_fields','asset_values'] as $t) {
  echo "TABLE:$t\n";
  foreach (db()->query("DESCRIBE $t") as $row) {
    echo $row['Field'], ' ', $row['Type'], PHP_EOL;
  }
}
