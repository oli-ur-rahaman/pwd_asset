<?php
require __DIR__ . '/app/lib/bootstrap.php';

$db_name = db()->query('SELECT DATABASE()')->fetchColumn();
$tables = db()->query('SELECT TABLE_NAME FROM information_schema.tables WHERE table_schema = DATABASE() ORDER BY TABLE_NAME')->fetchAll(PDO::FETCH_COLUMN);

$index_stmt = db()->prepare(
    'SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME ' .
    'FROM information_schema.statistics ' .
    'WHERE table_schema = DATABASE() AND table_name = ? ' .
    'ORDER BY INDEX_NAME, SEQ_IN_INDEX'
);

$fk_stmt = db()->prepare(
    'SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME ' .
    'FROM information_schema.key_column_usage ' .
    'WHERE table_schema = DATABASE() AND table_name = ? AND REFERENCED_TABLE_NAME IS NOT NULL ' .
    'ORDER BY CONSTRAINT_NAME, ORDINAL_POSITION'
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DB Indexes & FKs</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #222; }
        h1 { margin-bottom: 8px; }
        h2 { margin-top: 24px; }
        table { border-collapse: collapse; width: 100%; margin: 8px 0 16px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f2f2f2; }
        .muted { color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>Database Indexes & Foreign Keys</h1>
    <div class="muted">Database: <?= e((string)$db_name); ?></div>

    <?php foreach ($tables as $table): ?>
        <h2><?= e((string)$table); ?></h2>

        <?php
            $index_stmt->execute([$table]);
            $indexes = $index_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <h3>Indexes</h3>
        <?php if (!$indexes): ?>
            <div class="muted">No indexes found.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Index Name</th>
                        <th>Unique</th>
                        <th>Seq</th>
                        <th>Column</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($indexes as $idx): ?>
                        <tr>
                            <td><?= e($idx['INDEX_NAME']); ?></td>
                            <td><?= ((int)$idx['NON_UNIQUE'] === 0) ? 'Yes' : 'No'; ?></td>
                            <td><?= e($idx['SEQ_IN_INDEX']); ?></td>
                            <td><?= e($idx['COLUMN_NAME']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <?php
            $fk_stmt->execute([$table]);
            $fks = $fk_stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <h3>Foreign Keys</h3>
        <?php if (!$fks): ?>
            <div class="muted">No foreign keys found.</div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Constraint</th>
                        <th>Column</th>
                        <th>Ref Table</th>
                        <th>Ref Column</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fks as $fk): ?>
                        <tr>
                            <td><?= e($fk['CONSTRAINT_NAME']); ?></td>
                            <td><?= e($fk['COLUMN_NAME']); ?></td>
                            <td><?= e($fk['REFERENCED_TABLE_NAME']); ?></td>
                            <td><?= e($fk['REFERENCED_COLUMN_NAME']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endforeach; ?>
</body>
</html>
