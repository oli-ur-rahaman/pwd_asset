<?php

function ensure_asset_schema(): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;

    db()->exec(
        "CREATE TABLE IF NOT EXISTS segments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            segment_name VARCHAR(255) NOT NULL,
            active_status TINYINT NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY uniq_segments_name (segment_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            segment_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            active_status TINYINT NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY uniq_asset_categories_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_subcategories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            segment_id INT DEFAULT NULL,
            category_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            active_status TINYINT NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            KEY idx_asset_subcategories_category (category_id),
            UNIQUE KEY uniq_asset_subcategories_name (category_id, name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_fields (
            id INT AUTO_INCREMENT PRIMARY KEY,
            segment_id INT DEFAULT NULL,
            field_key VARCHAR(100) NOT NULL,
            label VARCHAR(255) NOT NULL,
            data_type VARCHAR(20) NOT NULL,
            number_format_rule VARCHAR(30) DEFAULT NULL,
            secondary_of_field_id INT DEFAULT NULL,
            conditional_map_json LONGTEXT DEFAULT NULL,
            is_required TINYINT NOT NULL DEFAULT 0,
            is_displayed TINYINT NOT NULL DEFAULT 1,
            is_import_enabled TINYINT NOT NULL DEFAULT 1,
            is_unique TINYINT NOT NULL DEFAULT 0,
            active_status TINYINT NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            deleted_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY uniq_asset_fields_key (field_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_field_options (
            id INT AUTO_INCREMENT PRIMARY KEY,
            field_id INT NOT NULL,
            option_value VARCHAR(255) NOT NULL,
            option_label VARCHAR(255) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            active_status TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            KEY idx_asset_field_options_field (field_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS assets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            segment_id INT DEFAULT NULL,
            asset_number VARCHAR(50) NOT NULL,
            category_id INT NOT NULL,
            subcategory_id INT DEFAULT NULL,
            office_type TINYINT NOT NULL,
            office_id INT NOT NULL,
            active_status TINYINT NOT NULL DEFAULT 1,
            deleted_at DATETIME DEFAULT NULL,
            created_by INT NOT NULL,
            updated_by INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY uniq_assets_number (asset_number),
            KEY idx_assets_category (category_id),
            KEY idx_assets_subcategory (subcategory_id),
            KEY idx_assets_office (office_type, office_id),
            KEY idx_assets_active (active_status, deleted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS subdivisions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            office_name VARCHAR(255) NOT NULL,
            office_address TEXT DEFAULT NULL,
            office_type TINYINT NOT NULL DEFAULT 5,
            zone_id INT NOT NULL,
            circle_id INT NOT NULL,
            division_id INT NOT NULL,
            active_status TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            KEY idx_subdivisions_zone (zone_id),
            KEY idx_subdivisions_circle (circle_id),
            KEY idx_subdivisions_division (division_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_values (
            id INT AUTO_INCREMENT PRIMARY KEY,
            asset_id INT NOT NULL,
            field_id INT NOT NULL,
            value_text TEXT DEFAULT NULL,
            value_number DECIMAL(18,4) DEFAULT NULL,
            value_date DATE DEFAULT NULL,
            value_bool TINYINT DEFAULT NULL,
            value_option VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY uniq_asset_values (asset_id, field_id),
            KEY idx_asset_values_asset (asset_id),
            KEY idx_asset_values_field (field_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_field_file_rules (
            field_id INT NOT NULL PRIMARY KEY,
            is_multiple TINYINT NOT NULL DEFAULT 0,
            max_files INT NOT NULL DEFAULT 1,
            max_file_size_bytes BIGINT NOT NULL DEFAULT 0,
            max_total_size_bytes BIGINT NOT NULL DEFAULT 0,
            allowed_extensions TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_file_values (
            id INT AUTO_INCREMENT PRIMARY KEY,
            asset_id INT NOT NULL,
            field_id INT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            file_ext VARCHAR(20) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            file_size BIGINT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_asset_file_values_asset (asset_id),
            KEY idx_asset_file_values_field (field_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS office_asset_declarations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            segment_id INT DEFAULT NULL,
            office_type TINYINT NOT NULL,
            office_id INT NOT NULL,
            declared_status TINYINT NOT NULL DEFAULT 0,
            declared_at DATETIME DEFAULT NULL,
            declared_by INT DEFAULT NULL,
            declared_officer_name VARCHAR(255) DEFAULT NULL,
            reset_at DATETIME DEFAULT NULL,
            reset_by INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY uniq_asset_declaration (office_type, office_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_import_batches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            segment_id INT DEFAULT NULL,
            office_type TINYINT NOT NULL,
            office_id INT NOT NULL,
            uploaded_by INT NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            imported_count INT NOT NULL DEFAULT 0,
            skipped_count INT NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            segment_id INT DEFAULT NULL,
            asset_id INT NOT NULL,
            user_id INT NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            summary VARCHAR(255) NOT NULL,
            details LONGTEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_asset_activity_logs_asset (asset_id),
            KEY idx_asset_activity_logs_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_table_column_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            segment_id INT DEFAULT NULL,
            category_id INT NOT NULL,
            column_key VARCHAR(100) NOT NULL,
            is_visible TINYINT NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_asset_table_column_pref (user_id, category_id, column_key),
            KEY idx_asset_table_column_pref_user (user_id),
            KEY idx_asset_table_column_pref_category (category_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS office_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subject VARCHAR(255) NOT NULL,
            uploaded_by INT NOT NULL,
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    db()->exec(
        "CREATE TABLE IF NOT EXISTS office_order_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            office_order_id INT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            stored_name VARCHAR(255) NOT NULL,
            file_ext VARCHAR(20) NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_office_order_files_order (office_order_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );

    asset_ensure_column('zones', 'active_status', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('zones', 'allow_office_user_management', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('circles', 'active_status', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('circles', 'allow_office_user_management', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('divisions', 'active_status', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('divisions', 'allow_office_user_management', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('subdivisions', 'allow_office_user_management', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('users', 'subdivision_id', 'INT DEFAULT NULL');
    asset_ensure_column('users', 'is_primary_office_user', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('users', 'office_access_level', 'TINYINT NOT NULL DEFAULT 2');
    asset_ensure_column('office_asset_declarations', 'declared_officer_name', 'VARCHAR(255) DEFAULT NULL');
    asset_ensure_column('info', 'welcome_message', 'LONGTEXT DEFAULT NULL');
    asset_ensure_column('info', 'asset_subcategory_enabled', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('info', 'asset_number_visible_to_users', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('info', 'asset_filter_distinct_threshold', 'INT NOT NULL DEFAULT 20');
    asset_ensure_column('segments', 'asset_subcategory_enabled', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('asset_fields', 'is_unique', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'is_filter_enabled', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'number_format_rule', 'VARCHAR(30) DEFAULT NULL');
    asset_ensure_column('asset_fields', 'secondary_of_field_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_fields', 'conditional_map_json', 'LONGTEXT DEFAULT NULL');
    asset_ensure_column('asset_categories', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_subcategories', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_fields', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('assets', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('office_asset_declarations', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_import_batches', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_activity_logs', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_table_column_preferences', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_segment_indexes();
    asset_relax_subcategory_requirement();
    asset_backfill_segment_assignments();

    asset_seed_default_fields();
    asset_backfill_office_user_access_levels();
}

function asset_ensure_column(string $table, string $column, string $definition): void
{
    $stmt = db()->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
    $stmt->execute([$table, $column]);
    if ($stmt->fetch()) {
        return;
    }
    db()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
}

function asset_index_exists(string $table, string $indexName): bool
{
    $stmt = db()->prepare('SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1');
    $stmt->execute([$table, $indexName]);
    return (bool)$stmt->fetchColumn();
}

function asset_drop_index_if_exists(string $table, string $indexName): void
{
    if (!asset_index_exists($table, $indexName)) {
        return;
    }
    db()->exec("ALTER TABLE {$table} DROP INDEX {$indexName}");
}

function asset_ensure_unique_index(string $table, string $indexName, array $columns): void
{
    if (asset_index_exists($table, $indexName)) {
        return;
    }
    $columnSql = implode(', ', $columns);
    db()->exec("ALTER TABLE {$table} ADD UNIQUE KEY {$indexName} ({$columnSql})");
}

function asset_ensure_index(string $table, string $indexName, array $columns): void
{
    if (asset_index_exists($table, $indexName)) {
        return;
    }
    $columnSql = implode(', ', $columns);
    db()->exec("ALTER TABLE {$table} ADD KEY {$indexName} ({$columnSql})");
}

function asset_ensure_segment_indexes(): void
{
    asset_drop_index_if_exists('asset_categories', 'uniq_asset_categories_name');
    asset_ensure_unique_index('asset_categories', 'uniq_asset_categories_segment_name', ['segment_id', 'name']);
    asset_ensure_index('asset_categories', 'idx_asset_categories_segment', ['segment_id']);

    asset_ensure_index('asset_subcategories', 'idx_asset_subcategories_segment', ['segment_id']);

    asset_drop_index_if_exists('asset_fields', 'uniq_asset_fields_key');
    asset_ensure_unique_index('asset_fields', 'uniq_asset_fields_segment_key', ['segment_id', 'field_key']);
    asset_ensure_index('asset_fields', 'idx_asset_fields_segment', ['segment_id']);

    asset_ensure_index('assets', 'idx_assets_segment', ['segment_id']);

    asset_drop_index_if_exists('office_asset_declarations', 'uniq_asset_declaration');
    asset_ensure_unique_index('office_asset_declarations', 'uniq_asset_declaration_segment', ['segment_id', 'office_type', 'office_id']);
    asset_ensure_index('office_asset_declarations', 'idx_asset_declaration_segment', ['segment_id']);

    asset_ensure_index('asset_import_batches', 'idx_asset_import_batches_segment', ['segment_id']);
    asset_ensure_index('asset_activity_logs', 'idx_asset_activity_logs_segment', ['segment_id']);

    asset_drop_index_if_exists('asset_table_column_preferences', 'uniq_asset_table_column_pref');
    asset_ensure_unique_index('asset_table_column_preferences', 'uniq_asset_table_column_pref_segment', ['user_id', 'segment_id', 'category_id', 'column_key']);
    asset_ensure_index('asset_table_column_preferences', 'idx_asset_table_column_pref_segment', ['segment_id']);
}

function asset_default_segment_name(): string
{
    return 'General';
}

function get_asset_segments(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM segments';
    if (!$includeInactive) {
        $sql .= ' WHERE active_status = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, segment_name ASC, id ASC';
    $rows = db()->query($sql)->fetchAll();
    if (!$rows) {
        asset_default_segment_id();
        $rows = db()->query($sql)->fetchAll();
    }
    return $rows;
}

function create_asset_segment(string $segmentName): int
{
    $segmentName = trim($segmentName);
    if ($segmentName === '') {
        throw new RuntimeException('Segment name is required.');
    }
    $stmt = db()->prepare('SELECT id FROM segments WHERE LOWER(segment_name) = LOWER(?) LIMIT 1');
    $stmt->execute([$segmentName]);
    if ($stmt->fetchColumn()) {
        throw new RuntimeException('Segment name already exists.');
    }
    $sortOrder = ((int)db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM segments')->fetchColumn()) + 10;
    $insert = db()->prepare('INSERT INTO segments (segment_name, active_status, sort_order, created_at) VALUES (?, 1, ?, NOW())');
    $insert->execute([$segmentName, $sortOrder]);
    return (int)db()->lastInsertId();
}

function update_asset_segment(int $segmentId, string $segmentName): void
{
    $segmentName = trim($segmentName);
    if ($segmentId <= 0 || $segmentName === '') {
        throw new RuntimeException('Segment name is required.');
    }
    $stmt = db()->prepare('SELECT id FROM segments WHERE LOWER(segment_name) = LOWER(?) AND id <> ? LIMIT 1');
    $stmt->execute([$segmentName, $segmentId]);
    if ($stmt->fetchColumn()) {
        throw new RuntimeException('Segment name already exists.');
    }
    db()->prepare('UPDATE segments SET segment_name = ?, updated_at = NOW() WHERE id = ?')->execute([$segmentName, $segmentId]);
}

function set_asset_segment_status(int $segmentId, int $status): void
{
    if ($segmentId <= 0) {
        throw new RuntimeException('Invalid segment.');
    }
    $segment = get_asset_segment($segmentId);
    if (!$segment) {
        throw new RuntimeException('Segment not found.');
    }
    $normalizedStatus = $status === 1 ? 1 : 0;
    if ($normalizedStatus === 0) {
        $activeCount = (int)db()->query('SELECT COUNT(*) FROM segments WHERE active_status = 1')->fetchColumn();
        if ($activeCount <= 1 && (int)($segment['active_status'] ?? 0) === 1) {
            throw new RuntimeException('At least one active segment is required.');
        }
    }
    db()->prepare('UPDATE segments SET active_status = ?, updated_at = NOW() WHERE id = ?')->execute([$normalizedStatus, $segmentId]);
}

function get_asset_segment(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM segments WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function asset_default_segment_id(): int
{
    static $segmentId = null;
    if ($segmentId !== null) {
        return $segmentId;
    }
    $stmt = db()->prepare('SELECT id FROM segments WHERE segment_name = ? LIMIT 1');
    $stmt->execute([asset_default_segment_name()]);
    $existingId = (int)($stmt->fetchColumn() ?: 0);
    if ($existingId > 0) {
        $segmentId = $existingId;
        return $segmentId;
    }

    db()->prepare(
        'INSERT INTO segments (segment_name, active_status, sort_order, created_at) VALUES (?, 1, 10, NOW())'
    )->execute([asset_default_segment_name()]);
    $segmentId = (int)db()->lastInsertId();
    return $segmentId;
}

function asset_requested_segment_id(): ?int
{
    $raw = $_POST['segment_id'] ?? $_GET['segment_id'] ?? ($_SESSION['asset_active_segment_id'] ?? null);
    $segmentId = (int)$raw;
    return $segmentId > 0 ? $segmentId : null;
}

function asset_active_segment_id(?int $segmentId = null, bool $allowInactive = false): int
{
    $candidate = $segmentId && $segmentId > 0 ? $segmentId : asset_requested_segment_id();
    if ($candidate && $candidate > 0) {
        $sql = 'SELECT id FROM segments WHERE id = ?';
        if (!$allowInactive) {
            $sql .= ' AND active_status = 1';
        }
        $sql .= ' LIMIT 1';
        $stmt = db()->prepare($sql);
        $stmt->execute([$candidate]);
        $resolved = (int)($stmt->fetchColumn() ?: 0);
        if ($resolved > 0) {
            $_SESSION['asset_active_segment_id'] = $resolved;
            return $resolved;
        }
    }

    $sql = 'SELECT id FROM segments';
    if (!$allowInactive) {
        $sql .= ' WHERE active_status = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, segment_name ASC, id ASC LIMIT 1';
    $resolved = (int)(db()->query($sql)->fetchColumn() ?: 0);
    if ($resolved <= 0) {
        $resolved = asset_default_segment_id();
    }
    $_SESSION['asset_active_segment_id'] = $resolved;
    return $resolved;
}

function asset_active_segment(?int $segmentId = null, bool $allowInactive = false): ?array
{
    return get_asset_segment(asset_active_segment_id($segmentId, $allowInactive));
}

function asset_has_multiple_segments(bool $includeInactive = false): bool
{
    return count(get_asset_segments($includeInactive)) > 1;
}

function asset_normalize_segment_id(?int $segmentId = null): int
{
    return asset_active_segment_id($segmentId);
}

function asset_backfill_segment_assignments(): void
{
    $defaultSegmentId = asset_default_segment_id();
    $tableColumns = [
        'asset_categories' => 'segment_id',
        'asset_subcategories' => 'segment_id',
        'asset_fields' => 'segment_id',
        'assets' => 'segment_id',
        'office_asset_declarations' => 'segment_id',
        'asset_import_batches' => 'segment_id',
        'asset_activity_logs' => 'segment_id',
        'asset_table_column_preferences' => 'segment_id',
    ];

    foreach ($tableColumns as $table => $column) {
        db()->prepare("UPDATE {$table} SET {$column} = ? WHERE {$column} IS NULL OR {$column} <= 0")
            ->execute([$defaultSegmentId]);
    }
}

function asset_relax_subcategory_requirement(): void
{
    $stmt = db()->prepare('SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
    $stmt->execute(['assets', 'subcategory_id']);
    $isNullable = strtoupper((string)$stmt->fetchColumn());
    if ($isNullable !== 'YES') {
        db()->exec('ALTER TABLE assets MODIFY COLUMN subcategory_id INT DEFAULT NULL');
    }
}

function asset_backfill_office_user_access_levels(): void
{
    db()->exec('UPDATE users SET is_primary_office_user = 0 WHERE office_role IN (2,3) AND is_primary_office_user <> 0');
    db()->exec('UPDATE users SET office_access_level = 0 WHERE office_role = 2 AND office_access_level <> 0');
    $superadminRows = db()->query('SELECT id, office_access_level FROM users WHERE office_role = 3 ORDER BY id ASC')->fetchAll();
    foreach ($superadminRows as $index => $row) {
        $expectedLevel = $index === 0 ? 0 : 3;
        if ((int)($row['office_access_level'] ?? -1) === $expectedLevel) {
            continue;
        }
        db()->prepare('UPDATE users SET office_access_level = ?, updated_at = NOW() WHERE id = ?')->execute([
            $expectedLevel,
            (int)$row['id'],
        ]);
    }
    foreach ([2 => 'zone_id', 3 => 'circle_id', 4 => 'division_id', 5 => 'subdivision_id'] as $officeType => $column) {
        $stmt = db()->prepare("SELECT id, {$column} AS office_id, is_primary_office_user, office_access_level FROM users WHERE office_role = 1 AND office_type = ? AND {$column} IS NOT NULL AND {$column} > 0 ORDER BY id ASC");
        $stmt->execute([$officeType]);
        $grouped = [];
        foreach ($stmt->fetchAll() as $row) {
            $officeId = (int)$row['office_id'];
            if ($officeId <= 0) {
                continue;
            }
            $grouped[$officeId][] = $row;
        }

        foreach ($grouped as $rows) {
            $primaryId = 0;
            foreach ($rows as $row) {
                if ((int)($row['is_primary_office_user'] ?? 0) === 1) {
                    $primaryId = (int)$row['id'];
                    break;
                }
            }
            if ($primaryId <= 0) {
                $primaryId = (int)$rows[0]['id'];
            }

            foreach ($rows as $row) {
                $userId = (int)$row['id'];
                $isPrimary = $userId === $primaryId;
                $currentLevel = (int)($row['office_access_level'] ?? 0);
                $nextLevel = $isPrimary ? 1 : ($currentLevel === 3 ? 3 : 2);
                $currentPrimary = (int)($row['is_primary_office_user'] ?? 0);

                if ($currentPrimary === ($isPrimary ? 1 : 0) && $currentLevel === $nextLevel) {
                    continue;
                }

                db()->prepare('UPDATE users SET is_primary_office_user = ?, office_access_level = ?, updated_at = NOW() WHERE id = ?')->execute([
                    $isPrimary ? 1 : 0,
                    $nextLevel,
                    $userId,
                ]);
            }
        }
    }
}

function asset_seed_default_fields(): void
{
    $segmentId = asset_default_segment_id();
    $defaults = [
        [
            'field_key' => 'description',
            'label' => 'Description / বিবরণ',
            'data_type' => 'text',
            'is_required' => 1,
            'sort_order' => 10,
        ],
        [
            'field_key' => 'purchase_date',
            'label' => 'Date of Purchase / ক্রয়ের তারিখ',
            'data_type' => 'date',
            'is_required' => 0,
            'sort_order' => 20,
        ],
        [
            'field_key' => 'condition_value',
            'label' => 'Condition / অবস্থা',
            'data_type' => 'dropdown',
            'is_required' => 1,
            'sort_order' => 30,
            'options' => [
                ['value' => 'যোগ্য', 'label' => 'যোগ্য'],
                ['value' => 'অযোগ্য', 'label' => 'অযোগ্য'],
            ],
        ],
        [
            'field_key' => 'remarks',
            'label' => 'Remarks / মন্তব্য',
            'data_type' => 'text',
            'is_required' => 0,
            'sort_order' => 40,
        ],
    ];

    foreach ($defaults as $field) {
        $stmt = db()->prepare('SELECT id FROM asset_fields WHERE segment_id = ? AND field_key = ? LIMIT 1');
        $stmt->execute([$segmentId, $field['field_key']]);
        $fieldId = (int)($stmt->fetchColumn() ?: 0);
        if ($fieldId === 0) {
            $insert = db()->prepare('INSERT INTO asset_fields (segment_id, field_key, label, data_type, is_required, is_displayed, is_import_enabled, active_status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, 1, 1, 1, ?, NOW())');
            $insert->execute([
                $segmentId,
                $field['field_key'],
                $field['label'],
                $field['data_type'],
                $field['is_required'],
                $field['sort_order'],
            ]);
            $fieldId = (int)db()->lastInsertId();
        }

        if (!empty($field['options'])) {
            foreach ($field['options'] as $idx => $option) {
                $exists = db()->prepare('SELECT id FROM asset_field_options WHERE field_id = ? AND option_value = ? LIMIT 1');
                $exists->execute([$fieldId, $option['value']]);
                if ($exists->fetchColumn()) {
                    continue;
                }
                $optionStmt = db()->prepare('INSERT INTO asset_field_options (field_id, option_value, option_label, sort_order, active_status, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
                $optionStmt->execute([$fieldId, $option['value'], $option['label'], ($idx + 1) * 10]);
            }
        }
    }
}

function asset_supported_data_types(): array
{
    return ['text', 'number', 'date', 'dropdown', 'yes_no', 'file', 'conditional'];
}

function asset_number_format_rule_examples(): array
{
    return [
        '8.2 -> max 8 digits before decimal and max 2 digits after decimal (no negative allowed)',
        '-8.2 -> max 8 digits before decimal and max 2 digits after decimal (negative allowed)',
        '*8.2 -> exact 8 digits before decimal and max 2 digits after decimal (no negative allowed)',
        '*8.*2 -> exact 8 digits before decimal and exact 2 digits after decimal (no negative allowed)',
        '-*8.*2 -> exact 8 digits before decimal and exact 2 digits after decimal (negative allowed)',
    ];
}

function asset_is_conditional_secondary(array $field): bool
{
    return (int)($field['secondary_of_field_id'] ?? 0) > 0;
}

function asset_is_conditional_primary(array $field): bool
{
    return (string)($field['data_type'] ?? '') === 'conditional';
}

function get_asset_management_fields(bool $includeInactive = false, ?int $segmentId = null): array
{
    return array_values(array_filter(
        get_asset_fields($includeInactive, $segmentId),
        static fn(array $field): bool => !asset_is_conditional_secondary($field)
    ));
}

function get_asset_conditional_child_field(int $parentFieldId, bool $includeInactive = true, ?int $segmentId = null): ?array
{
    foreach (get_asset_fields($includeInactive, $segmentId) as $field) {
        if ((int)($field['secondary_of_field_id'] ?? 0) === $parentFieldId) {
            return $field;
        }
    }
    return null;
}

function asset_assert_segment_match(?array $row, ?int $segmentId, string $label): array
{
    if (!$row) {
        throw new RuntimeException("{$label} not found.");
    }
    $normalizedSegmentId = asset_normalize_segment_id($segmentId);
    if ((int)($row['segment_id'] ?? 0) !== $normalizedSegmentId) {
        throw new RuntimeException("{$label} does not belong to the selected segment.");
    }
    return $row;
}

function next_sort_order_for_filters(string $table, array $conditions = []): int
{
    $sql = "SELECT COALESCE(MAX(sort_order), 0) FROM {$table}";
    $params = [];
    if ($conditions) {
        $clauses = [];
        foreach ($conditions as $column => $value) {
            $clauses[] = "{$column} = ?";
            $params[] = $value;
        }
        $sql .= ' WHERE ' . implode(' AND ', $clauses);
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() + 10;
}

function asset_decode_conditional_map(array $field): array
{
    $raw = (string)($field['conditional_map_json'] ?? '');
    if ($raw === '') {
        return [];
    }
    $map = json_decode($raw, true);
    if (!is_array($map)) {
        return [];
    }
    $normalized = [];
    foreach ($map as $primary => $options) {
        $primary = trim((string)$primary);
        if ($primary === '') {
            continue;
        }
        $normalized[$primary] = array_values(array_filter(array_map(
            static fn(mixed $item): string => trim((string)$item),
            is_array($options) ? $options : []
        ), static fn(string $item): bool => $item !== ''));
    }
    return $normalized;
}

function asset_conditional_child_options(array $parentField, string $parentValue): array
{
    $map = asset_decode_conditional_map($parentField);
    foreach ($map as $primary => $options) {
        if (strcasecmp($primary, trim($parentValue)) === 0) {
            return $options;
        }
    }
    return [];
}

function asset_conditional_union_options(array $map): array
{
    $all = [];
    foreach ($map as $options) {
        foreach ($options as $option) {
            $option = trim((string)$option);
            if ($option !== '') {
                $all[$option] = $option;
            }
        }
    }
    return array_values($all);
}

function asset_parse_number_format_rule(string $rule): ?array
{
    $rule = trim($rule);
    if ($rule === '') {
        return null;
    }
    if (!preg_match('/^(-)?(\*)?(\d+)\.(\*)?(\d+)$/', $rule, $matches)) {
        return null;
    }
    return [
        'allow_negative' => $matches[1] === '-',
        'before_exact' => $matches[2] === '*',
        'before_digits' => (int)$matches[3],
        'after_exact' => $matches[4] === '*',
        'after_digits' => (int)$matches[5],
    ];
}

function asset_normalize_number_string(mixed $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }
    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
        return null;
    }
    return $value;
}

function asset_number_format_error(string $label, array $parsedRule): string
{
    $beforeText = ($parsedRule['before_exact'] ? 'exactly ' : 'at most ') . $parsedRule['before_digits'] . ' digit' . ($parsedRule['before_digits'] === 1 ? '' : 's') . ' before decimal';
    $afterText = ($parsedRule['after_exact'] ? 'exactly ' : 'at most ') . $parsedRule['after_digits'] . ' digit' . ($parsedRule['after_digits'] === 1 ? '' : 's') . ' after decimal';
    $signText = $parsedRule['allow_negative'] ? 'negative allowed' : 'no negative allowed';
    return "{$label} must follow {$beforeText} and {$afterText} ({$signText}).";
}

function asset_number_matches_rule(string $value, array $parsedRule): bool
{
    if (!preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
        return false;
    }
    if (!$parsedRule['allow_negative'] && str_starts_with($value, '-')) {
        return false;
    }
    $unsigned = ltrim($value, '-');
    [$before, $after] = array_pad(explode('.', $unsigned, 2), 2, '');
    $beforeLength = strlen($before);
    $afterLength = strlen($after);
    if ($parsedRule['before_exact']) {
        if ($beforeLength !== $parsedRule['before_digits']) {
            return false;
        }
    } elseif ($beforeLength > $parsedRule['before_digits']) {
        return false;
    }
    if ($parsedRule['after_exact']) {
        if ($afterLength !== $parsedRule['after_digits']) {
            return false;
        }
    } elseif ($afterLength > $parsedRule['after_digits']) {
        return false;
    }
    return true;
}

function asset_locked_field_keys(): array
{
    return [];
}

function asset_file_storage_dir(): string
{
    return dirname(__DIR__, 2) . '/storage/asset_files';
}

function ensure_asset_file_storage_dir(): string
{
    $dir = asset_file_storage_dir();
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create asset file storage directory.');
    }
    return $dir;
}

function asset_default_file_rule(): array
{
    return [
        'is_multiple' => 0,
        'max_files' => 1,
        'max_file_size_bytes' => 0,
        'max_total_size_bytes' => 0,
        'allowed_extensions' => 'pdf,jpg,jpeg,png,doc,docx,xls,xlsx,txt',
    ];
}

function asset_parse_extensions_string(string $value): array
{
    $parts = preg_split('/[\s,]+/', strtolower(trim($value)));
    $parts = array_values(array_unique(array_filter(array_map(static function (string $item): string {
        return ltrim(trim($item), '.');
    }, $parts ?: []))));
    return $parts;
}

function asset_extensions_string(array $extensions): string
{
    return implode(',', asset_parse_extensions_string(implode(',', $extensions)));
}

function asset_bytes_from_megabytes(mixed $value): int
{
    $number = (float)$value;
    if ($number <= 0) {
        return 0;
    }
    return (int)round($number * 1024 * 1024);
}

function asset_megabytes_from_bytes(int $bytes): string
{
    if ($bytes <= 0) {
        return '';
    }
    $mb = $bytes / 1024 / 1024;
    return rtrim(rtrim(number_format($mb, 2, '.', ''), '0'), '.');
}

function asset_allowed_file_mime_types(): array
{
    return [
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
    ];
}

function asset_uploaded_files_for_field(array $fileBag, string $fieldKey): array
{
    if (!isset($fileBag['name'][$fieldKey])) {
        return [];
    }
    $names = $fileBag['name'][$fieldKey];
    $types = $fileBag['type'][$fieldKey] ?? [];
    $tmpNames = $fileBag['tmp_name'][$fieldKey] ?? [];
    $errors = $fileBag['error'][$fieldKey] ?? [];
    $sizes = $fileBag['size'][$fieldKey] ?? [];

    if (!is_array($names)) {
        $names = [$names];
        $types = [$types];
        $tmpNames = [$tmpNames];
        $errors = [$errors];
        $sizes = [$sizes];
    }

    $files = [];
    foreach ($names as $index => $name) {
        $files[] = [
            'name' => $name,
            'type' => $types[$index] ?? '',
            'tmp_name' => $tmpNames[$index] ?? '',
            'error' => $errors[$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => (int)($sizes[$index] ?? 0),
        ];
    }
    return $files;
}

function asset_core_columns(): array
{
    return [
        ['key' => '__sl', 'label' => 'SL No'],
        ['key' => 'asset_number', 'label' => 'Asset Number / সম্পদ নং'],
        ['key' => 'subcategory_name', 'label' => 'Sub-category / উপ-শ্রেণি'],
    ];
}

function asset_subcategory_enabled(?int $segmentId = null): bool
{
    $normalizedSegmentId = asset_normalize_segment_id($segmentId);
    $segment = get_asset_segment($normalizedSegmentId, true);
    if ($segment && array_key_exists('asset_subcategory_enabled', $segment)) {
        return (int)($segment['asset_subcategory_enabled'] ?? 1) === 1;
    }
    $info = get_info_row();
    $value = $info['asset_subcategory_enabled'] ?? null;
    return $value === null || $value === '' ? true : (int)$value === 1;
}

function set_asset_subcategory_enabled(int $status, ?int $segmentId = null): void
{
    db()->prepare('UPDATE segments SET asset_subcategory_enabled = ?, updated_at = NOW() WHERE id = ?')->execute([
        $status === 1 ? 1 : 0,
        asset_normalize_segment_id($segmentId),
    ]);
}

function asset_number_visible_to_users(): bool
{
    $info = get_info_row();
    $value = $info['asset_number_visible_to_users'] ?? null;
    if ($value === null || $value === '') {
        return true;
    }
    return (int)$value === 1;
}

function set_asset_number_visible_to_users(int $status): void
{
    $existing = get_info_row();
    save_info_row(
        $existing['video_tutorial_url'] ?? null,
        $existing['login_message'] ?? null,
        [
            'site_name' => $existing['site_name'] ?? null,
            'welcome_message' => $existing['welcome_message'] ?? null,
            'asset_subcategory_enabled' => $existing['asset_subcategory_enabled'] ?? 1,
            'asset_number_visible_to_users' => $status === 1 ? 1 : 0,
            'asset_filter_distinct_threshold' => $existing['asset_filter_distinct_threshold'] ?? 20,
            'i_opr_repair' => $existing['i_opr_repair'] ?? null,
            'i_opr_other' => $existing['i_opr_other'] ?? null,
            'i_dev_pw' => $existing['i_dev_pw'] ?? null,
            'i_opr_min' => $existing['i_opr_min'] ?? null,
            'i_dev_min' => $existing['i_dev_min'] ?? null,
            'i_opr' => $existing['i_opr'] ?? null,
            'i_dev' => $existing['i_dev'] ?? null,
        ]
    );
}

function asset_filter_distinct_threshold(): int
{
    $info = get_info_row();
    $value = (int)($info['asset_filter_distinct_threshold'] ?? 20);
    return $value > 0 ? $value : 20;
}

function set_asset_filter_distinct_threshold(int $threshold): void
{
    $existing = get_info_row();
    save_info_row(
        $existing['video_tutorial_url'] ?? null,
        $existing['login_message'] ?? null,
        [
            'site_name' => $existing['site_name'] ?? null,
            'welcome_message' => $existing['welcome_message'] ?? null,
            'asset_subcategory_enabled' => $existing['asset_subcategory_enabled'] ?? 1,
            'asset_number_visible_to_users' => $existing['asset_number_visible_to_users'] ?? 1,
            'asset_filter_distinct_threshold' => max(1, $threshold),
            'i_opr_repair' => $existing['i_opr_repair'] ?? null,
            'i_opr_other' => $existing['i_opr_other'] ?? null,
            'i_dev_pw' => $existing['i_dev_pw'] ?? null,
            'i_opr_min' => $existing['i_opr_min'] ?? null,
            'i_dev_min' => $existing['i_dev_min'] ?? null,
            'i_opr' => $existing['i_opr'] ?? null,
            'i_dev' => $existing['i_dev'] ?? null,
        ]
    );
}

function asset_table_preference_category_id(int $categoryId, string $tableScope = 'my_office'): int
{
    return $tableScope === 'office_under_me' ? $categoryId + 1000000 : $categoryId;
}

function asset_table_available_columns(array $fields, array $uiFieldLabels, string $tableScope = 'my_office', ?int $segmentId = null): array
{
    $columns = [];
    if (is_superadmin() || asset_number_visible_to_users()) {
        $columns[] = ['key' => 'asset_number', 'label' => 'Asset Number', 'type' => 'fixed'];
    }
    if (is_superadmin() || $tableScope === 'office_under_me') {
        $columns[] = ['key' => 'office_name', 'label' => 'Office', 'type' => 'fixed'];
    }
    if (asset_subcategory_enabled($segmentId)) {
        $columns[] = ['key' => 'subcategory_name', 'label' => 'Sub-category', 'type' => 'fixed'];
    }
    $columns[] = ['key' => 'data_provider', 'label' => 'Data Provider', 'type' => 'fixed'];
    foreach ($fields as $field) {
        if ((int)$field['is_displayed'] !== 1 || (int)$field['active_status'] !== 1) {
            continue;
        }
        $columns[] = [
            'key' => (string)$field['field_key'],
            'label' => (string)($uiFieldLabels[$field['field_key']] ?? $field['label']),
            'type' => 'field',
        ];
    }
    return $columns;
}

function get_asset_table_column_preferences(int $userId, ?int $segmentId = null): array
{
    $stmt = db()->prepare('SELECT category_id, column_key, is_visible FROM asset_table_column_preferences WHERE user_id = ? AND segment_id = ?');
    $stmt->execute([$userId, asset_normalize_segment_id($segmentId)]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int)$row['category_id']][(string)$row['column_key']] = (int)$row['is_visible'] === 1;
    }
    return $map;
}

function resolve_asset_table_visible_column_keys(int $categoryId, array $availableColumns, array $preferenceMap): array
{
    $visible = [];
    $saved = $preferenceMap[$categoryId] ?? [];
    foreach ($availableColumns as $column) {
        $columnKey = (string)$column['key'];
        $visible[$columnKey] = array_key_exists($columnKey, $saved) ? (bool)$saved[$columnKey] : true;
    }
    return $visible;
}

function save_asset_table_column_preferences(int $userId, int $categoryId, array $availableColumns, array $visibleKeys, bool $applyToAll = false, string $tableScope = 'my_office', ?int $segmentId = null): void
{
    if ($userId <= 0 || $categoryId <= 0) {
        throw new RuntimeException('Invalid visibility target.');
    }
    $segmentId = asset_normalize_segment_id($segmentId);
    $availableKeys = array_map(static fn(array $column): string => (string)$column['key'], $availableColumns);
    $normalizedVisible = array_fill_keys($availableKeys, false);
    foreach ($visibleKeys as $key) {
        $key = (string)$key;
        if (isset($normalizedVisible[$key])) {
            $normalizedVisible[$key] = true;
        }
    }
    $targetCategoryIds = [$categoryId];
    if ($applyToAll) {
        $targetCategoryIds = array_map(static fn(array $category): int => (int)$category['id'], get_asset_categories(false, $segmentId));
    }
    $stmt = db()->prepare(
        'INSERT INTO asset_table_column_preferences (user_id, segment_id, category_id, column_key, is_visible, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE is_visible = VALUES(is_visible), updated_at = NOW()'
    );
    foreach ($targetCategoryIds as $targetCategoryId) {
        $prefCategoryId = asset_table_preference_category_id($targetCategoryId, $tableScope);
        foreach ($normalizedVisible as $columnKey => $isVisible) {
            $stmt->execute([$userId, $segmentId, $prefCategoryId, $columnKey, $isVisible ? 1 : 0]);
        }
    }
}

function add_asset_activity_log(int $assetId, int $userId, string $actionType, string $summary, array $details = []): void
{
    $asset = get_asset($assetId);
    $segmentId = (int)($asset['segment_id'] ?? asset_default_segment_id());
    $stmt = db()->prepare(
        'INSERT INTO asset_activity_logs (segment_id, asset_id, user_id, action_type, summary, details, created_at)
         VALUES (?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        $segmentId,
        $assetId,
        $userId,
        $actionType,
        $summary,
        $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
    ]);
}

function get_asset_activity_logs(int $assetId): array
{
    $stmt = db()->prepare(
        'SELECT l.*, u.email_id
         FROM asset_activity_logs l
         JOIN users u ON u.id = l.user_id
         WHERE l.asset_id = ?
         ORDER BY l.id DESC'
    );
    $stmt->execute([$assetId]);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$row) {
        $detailItems = [];
        if (!empty($row['details'])) {
            $detailItems = json_decode((string)$row['details'], true);
            if (!is_array($detailItems)) {
                $detailItems = [];
            }
        }
        $row['detail_items'] = $detailItems;
    }
    unset($row);
    return $rows;
}

function asset_format_normalized_value_for_log(array $field, ?array $normalized): string
{
    if ($normalized === null) {
        return '';
    }
    return match ((string)$field['data_type']) {
        'number' => $normalized['value_number'] !== null ? rtrim(rtrim((string)$normalized['value_number'], '0'), '.') : '',
        'date' => (string)($normalized['value_date'] ?? ''),
        'yes_no' => $normalized['value_bool'] === null ? '' : ((int)$normalized['value_bool'] === 1 ? 'Yes' : 'No'),
        'dropdown', 'conditional' => (string)($normalized['value_option'] ?? ''),
        default => (string)($normalized['value_text'] ?? ''),
    };
}

function asset_category_name_by_id(int $categoryId): string
{
    if ($categoryId <= 0) {
        return '';
    }
    $stmt = db()->prepare('SELECT name FROM asset_categories WHERE id = ? LIMIT 1');
    $stmt->execute([$categoryId]);
    return (string)($stmt->fetchColumn() ?: '');
}

function asset_subcategory_name_by_id(int $subcategoryId): string
{
    if ($subcategoryId <= 0) {
        return '';
    }
    $stmt = db()->prepare('SELECT name FROM asset_subcategories WHERE id = ? LIMIT 1');
    $stmt->execute([$subcategoryId]);
    return (string)($stmt->fetchColumn() ?: '');
}

function build_asset_create_log_details(array $validated, array $fieldMap): array
{
    $details = [
        ['field' => 'Category', 'value' => asset_category_name_by_id((int)$validated['category_id'])],
    ];
    if (asset_subcategory_enabled((int)($validated['segment_id'] ?? 0))) {
        $details[] = ['field' => 'Sub-category', 'value' => asset_subcategory_name_by_id((int)$validated['subcategory_id'])];
    }
    foreach ($validated['field_values'] as $fieldKey => $normalized) {
        $field = $fieldMap[$fieldKey] ?? null;
        if (!$field || (string)$field['data_type'] === 'file') {
            continue;
        }
        $value = asset_format_normalized_value_for_log($field, $normalized);
        if ($value === '') {
            continue;
        }
        $details[] = ['field' => (string)$field['label'], 'value' => $value];
    }
    foreach (($validated['file_operations'] ?? []) as $fieldKey => $operation) {
        $field = $fieldMap[$fieldKey] ?? null;
        if (!$field) {
            continue;
        }
        $uploads = array_values(array_filter(array_map(static fn(array $upload): string => (string)($upload['name'] ?? ''), $operation['uploads'] ?? [])));
        if ($uploads) {
            $details[] = ['field' => (string)$field['label'], 'value' => 'Uploaded: ' . implode(', ', $uploads)];
        }
    }
    return $details;
}

function build_asset_update_log_details(array $beforeAsset, array $validated, array $fieldMap): array
{
    $details = [];
    $oldCategory = asset_category_name_by_id((int)($beforeAsset['category_id'] ?? 0));
    $newCategory = asset_category_name_by_id((int)$validated['category_id']);
    if ($oldCategory !== $newCategory) {
        $details[] = ['field' => 'Category', 'from' => $oldCategory, 'to' => $newCategory];
    }
    if (asset_subcategory_enabled((int)($validated['segment_id'] ?? 0))) {
        $oldSub = asset_subcategory_name_by_id((int)($beforeAsset['subcategory_id'] ?? 0));
        $newSub = asset_subcategory_name_by_id((int)$validated['subcategory_id']);
        if ($oldSub !== $newSub) {
            $details[] = ['field' => 'Sub-category', 'from' => $oldSub, 'to' => $newSub];
        }
    }
    foreach ($fieldMap as $fieldKey => $field) {
        if ((string)$field['data_type'] === 'file') {
            continue;
        }
        $oldValue = trim((string)($beforeAsset['values'][$fieldKey] ?? ''));
        $newValue = asset_format_normalized_value_for_log($field, $validated['field_values'][$fieldKey] ?? null);
        if ($oldValue !== $newValue) {
            $details[] = ['field' => (string)$field['label'], 'from' => $oldValue, 'to' => $newValue];
        }
    }
    foreach (($validated['file_operations'] ?? []) as $fieldKey => $operation) {
        $field = $fieldMap[$fieldKey] ?? null;
        if (!$field) {
            continue;
        }
        $existingFiles = $beforeAsset['files'][$fieldKey] ?? [];
        $existingById = [];
        foreach ($existingFiles as $fileRow) {
            $existingById[(int)$fileRow['id']] = (string)$fileRow['original_name'];
        }
        $removed = [];
        foreach (($operation['delete_ids'] ?? []) as $deleteId) {
            $deleteId = (int)$deleteId;
            if ($deleteId > 0 && isset($existingById[$deleteId])) {
                $removed[] = $existingById[$deleteId];
            }
        }
        $uploaded = array_values(array_filter(array_map(static fn(array $upload): string => (string)($upload['name'] ?? ''), $operation['uploads'] ?? [])));
        if ($removed || $uploaded) {
            $parts = [];
            if ($uploaded) {
                $parts[] = 'Added: ' . implode(', ', $uploaded);
            }
            if ($removed) {
                $parts[] = 'Removed: ' . implode(', ', $removed);
            }
            $details[] = ['field' => (string)$field['label'], 'value' => implode(' | ', $parts)];
        }
    }
    return $details;
}

function current_office_context(?array $user = null): ?array
{
    $user = $user ?: current_user();
    if (!$user) {
        return null;
    }
    $officeType = (int)($user['office_type'] ?? 0);
    if ($officeType === 2 && !empty($user['zone_id'])) {
        return ['office_type' => 2, 'office_id' => (int)$user['zone_id']];
    }
    if ($officeType === 3 && !empty($user['circle_id'])) {
        return ['office_type' => 3, 'office_id' => (int)$user['circle_id']];
    }
    if ($officeType === 4 && !empty($user['division_id'])) {
        return ['office_type' => 4, 'office_id' => (int)$user['division_id']];
    }
    if ($officeType === 5 && !empty($user['subdivision_id'])) {
        return ['office_type' => 5, 'office_id' => (int)$user['subdivision_id']];
    }
    return null;
}

function office_user_has_under_me_scope(?array $user = null): bool
{
    $user = $user ?: current_user();
    if (!$user || is_superadmin()) {
        return false;
    }
    return in_array((int)($user['office_type'] ?? 0), [2, 3, 4], true);
}

function user_can_view_subordinate_asset(array $user, array $asset): bool
{
    $officeType = (int)($user['office_type'] ?? 0);
    $assetOfficeType = (int)($asset['office_type'] ?? 0);
    $assetOfficeId = (int)($asset['office_id'] ?? 0);
    if ($assetOfficeId <= 0) {
        return false;
    }
    return match ($officeType) {
        2 => match ($assetOfficeType) {
            3 => (($circle = find_circle_with_zone($assetOfficeId)) && (int)$circle['zone_id'] === (int)($user['zone_id'] ?? 0)),
            4 => (($division = find_division_with_hierarchy($assetOfficeId)) && (int)$division['zone_id'] === (int)($user['zone_id'] ?? 0)),
            5 => (($subdivision = find_subdivision_with_hierarchy($assetOfficeId)) && (int)$subdivision['zone_id'] === (int)($user['zone_id'] ?? 0)),
            default => false,
        },
        3 => match ($assetOfficeType) {
            4 => (($division = find_division_with_hierarchy($assetOfficeId)) && (int)$division['circle_id'] === (int)($user['circle_id'] ?? 0)),
            5 => (($subdivision = find_subdivision_with_hierarchy($assetOfficeId)) && (int)$subdivision['circle_id'] === (int)($user['circle_id'] ?? 0)),
            default => false,
        },
        4 => $assetOfficeType === 5 && (($subdivision = find_subdivision_with_hierarchy($assetOfficeId)) && (int)$subdivision['division_id'] === (int)($user['division_id'] ?? 0)),
        default => false,
    };
}

function user_can_view_asset(array $user, array $asset, string $viewScope = 'my_office'): bool
{
    if (is_superadmin()) {
        return true;
    }
    $ctx = current_office_context($user);
    if (!$ctx) {
        return false;
    }
    if ((int)$asset['office_type'] === (int)$ctx['office_type'] && (int)$asset['office_id'] === (int)$ctx['office_id']) {
        return true;
    }
    if ($viewScope === 'office_under_me') {
        return user_can_view_subordinate_asset($user, $asset);
    }
    return false;
}

function asset_office_type_label(int $officeType): string
{
    return match ($officeType) {
        2 => 'Zone',
        3 => 'Circle',
        4 => 'Division',
        5 => 'Sub-division',
        default => 'Office',
    };
}

function office_name_from_type_id(int $officeType, int $officeId): string
{
    if ($officeId <= 0) {
        return '-';
    }
    $map = [
        2 => ['table' => 'zones', 'column' => 'office_name'],
        3 => ['table' => 'circles', 'column' => 'office_name'],
        4 => ['table' => 'divisions', 'column' => 'office_name'],
        5 => ['table' => 'subdivisions', 'column' => 'office_name'],
    ];
    if (!isset($map[$officeType])) {
        return '-';
    }
    $meta = $map[$officeType];
    $stmt = db()->prepare("SELECT {$meta['column']} FROM {$meta['table']} WHERE id = ? LIMIT 1");
    $stmt->execute([$officeId]);
    return (string)($stmt->fetchColumn() ?: '-');
}

function office_order_storage_dir(): string
{
    return dirname(__DIR__, 2) . '/storage/office_orders';
}

function ensure_office_order_storage_dir(): string
{
    $dir = office_order_storage_dir();
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create office order storage directory.');
    }
    return $dir;
}

function office_order_allowed_extensions(): array
{
    return [
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'bmp' => 'image/bmp',
    ];
}

function normalize_uploaded_files_array(array $fileBag): array
{
    if (!isset($fileBag['name'])) {
        return [];
    }
    if (!is_array($fileBag['name'])) {
        return [$fileBag];
    }

    $files = [];
    foreach ($fileBag['name'] as $index => $name) {
        $files[] = [
            'name' => $name,
            'type' => $fileBag['type'][$index] ?? '',
            'tmp_name' => $fileBag['tmp_name'][$index] ?? '',
            'error' => $fileBag['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $fileBag['size'][$index] ?? 0,
        ];
    }
    return $files;
}

function validate_office_order_upload(array $file): array
{
    $errors = [];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $errors[] = 'One of the selected files failed to upload.';
        return $errors;
    }
    $originalName = (string)($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowed = office_order_allowed_extensions();
    if (!isset($allowed[$extension])) {
        $errors[] = 'Unsupported file type: ' . $originalName;
    }
    return $errors;
}

function create_office_order(string $subject, array $fileBag, int $userId): void
{
    $subject = trim($subject);
    if ($subject === '') {
        throw new RuntimeException('Order subject is required.');
    }

    $files = array_values(array_filter(
        normalize_uploaded_files_array($fileBag),
        static fn(array $file): bool => !empty($file['tmp_name'])
    ));

    if (!$files) {
        throw new RuntimeException('Please choose at least one PDF or image file.');
    }

    $uploadErrors = [];
    foreach ($files as $file) {
        $uploadErrors = array_merge($uploadErrors, validate_office_order_upload($file));
    }
    if ($uploadErrors) {
        throw new RuntimeException(implode(' ', $uploadErrors));
    }

    $dir = ensure_office_order_storage_dir();
    $allowed = office_order_allowed_extensions();
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    db()->beginTransaction();
    try {
        $stmt = db()->prepare('INSERT INTO office_orders (subject, uploaded_by, uploaded_at, created_at) VALUES (?, ?, NOW(), NOW())');
        $stmt->execute([$subject, $userId]);
        $orderId = (int)db()->lastInsertId();

        $fileStmt = db()->prepare('INSERT INTO office_order_files (office_order_id, original_name, stored_name, file_ext, mime_type, created_at) VALUES (?, ?, ?, ?, ?, NOW())');

        foreach ($files as $file) {
            $originalName = (string)$file['name'];
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $storedName = sprintf('order_%d_%s.%s', $orderId, bin2hex(random_bytes(8)), $extension);
            $targetPath = $dir . '/' . $storedName;
            $detectedMime = $finfo ? (string)finfo_file($finfo, (string)$file['tmp_name']) : '';
            $mimeType = $allowed[$extension];
            if ($detectedMime !== '' && str_starts_with($mimeType, 'image/') && !str_starts_with($detectedMime, 'image/')) {
                throw new RuntimeException('Invalid image file detected: ' . $originalName);
            }
            if ($mimeType === 'application/pdf' && $detectedMime !== '' && $detectedMime !== 'application/pdf') {
                throw new RuntimeException('Invalid PDF file detected: ' . $originalName);
            }
            if (!move_uploaded_file((string)$file['tmp_name'], $targetPath)) {
                throw new RuntimeException('Failed to store uploaded file: ' . $originalName);
            }
            $fileStmt->execute([$orderId, $originalName, $storedName, $extension, $mimeType]);
        }

        db()->commit();
        add_log($userId, 'office_orders', $orderId, 'Office order uploaded.');
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    } finally {
        if ($finfo) {
            finfo_close($finfo);
        }
    }
}

function get_office_orders(): array
{
    $orders = db()->query('
        SELECT o.*, u.officer_name, u.email_id
        FROM office_orders o
        LEFT JOIN users u ON u.id = o.uploaded_by
        ORDER BY o.id DESC
    ')->fetchAll();

    if (!$orders) {
        return [];
    }

    $orderIds = array_map(static fn(array $order): int => (int)$order['id'], $orders);
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $fileStmt = db()->prepare("SELECT * FROM office_order_files WHERE office_order_id IN ({$placeholders}) ORDER BY id ASC");
    $fileStmt->execute($orderIds);
    $filesByOrder = [];
    foreach ($fileStmt->fetchAll() as $file) {
        $filesByOrder[(int)$file['office_order_id']][] = $file;
    }

    foreach ($orders as &$order) {
        $order['files'] = $filesByOrder[(int)$order['id']] ?? [];
    }
    unset($order);

    return $orders;
}

function get_office_order_file(int $fileId): ?array
{
    $stmt = db()->prepare('
        SELECT f.*, o.subject
        FROM office_order_files f
        JOIN office_orders o ON o.id = f.office_order_id
        WHERE f.id = ?
        LIMIT 1
    ');
    $stmt->execute([$fileId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function stream_office_order_file(int $fileId): void
{
    $file = get_office_order_file($fileId);
    if (!$file) {
        http_response_code(404);
        exit('File not found.');
    }

    $path = office_order_storage_dir() . '/' . $file['stored_name'];
    if (!is_file($path)) {
        http_response_code(404);
        exit('Stored file not found.');
    }

    header('Content-Type: ' . (string)$file['mime_type']);
    header('Content-Length: ' . (string)filesize($path));
    header('Content-Disposition: inline; filename="' . rawurlencode((string)$file['original_name']) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function current_office_label(?array $user = null): string
{
    $ctx = current_office_context($user);
    if (!$ctx) {
        return get_office_name_for_user($user ?: current_user());
    }
    return office_name_from_type_id($ctx['office_type'], $ctx['office_id']);
}

function asset_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '_', $value);
    return trim((string)$value, '_');
}

function asset_normalize_yes_no(mixed $value): ?int
{
    $raw = strtolower(trim((string)$value));
    if ($raw === '') {
        return null;
    }
    if (in_array($raw, ['1', 'yes', 'y', 'true'], true)) {
        return 1;
    }
    if (in_array($raw, ['0', 'no', 'n', 'false'], true)) {
        return 0;
    }
    return null;
}

function get_asset_categories(bool $includeInactive = false, ?int $segmentId = null): array
{
    $sql = 'SELECT * FROM asset_categories WHERE deleted_at IS NULL AND segment_id = ?';
    $params = [asset_normalize_segment_id($segmentId)];
    if (!$includeInactive) {
        $sql .= ' AND active_status = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_asset_category(int $id, ?int $segmentId = null): ?array
{
    $stmt = db()->prepare('SELECT * FROM asset_categories WHERE id = ? AND segment_id = ? LIMIT 1');
    $stmt->execute([$id, asset_normalize_segment_id($segmentId)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_asset_category(string $name, ?int $segmentId = null): void
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $stmt = db()->prepare('INSERT INTO asset_categories (segment_id, name, active_status, sort_order, created_at) VALUES (?, ?, 1, ?, NOW())');
    $stmt->execute([$segmentId, $name, next_sort_order_for_filters('asset_categories', ['segment_id' => $segmentId])]);
}

function update_asset_category(int $id, string $name, ?int $segmentId = null): void
{
    asset_assert_segment_match(get_asset_category($id, $segmentId), $segmentId, 'Category');
    $stmt = db()->prepare('UPDATE asset_categories SET name = ?, updated_at = NOW() WHERE id = ? AND segment_id = ?');
    $stmt->execute([$name, $id, asset_normalize_segment_id($segmentId)]);
}

function set_asset_category_status(int $id, int $status, ?int $segmentId = null): void
{
    asset_assert_segment_match(get_asset_category($id, $segmentId), $segmentId, 'Category');
    $stmt = db()->prepare('UPDATE asset_categories SET active_status = ?, updated_at = NOW() WHERE id = ? AND segment_id = ?');
    $stmt->execute([$status === 1 ? 1 : 0, $id, asset_normalize_segment_id($segmentId)]);
}

function delete_asset_category(int $id, ?int $segmentId = null): bool
{
    asset_assert_segment_match(get_asset_category($id, $segmentId), $segmentId, 'Category');
    $stmt = db()->prepare('SELECT COUNT(*) FROM assets WHERE category_id = ?');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        return false;
    }
    $stmt = db()->prepare('DELETE FROM asset_categories WHERE id = ? AND segment_id = ?');
    $stmt->execute([$id, asset_normalize_segment_id($segmentId)]);
    return true;
}

function get_asset_subcategories(?int $categoryId = null, bool $includeInactive = false, ?int $segmentId = null): array
{
    $sql = 'SELECT s.*, c.name AS category_name FROM asset_subcategories s JOIN asset_categories c ON c.id = s.category_id WHERE s.deleted_at IS NULL AND s.segment_id = ? AND c.segment_id = ?';
    $normalizedSegmentId = asset_normalize_segment_id($segmentId);
    $params = [$normalizedSegmentId, $normalizedSegmentId];
    if ($categoryId !== null && $categoryId > 0) {
        $sql .= ' AND s.category_id = ?';
        $params[] = $categoryId;
    }
    if (!$includeInactive) {
        $sql .= ' AND s.active_status = 1 AND c.active_status = 1 AND c.deleted_at IS NULL';
    }
    $sql .= ' ORDER BY c.sort_order ASC, c.name ASC, s.sort_order ASC, s.name ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_asset_subcategory(int $id, ?int $segmentId = null): ?array
{
    $stmt = db()->prepare('SELECT * FROM asset_subcategories WHERE id = ? AND segment_id = ? LIMIT 1');
    $stmt->execute([$id, asset_normalize_segment_id($segmentId)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_asset_subcategory(int $categoryId, string $name, ?int $segmentId = null): void
{
    $segmentId = asset_normalize_segment_id($segmentId);
    asset_assert_segment_match(get_asset_category($categoryId, $segmentId), $segmentId, 'Category');
    $stmt = db()->prepare('INSERT INTO asset_subcategories (segment_id, category_id, name, active_status, sort_order, created_at) VALUES (?, ?, ?, 1, ?, NOW())');
    $stmt->execute([$segmentId, $categoryId, $name, next_sort_order_for_filters('asset_subcategories', ['segment_id' => $segmentId, 'category_id' => $categoryId])]);
}

function update_asset_subcategory(int $id, int $categoryId, string $name, ?int $segmentId = null): void
{
    asset_assert_segment_match(get_asset_subcategory($id, $segmentId), $segmentId, 'Sub-category');
    asset_assert_segment_match(get_asset_category($categoryId, $segmentId), $segmentId, 'Category');
    $stmt = db()->prepare('UPDATE asset_subcategories SET category_id = ?, name = ?, updated_at = NOW() WHERE id = ? AND segment_id = ?');
    $stmt->execute([$categoryId, $name, $id, asset_normalize_segment_id($segmentId)]);
}

function set_asset_subcategory_status(int $id, int $status, ?int $segmentId = null): void
{
    asset_assert_segment_match(get_asset_subcategory($id, $segmentId), $segmentId, 'Sub-category');
    $stmt = db()->prepare('UPDATE asset_subcategories SET active_status = ?, updated_at = NOW() WHERE id = ? AND segment_id = ?');
    $stmt->execute([$status === 1 ? 1 : 0, $id, asset_normalize_segment_id($segmentId)]);
}

function delete_asset_subcategory(int $id, ?int $segmentId = null): bool
{
    asset_assert_segment_match(get_asset_subcategory($id, $segmentId), $segmentId, 'Sub-category');
    $stmt = db()->prepare('SELECT COUNT(*) FROM assets WHERE subcategory_id = ?');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        return false;
    }
    $stmt = db()->prepare('DELETE FROM asset_subcategories WHERE id = ? AND segment_id = ?');
    $stmt->execute([$id, asset_normalize_segment_id($segmentId)]);
    return true;
}

function get_asset_fields(bool $includeInactive = false, ?int $segmentId = null): array
{
    $sql = 'SELECT * FROM asset_fields WHERE deleted_at IS NULL AND segment_id = ?';
    $params = [asset_normalize_segment_id($segmentId)];
    if (!$includeInactive) {
        $sql .= ' AND active_status = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function get_asset_field(int $id, ?int $segmentId = null): ?array
{
    $stmt = db()->prepare('SELECT * FROM asset_fields WHERE id = ? AND segment_id = ? LIMIT 1');
    $stmt->execute([$id, asset_normalize_segment_id($segmentId)]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_asset_field_by_key(string $fieldKey, ?int $segmentId = null): ?array
{
    $stmt = db()->prepare('SELECT * FROM asset_fields WHERE segment_id = ? AND field_key = ? LIMIT 1');
    $stmt->execute([asset_normalize_segment_id($segmentId), $fieldKey]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_asset_field_options(int $fieldId, bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM asset_field_options WHERE field_id = ?';
    if (!$includeInactive) {
        $sql .= ' AND active_status = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    $stmt = db()->prepare($sql);
    $stmt->execute([$fieldId]);
    return $stmt->fetchAll();
}

function get_asset_field_file_rule(int $fieldId): array
{
    $defaults = asset_default_file_rule();
    $stmt = db()->prepare('SELECT * FROM asset_field_file_rules WHERE field_id = ? LIMIT 1');
    $stmt->execute([$fieldId]);
    $row = $stmt->fetch();
    if (!$row) {
        return $defaults;
    }
    return [
        'is_multiple' => (int)($row['is_multiple'] ?? 0),
        'max_files' => max(1, (int)($row['max_files'] ?? 1)),
        'max_file_size_bytes' => max(0, (int)($row['max_file_size_bytes'] ?? 0)),
        'max_total_size_bytes' => max(0, (int)($row['max_total_size_bytes'] ?? 0)),
        'allowed_extensions' => asset_extensions_string(asset_parse_extensions_string((string)($row['allowed_extensions'] ?? ''))),
    ];
}

function save_asset_field_file_rule(int $fieldId, array $rule): void
{
    $existing = db()->prepare('SELECT field_id FROM asset_field_file_rules WHERE field_id = ? LIMIT 1');
    $existing->execute([$fieldId]);
    if ($existing->fetchColumn()) {
        $stmt = db()->prepare('UPDATE asset_field_file_rules SET is_multiple = ?, max_files = ?, max_file_size_bytes = ?, max_total_size_bytes = ?, allowed_extensions = ?, updated_at = NOW() WHERE field_id = ?');
        $stmt->execute([
            $rule['is_multiple'],
            $rule['max_files'],
            $rule['max_file_size_bytes'],
            $rule['max_total_size_bytes'],
            $rule['allowed_extensions'],
            $fieldId,
        ]);
        return;
    }
    $stmt = db()->prepare('INSERT INTO asset_field_file_rules (field_id, is_multiple, max_files, max_file_size_bytes, max_total_size_bytes, allowed_extensions, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $fieldId,
        $rule['is_multiple'],
        $rule['max_files'],
        $rule['max_file_size_bytes'],
        $rule['max_total_size_bytes'],
        $rule['allowed_extensions'],
    ]);
}

function delete_asset_field_file_rule(int $fieldId): void
{
    db()->prepare('DELETE FROM asset_field_file_rules WHERE field_id = ?')->execute([$fieldId]);
}

function create_asset_field(array $payload): void
{
    $segmentId = asset_normalize_segment_id((int)($payload['segment_id'] ?? 0));
    $fieldSortOrder = (int)($payload['sort_order'] ?? 0);
    if ($fieldSortOrder <= 0) {
        $fieldSortOrder = next_sort_order_for_filters('asset_fields', ['segment_id' => $segmentId]);
    }
    db()->beginTransaction();
    try {
        if (($payload['data_type'] ?? '') === 'conditional') {
            $stmt = db()->prepare('INSERT INTO asset_fields (segment_id, field_key, label, data_type, number_format_rule, secondary_of_field_id, conditional_map_json, is_required, is_displayed, is_import_enabled, is_unique, is_filter_enabled, active_status, sort_order, created_at) VALUES (?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, 0, 1, 1, ?, NOW())');
            $stmt->execute([
                $segmentId,
                $payload['field_key'],
                $payload['label'],
                'conditional',
                json_encode($payload['conditional_map'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $fieldSortOrder,
            ]);
            $fieldId = (int)db()->lastInsertId();
            replace_asset_field_options($fieldId, $payload['options'] ?? []);

            $childStmt = db()->prepare('INSERT INTO asset_fields (segment_id, field_key, label, data_type, number_format_rule, secondary_of_field_id, conditional_map_json, is_required, is_displayed, is_import_enabled, is_unique, is_filter_enabled, active_status, sort_order, created_at) VALUES (?, ?, ?, ?, NULL, ?, NULL, ?, ?, ?, 0, 1, 1, ?, NOW())');
            $childStmt->execute([
                $segmentId,
                $payload['secondary_field_key'],
                $payload['secondary_label'],
                'dropdown',
                $fieldId,
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $fieldSortOrder + 1,
            ]);
            $childId = (int)db()->lastInsertId();
            replace_asset_field_options($childId, $payload['secondary_options'] ?? []);
        } else {
            $stmt = db()->prepare('INSERT INTO asset_fields (segment_id, field_key, label, data_type, number_format_rule, secondary_of_field_id, conditional_map_json, is_required, is_displayed, is_import_enabled, is_unique, is_filter_enabled, active_status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, 1, ?, NOW())');
            $stmt->execute([
                $segmentId,
                $payload['field_key'],
                $payload['label'],
                $payload['data_type'],
                $payload['number_format_rule'] ?: null,
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $payload['is_unique'],
                $payload['is_filter_enabled'],
                $fieldSortOrder,
            ]);
            $fieldId = (int)db()->lastInsertId();
            replace_asset_field_options($fieldId, $payload['options'] ?? []);
            if ($payload['data_type'] === 'file') {
                save_asset_field_file_rule($fieldId, $payload['file_rule'] ?? asset_default_file_rule());
            }
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function update_asset_field(int $id, array $payload): void
{
    $segmentId = asset_normalize_segment_id((int)($payload['segment_id'] ?? 0));
    $existing = get_asset_field($id, $segmentId);
    if (!$existing) {
        throw new RuntimeException('Field not found.');
    }
    $childField = get_asset_conditional_child_field($id, true, $segmentId);
    db()->beginTransaction();
    try {
        if (($payload['data_type'] ?? '') === 'conditional') {
            $stmt = db()->prepare('UPDATE asset_fields SET label = ?, data_type = ?, number_format_rule = NULL, conditional_map_json = ?, is_required = ?, is_displayed = ?, is_import_enabled = ?, is_unique = 0, is_filter_enabled = 1, sort_order = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([
                $payload['label'],
                'conditional',
                json_encode($payload['conditional_map'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $payload['sort_order'],
                $id,
            ]);
            replace_asset_field_options($id, $payload['options'] ?? []);
            delete_asset_field_file_rule($id);

            if (!$childField) {
                throw new RuntimeException('Conditional child field not found.');
            }
            $childStmt = db()->prepare('UPDATE asset_fields SET label = ?, data_type = ?, number_format_rule = NULL, is_required = ?, is_displayed = ?, is_import_enabled = ?, is_unique = 0, is_filter_enabled = 1, sort_order = ?, updated_at = NOW() WHERE id = ?');
            $childStmt->execute([
                $payload['secondary_label'],
                'dropdown',
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $payload['sort_order'] + 1,
                (int)$childField['id'],
            ]);
            replace_asset_field_options((int)$childField['id'], $payload['secondary_options'] ?? []);
            delete_asset_field_file_rule((int)$childField['id']);
        } else {
            $stmt = db()->prepare('UPDATE asset_fields SET label = ?, data_type = ?, number_format_rule = ?, conditional_map_json = NULL, is_required = ?, is_displayed = ?, is_import_enabled = ?, is_unique = ?, is_filter_enabled = ?, sort_order = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([
                $payload['label'],
                $payload['data_type'],
                $payload['number_format_rule'] ?: null,
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $payload['is_unique'],
                $payload['is_filter_enabled'],
                $payload['sort_order'],
                $id,
            ]);
            replace_asset_field_options($id, $payload['options'] ?? []);
            if ($payload['data_type'] === 'file') {
                save_asset_field_file_rule($id, $payload['file_rule'] ?? asset_default_file_rule());
            } else {
                delete_asset_field_file_rule($id);
            }
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function replace_asset_field_options(int $fieldId, array $options): void
{
    $delete = db()->prepare('DELETE FROM asset_field_options WHERE field_id = ?');
    $delete->execute([$fieldId]);
    foreach ($options as $idx => $option) {
        $value = trim((string)($option['value'] ?? ''));
        $label = trim((string)($option['label'] ?? ''));
        if ($value === '' || $label === '') {
            continue;
        }
        $stmt = db()->prepare('INSERT INTO asset_field_options (field_id, option_value, option_label, sort_order, active_status, created_at) VALUES (?, ?, ?, ?, 1, NOW())');
        $stmt->execute([$fieldId, $value, $label, ($idx + 1) * 10]);
    }
}

function set_asset_field_status(int $id, int $status, ?int $segmentId = null): void
{
    asset_assert_segment_match(get_asset_field($id, $segmentId), $segmentId, 'Field');
    $stmt = db()->prepare('UPDATE asset_fields SET active_status = ?, updated_at = NOW() WHERE id = ? AND segment_id = ?');
    $normalizedSegmentId = asset_normalize_segment_id($segmentId);
    $stmt->execute([$status === 1 ? 1 : 0, $id, $normalizedSegmentId]);
    $child = get_asset_conditional_child_field($id, true, $segmentId);
    if ($child) {
        $stmt->execute([$status === 1 ? 1 : 0, (int)$child['id'], $normalizedSegmentId]);
    }
}

function delete_asset_field(int $id, ?int $segmentId = null): bool
{
    $field = get_asset_field($id, $segmentId);
    if (!$field) {
        return false;
    }
    if (in_array($field['field_key'], asset_locked_field_keys(), true)) {
        return false;
    }
    $linkedFieldIds = [$id];
    $child = get_asset_conditional_child_field($id, true, $segmentId);
    if ($child) {
        $linkedFieldIds[] = (int)$child['id'];
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM asset_values WHERE field_id = ?');
    foreach ($linkedFieldIds as $fieldId) {
        $stmt->execute([$fieldId]);
        if ((int)$stmt->fetchColumn() > 0) {
            return false;
        }
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM asset_file_values WHERE field_id = ?');
    foreach ($linkedFieldIds as $fieldId) {
        $stmt->execute([$fieldId]);
        if ((int)$stmt->fetchColumn() > 0) {
            return false;
        }
    }
    foreach ($linkedFieldIds as $fieldId) {
        db()->prepare('DELETE FROM asset_field_options WHERE field_id = ?')->execute([$fieldId]);
        delete_asset_field_file_rule($fieldId);
        db()->prepare('DELETE FROM asset_fields WHERE id = ?')->execute([$fieldId]);
    }
    return true;
}

function next_sort_order(string $table, ?string $groupColumn = null, mixed $groupValue = null): int
{
    $sql = "SELECT COALESCE(MAX(sort_order), 0) FROM {$table}";
    $params = [];
    if ($groupColumn !== null) {
        $sql .= " WHERE {$groupColumn} = ?";
        $params[] = $groupValue;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn() + 10;
}

function asset_field_map(bool $includeInactive = false): array
{
    return asset_field_map_for_segment($includeInactive, null);
}

function asset_field_map_for_segment(bool $includeInactive = false, ?int $segmentId = null): array
{
    $map = [];
    foreach (get_asset_fields($includeInactive, $segmentId) as $field) {
        $map[$field['field_key']] = $field;
    }
    return $map;
}

function asset_category_map(bool $includeInactive = false): array
{
    return asset_category_map_for_segment($includeInactive, null);
}

function asset_category_map_for_segment(bool $includeInactive = false, ?int $segmentId = null): array
{
    $map = [];
    foreach (get_asset_categories($includeInactive, $segmentId) as $category) {
        $map[$category['id']] = $category;
    }
    return $map;
}

function asset_subcategory_map(bool $includeInactive = false): array
{
    return asset_subcategory_map_for_segment($includeInactive, null);
}

function asset_subcategory_map_for_segment(bool $includeInactive = false, ?int $segmentId = null): array
{
    $map = [];
    foreach (get_asset_subcategories(null, $includeInactive, $segmentId) as $subcategory) {
        $map[$subcategory['id']] = $subcategory;
    }
    return $map;
}

function validate_asset_field_definition(array $input, ?int $fieldId = null): array
{
    $segmentId = asset_normalize_segment_id((int)($input['segment_id'] ?? 0));
    $label = trim((string)($input['label'] ?? ''));
    $dataType = trim((string)($input['data_type'] ?? ''));
    $keyInput = trim((string)($input['field_key'] ?? ''));
    $existingField = $fieldId ? get_asset_field($fieldId, $segmentId) : null;
    $fieldKey = $keyInput !== '' ? asset_slug($keyInput) : ($existingField['field_key'] ?? asset_slug($label));
    $errors = [];
    if ($label === '') {
        $errors[] = 'Field label is required.';
    }
    if ($fieldKey === '') {
        $errors[] = 'Field key is required.';
    }
    if (!in_array($dataType, asset_supported_data_types(), true)) {
        $errors[] = 'Invalid field type.';
    }
    if ($existingField && (
        ((string)$existingField['data_type'] === 'conditional' && $dataType !== 'conditional')
        || ((string)$existingField['data_type'] !== 'conditional' && $dataType === 'conditional')
    )) {
        $errors[] = 'Changing to or from conditional type is not supported. Create a new field instead.';
    }
    $sortOrder = (int)($input['sort_order'] ?? 0);
    if ($sortOrder <= 0) {
        $sortOrder = $fieldId ? (int)($existingField['sort_order'] ?? 0) : next_sort_order_for_filters('asset_fields', ['segment_id' => $segmentId]);
    }

    $existingId = (int)((get_asset_field_by_key($fieldKey, $segmentId)['id'] ?? 0));
    if ($existingId > 0 && $existingId !== (int)$fieldId) {
        $errors[] = 'Field key already exists.';
    }

    $options = [];
    if ($dataType === 'dropdown') {
        $rawOptions = preg_split('/\r\n|\r|\n/', (string)($input['options_text'] ?? ''));
        foreach ($rawOptions as $option) {
            $option = trim($option);
            if ($option === '') {
                continue;
            }
            $options[] = ['value' => $option, 'label' => $option];
        }
        if (!$options) {
            $errors[] = 'Dropdown fields need at least one option.';
        }
    }

    $numberFormatRule = trim((string)($input['number_format_rule'] ?? ''));
    if ($dataType === 'number' && $numberFormatRule !== '' && asset_parse_number_format_rule($numberFormatRule) === null) {
        $errors[] = 'Invalid number format rule.';
    }
    if ($dataType !== 'number') {
        $numberFormatRule = '';
    }

    $secondaryLabel = trim((string)($input['secondary_label'] ?? ''));
    $conditionalMap = [];
    $secondaryOptions = [];
    $secondaryFieldKey = '';
    if ($dataType === 'conditional') {
        $rawPrimaryOptions = preg_split('/\r\n|\r|\n/', (string)($input['conditional_primary_options_text'] ?? ''));
        $primaryOptions = [];
        foreach ($rawPrimaryOptions as $option) {
            $option = trim((string)$option);
            if ($option !== '') {
                $primaryOptions[] = ['value' => $option, 'label' => $option];
            }
        }
        $options = $primaryOptions;
        if (!$options) {
            $errors[] = 'Conditional fields need at least one primary option.';
        }
        if ($secondaryLabel === '') {
            $errors[] = 'Secondary label is required for conditional fields.';
        }
        $ruleLines = preg_split('/\r\n|\r|\n/', (string)($input['conditional_rules_text'] ?? ''));
        $primaryLookup = [];
        foreach ($options as $option) {
            $primaryLookup[strtolower((string)$option['value'])] = (string)$option['value'];
        }
        foreach ($ruleLines as $line) {
            $line = trim((string)$line);
            if ($line === '') {
                continue;
            }
            $parts = preg_split('/\s*=\s*/', $line, 2);
            if (!$parts || count($parts) !== 2) {
                $errors[] = 'Conditional rules must use Primary=child1,child2 format.';
                continue;
            }
            $primaryKey = strtolower(trim((string)$parts[0]));
            if (!isset($primaryLookup[$primaryKey])) {
                $errors[] = 'Conditional rules reference an unknown primary option.';
                continue;
            }
            $children = array_values(array_filter(array_map(
                static fn(string $item): string => trim($item),
                preg_split('/\s*,\s*/', (string)$parts[1]) ?: []
            ), static fn(string $item): bool => $item !== ''));
            if (!$children) {
                $errors[] = 'Each conditional primary option needs at least one secondary option.';
                continue;
            }
            $conditionalMap[$primaryLookup[$primaryKey]] = array_values(array_unique($children));
        }
        foreach ($options as $option) {
            if (empty($conditionalMap[(string)$option['value']])) {
                $errors[] = 'Each conditional primary option needs secondary options defined.';
                break;
            }
        }
        $secondaryOptions = array_map(
            static fn(string $option): array => ['value' => $option, 'label' => $option],
            asset_conditional_union_options($conditionalMap)
        );
        $existingChild = $fieldId ? get_asset_conditional_child_field($fieldId, true, $segmentId) : null;
        $secondaryFieldKey = $existingChild['field_key'] ?? asset_slug(trim((string)($input['secondary_field_key'] ?? $secondaryLabel)));
        if ($secondaryFieldKey === '') {
            $secondaryFieldKey = $fieldKey . '_secondary';
        }
        $existingSecondaryId = (int)((get_asset_field_by_key($secondaryFieldKey, $segmentId)['id'] ?? 0));
        if ($existingSecondaryId > 0 && (!$existingChild || $existingSecondaryId !== (int)$existingChild['id'])) {
            $errors[] = 'Secondary field key already exists.';
        }
    }

    $fileRule = asset_default_file_rule();
    if ($dataType === 'file') {
        $isMultiple = (string)($input['file_is_multiple'] ?? '0') === '1' ? 1 : 0;
        $maxFiles = max(1, (int)($input['file_max_files'] ?? ($isMultiple ? 5 : 1)));
        if ($isMultiple === 0) {
            $maxFiles = 1;
        }
        $maxFileSizeBytes = asset_bytes_from_megabytes($input['file_max_size_mb'] ?? 0);
        $maxTotalSizeBytes = asset_bytes_from_megabytes($input['file_total_size_mb'] ?? 0);
        $allowedExtensions = asset_extensions_string(asset_parse_extensions_string((string)($input['file_allowed_extensions'] ?? '')));
        if ($allowedExtensions === '') {
            $errors[] = 'File fields need at least one allowed file extension.';
        }
        $fileRule = [
            'is_multiple' => $isMultiple,
            'max_files' => $maxFiles,
            'max_file_size_bytes' => $maxFileSizeBytes,
            'max_total_size_bytes' => $maxTotalSizeBytes,
            'allowed_extensions' => $allowedExtensions,
        ];
    }

    return [
        'errors' => $errors,
        'payload' => [
            'segment_id' => $segmentId,
            'field_key' => $fieldKey,
            'label' => $label,
            'data_type' => $dataType,
            'number_format_rule' => $numberFormatRule,
            'is_required' => !empty($input['is_required']) ? 1 : 0,
            'is_displayed' => !empty($input['is_displayed']) ? 1 : 0,
            'is_import_enabled' => in_array($dataType, ['file'], true) ? 0 : (!empty($input['is_import_enabled']) ? 1 : 0),
            'is_unique' => in_array($dataType, ['file', 'conditional'], true) ? 0 : (!empty($input['is_unique']) ? 1 : 0),
            'is_filter_enabled' => $dataType === 'conditional' ? 1 : (!empty($input['is_filter_enabled']) ? 1 : 0),
            'sort_order' => $sortOrder,
            'options' => $options,
            'file_rule' => $fileRule,
            'secondary_label' => $secondaryLabel,
            'secondary_field_key' => $secondaryFieldKey,
            'secondary_options' => $secondaryOptions,
            'conditional_map' => $conditionalMap,
        ],
    ];
}

function validate_asset_payload(array $input, ?int $assetId = null, array $fileBag = [], bool $skipFileFields = false): array
{
    $segmentId = asset_normalize_segment_id(isset($input['segment_id']) ? (int)$input['segment_id'] : null);
    $errors = [];
    $categoryId = (int)($input['category_id'] ?? 0);
    $subcategoryEnabled = asset_subcategory_enabled($segmentId);
    $subcategoryId = $subcategoryEnabled ? (int)($input['subcategory_id'] ?? 0) : 0;
    $category = $categoryId > 0 ? get_asset_category($categoryId, $segmentId) : null;
    $subcategory = $subcategoryEnabled && $subcategoryId > 0 ? get_asset_subcategory($subcategoryId, $segmentId) : null;
    if (!$category || (!is_superadmin() && (int)$category['active_status'] !== 1)) {
        $errors['category_id'] = 'Valid category is required.';
    }
    if ($subcategoryEnabled && (!$subcategory || (int)($subcategory['category_id'] ?? 0) !== $categoryId || (!is_superadmin() && (int)$subcategory['active_status'] !== 1))) {
        $errors['subcategory_id'] = 'Valid sub-category is required.';
    }

    $fieldMap = asset_field_map_for_segment(false, $segmentId);
    $values = [];
    $fileOperations = [];
    foreach ($fieldMap as $fieldKey => $field) {
        if ($field['data_type'] === 'file') {
            if ($skipFileFields) {
                continue;
            }
            $fileOperations[$fieldKey] = validate_asset_file_field_value($field, $assetId, $input, $fileBag, $errors);
            continue;
        }
        $raw = $input['fields'][$fieldKey] ?? null;
        $values[$fieldKey] = normalize_asset_field_value($field, $raw, $fieldMap, $errors);
    }
    foreach ($fieldMap as $fieldKey => $field) {
        if ((int)($field['is_unique'] ?? 0) !== 1 || isset($errors[$fieldKey]) || $field['data_type'] === 'file') {
            continue;
        }
        validate_asset_unique_value($field, $values[$fieldKey] ?? [], $assetId, $errors);
    }
    foreach ($fieldMap as $fieldKey => $field) {
        $parentId = (int)($field['secondary_of_field_id'] ?? 0);
        if ($parentId <= 0 || isset($errors[$fieldKey])) {
            continue;
        }
        $parentField = null;
        foreach ($fieldMap as $candidateField) {
            if ((int)$candidateField['id'] === $parentId) {
                $parentField = $candidateField;
                break;
            }
        }
        if (!$parentField) {
            continue;
        }
        $parentValue = (string)(($values[(string)$parentField['field_key']]['value_option'] ?? '') ?: ($values[(string)$parentField['field_key']]['value_text'] ?? ''));
        $childValue = (string)(($values[$fieldKey]['value_option'] ?? '') ?: ($values[$fieldKey]['value_text'] ?? ''));
        if ($childValue === '') {
            continue;
        }
        $allowedChildren = array_map('strtolower', asset_conditional_child_options($parentField, $parentValue));
        if (!$allowedChildren || !in_array(strtolower($childValue), $allowedChildren, true)) {
            $errors[$fieldKey] = ($field['label'] ?? $fieldKey) . ' has an invalid option for the selected ' . ($parentField['label'] ?? $parentField['field_key']) . '.';
        }
    }

    return [
        'errors' => $errors,
        'payload' => [
            'segment_id' => $segmentId,
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'field_values' => $values,
            'file_operations' => $fileOperations,
        ],
    ];
}

function validate_asset_file_field_value(array $field, ?int $assetId, array $input, array $fileBag, array &$errors): array
{
    $rule = get_asset_field_file_rule((int)$field['id']);
    $existingFiles = $assetId ? get_asset_field_files($assetId, (int)$field['id']) : [];
    $deleteIdsRaw = $input['delete_field_files'][$field['field_key']] ?? [];
    $deleteIdsRaw = is_array($deleteIdsRaw) ? $deleteIdsRaw : [];
    $deleteIds = array_values(array_filter(array_map('intval', $deleteIdsRaw), static fn(int $id): bool => $id > 0));
    $deleteLookup = array_flip($deleteIds);
    $remainingExisting = array_values(array_filter($existingFiles, static fn(array $file): bool => !isset($deleteLookup[(int)$file['id']])));

    $uploads = array_values(array_filter(
        asset_uploaded_files_for_field($fileBag['field_files'] ?? [], (string)$field['field_key']),
        static fn(array $file): bool => ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE || !empty($file['tmp_name'])
    ));

    if ((int)$rule['is_multiple'] !== 1 && $uploads) {
        $deleteIds = array_map(static fn(array $file): int => (int)$file['id'], $existingFiles);
        $deleteLookup = array_flip($deleteIds);
        $remainingExisting = [];
    }

    $allowedExtensions = asset_parse_extensions_string((string)$rule['allowed_extensions']);
    $mimeMap = asset_allowed_file_mime_types();
    $validUploads = [];
    $newSizeTotal = 0;
    foreach ($uploads as $upload) {
        $label = (string)($field['label'] ?? $field['field_key']);
        $errorCode = (int)($upload['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            $errors[$field['field_key']] = "{$label} file upload failed.";
            continue;
        }
        $originalName = (string)($upload['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
            $errors[$field['field_key']] = "{$label} contains an unsupported file type.";
            continue;
        }
        $size = (int)($upload['size'] ?? 0);
        if ((int)$rule['max_file_size_bytes'] > 0 && $size > (int)$rule['max_file_size_bytes']) {
            $errors[$field['field_key']] = "{$label} contains a file larger than the per-file limit.";
            continue;
        }
        $validUploads[] = [
            'name' => $originalName,
            'tmp_name' => (string)($upload['tmp_name'] ?? ''),
            'size' => $size,
            'extension' => $extension,
            'mime_type' => $mimeMap[$extension] ?? 'application/octet-stream',
        ];
        $newSizeTotal += $size;
    }

    $finalCount = count($remainingExisting) + count($validUploads);
    $existingSize = array_sum(array_map(static fn(array $file): int => (int)($file['file_size'] ?? 0), $remainingExisting));
    $finalSize = $existingSize + $newSizeTotal;
    $label = (string)($field['label'] ?? $field['field_key']);
    if ((int)$field['is_required'] === 1 && $finalCount === 0) {
        $errors[$field['field_key']] = "{$label} is required.";
    } elseif ((int)$rule['is_multiple'] === 0 && $finalCount > 1) {
        $errors[$field['field_key']] = "{$label} allows only one file.";
    } elseif ((int)$rule['max_files'] > 0 && $finalCount > (int)$rule['max_files']) {
        $errors[$field['field_key']] = "{$label} exceeds the maximum number of files.";
    } elseif ((int)$rule['max_total_size_bytes'] > 0 && $finalSize > (int)$rule['max_total_size_bytes']) {
        $errors[$field['field_key']] = "{$label} exceeds the total upload size limit.";
    }

    return [
        'delete_ids' => $deleteIds,
        'uploads' => $validUploads,
    ];
}

function normalize_asset_field_value(array $field, mixed $raw, array $fieldMap, array &$errors): array
{
    $key = $field['field_key'];
    $type = $field['data_type'];
    $label = $field['label'] ?? $key;
    $value = is_string($raw) ? trim($raw) : $raw;
    $normalized = [
        'value_text' => null,
        'value_number' => null,
        'value_date' => null,
        'value_bool' => null,
        'value_option' => null,
        'display' => '',
    ];

    $isEmpty = $value === null || $value === '';
    if ((int)$field['is_required'] === 1 && $isEmpty) {
        $errors[$key] = "{$label} is required.";
        return $normalized;
    }
    if ($isEmpty) {
        return $normalized;
    }

    if ($type === 'text') {
        $normalized['value_text'] = (string)$value;
        $normalized['display'] = (string)$value;
        return $normalized;
    }

    if ($type === 'number') {
        $valueString = asset_normalize_number_string($value);
        if ($valueString === null) {
            $errors[$key] = "{$label} must be numeric.";
            return $normalized;
        }
        $parsedRule = asset_parse_number_format_rule((string)($field['number_format_rule'] ?? ''));
        if ($parsedRule && !asset_number_matches_rule($valueString, $parsedRule)) {
            $errors[$key] = asset_number_format_error((string)$label, $parsedRule);
            return $normalized;
        }
        $normalized['value_number'] = (float)$valueString;
        $normalized['display'] = $valueString;
        return $normalized;
    }

    if ($type === 'date') {
        $timestamp = strtotime((string)$value);
        if ($timestamp === false) {
            $errors[$key] = "{$label} must be a valid date.";
            return $normalized;
        }
        $normalized['value_date'] = date('Y-m-d', $timestamp);
        $normalized['display'] = $normalized['value_date'];
        return $normalized;
    }

    if ($type === 'yes_no') {
        $bool = asset_normalize_yes_no($value);
        if ($bool === null) {
            $errors[$key] = "{$label} must be Yes or No.";
            return $normalized;
        }
        $normalized['value_bool'] = $bool;
        $normalized['display'] = $bool === 1 ? 'Yes' : 'No';
        return $normalized;
    }

    if ($type === 'dropdown' || $type === 'conditional') {
        $allowed = [];
        foreach (get_asset_field_options((int)$field['id']) as $option) {
            $allowed[strtolower($option['option_value'])] = $option['option_value'];
            $allowed[strtolower($option['option_label'])] = $option['option_value'];
        }
        $lookup = strtolower((string)$value);
        if (!isset($allowed[$lookup])) {
            $errors[$key] = "{$label} has an invalid option.";
            return $normalized;
        }
        $normalized['value_option'] = $allowed[$lookup];
        $normalized['display'] = $allowed[$lookup];
        return $normalized;
    }

    $normalized['value_text'] = (string)$value;
    $normalized['display'] = (string)$value;
    return $normalized;
}

function validate_asset_unique_value(array $field, array $normalized, ?int $assetId, array &$errors): void
{
    $fieldId = (int)($field['id'] ?? 0);
    if ($fieldId <= 0) {
        return;
    }

    $column = match ((string)$field['data_type']) {
        'number' => 'value_number',
        'date' => 'value_date',
        'yes_no' => 'value_bool',
        'dropdown' => 'value_option',
        default => 'value_text',
    };
    $value = $normalized[$column] ?? null;
    if ($value === null || $value === '') {
        return;
    }

    $sql = "SELECT a.id
        FROM asset_values v
        JOIN assets a ON a.id = v.asset_id
        WHERE v.field_id = ?
          AND a.deleted_at IS NULL
          AND a.active_status = 1
          AND v.{$column} = ?";
    $params = [$fieldId, $value];
    if (($assetId ?? 0) > 0) {
        $sql .= ' AND a.id <> ?';
        $params[] = $assetId;
    }
    $sql .= ' LIMIT 1';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    if ($stmt->fetchColumn()) {
        $errors[$field['field_key']] = ($field['label'] ?? $field['field_key']) . ' already exists.';
    }
}

function asset_normalize_unique_compare_value(array $field, mixed $raw): ?string
{
    $type = (string)($field['data_type'] ?? 'text');
    $value = is_string($raw) ? trim($raw) : $raw;
    if ($value === null || $value === '') {
        return null;
    }

    return match ($type) {
        'number' => is_numeric($value) ? rtrim(rtrim(number_format((float)$value, 4, '.', ''), '0'), '.') : null,
        'date' => (($timestamp = strtotime((string)$value)) !== false) ? date('Y-m-d', $timestamp) : null,
        'yes_no' => (($bool = asset_normalize_yes_no($value)) !== null) ? (string)$bool : null,
        'dropdown' => (function () use ($field, $value): ?string {
            $allowed = [];
            foreach (get_asset_field_options((int)$field['id']) as $option) {
                $allowed[strtolower((string)$option['option_value'])] = (string)$option['option_value'];
                $allowed[strtolower((string)$option['option_label'])] = (string)$option['option_value'];
            }
            return $allowed[strtolower(trim((string)$value))] ?? null;
        })(),
        default => (string)$value,
    };
}

function asset_unique_existing_values_map(?int $segmentId = null): array
{
    $map = [];
    $segmentId = asset_normalize_segment_id($segmentId);
    foreach (get_asset_fields(false, $segmentId) as $field) {
        if ((int)($field['is_unique'] ?? 0) !== 1 || (int)($field['is_import_enabled'] ?? 0) !== 1 || (string)($field['data_type'] ?? '') === 'file') {
            continue;
        }
        $column = match ((string)$field['data_type']) {
            'number' => 'value_number',
            'date' => 'value_date',
            'yes_no' => 'value_bool',
            'dropdown' => 'value_option',
            default => 'value_text',
        };
        $stmt = db()->prepare("SELECT v.{$column} AS field_value
            FROM asset_values v
            JOIN assets a ON a.id = v.asset_id
            WHERE v.field_id = ?
              AND a.segment_id = ?
              AND a.deleted_at IS NULL
              AND a.active_status = 1
              AND v.{$column} IS NOT NULL");
        $stmt->execute([(int)$field['id'], $segmentId]);
        $values = [];
        foreach ($stmt->fetchAll() as $row) {
            $normalized = asset_normalize_unique_compare_value($field, $row['field_value'] ?? null);
            if ($normalized === null || $normalized === '') {
                continue;
            }
            $values[] = $normalized;
        }
        $map[(string)$field['field_key']] = array_values(array_unique($values));
    }
    return $map;
}

function validate_asset_unique_values_within_batch(array $fieldMap, array $fieldValues, array &$seenValues, array &$errors): void
{
    foreach ($fieldMap as $fieldKey => $field) {
        if ((int)($field['is_unique'] ?? 0) !== 1 || (string)($field['data_type'] ?? '') === 'file') {
            continue;
        }
        $normalized = match ((string)$field['data_type']) {
            'number' => isset($fieldValues[$fieldKey]['value_number']) && $fieldValues[$fieldKey]['value_number'] !== null
                ? asset_normalize_unique_compare_value($field, $fieldValues[$fieldKey]['value_number'])
                : null,
            'date' => $fieldValues[$fieldKey]['value_date'] ?? null,
            'yes_no' => isset($fieldValues[$fieldKey]['value_bool']) && $fieldValues[$fieldKey]['value_bool'] !== null
                ? (string)$fieldValues[$fieldKey]['value_bool']
                : null,
            'dropdown' => $fieldValues[$fieldKey]['value_option'] ?? null,
            default => $fieldValues[$fieldKey]['value_text'] ?? null,
        };
        if ($normalized === null || $normalized === '') {
            continue;
        }
        if (!isset($seenValues[$fieldKey])) {
            $seenValues[$fieldKey] = [];
        }
        if (isset($seenValues[$fieldKey][$normalized])) {
            $errors[$fieldKey] = ($field['label'] ?? $fieldKey) . ' already exists.';
            continue;
        }
        $seenValues[$fieldKey][$normalized] = true;
    }
}

function create_asset(array $validated, array $user): int
{
    $fileCleanup = ['new_paths' => [], 'delete_paths' => []];
    $fieldMap = asset_field_map_for_segment(true, $segmentId);
    db()->beginTransaction();
    try {
        $assetId = persist_asset_record($validated, $user, $fileCleanup);
        db()->commit();
        finalize_asset_file_changes($fileCleanup, true);
        add_log((int)$user['id'], 'assets', $assetId, 'Asset created.');
        add_asset_activity_log(
            $assetId,
            (int)$user['id'],
            'created',
            'Asset created.',
            build_asset_create_log_details($validated, $fieldMap)
        );
        return $assetId;
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        finalize_asset_file_changes($fileCleanup, false);
        throw $e;
    }
}

function persist_asset_record(array $validated, array $user, array &$fileCleanup = []): int
{
    $ctx = current_office_context($user);
    if (!$ctx) {
        throw new RuntimeException('Office context not found.');
    }
    $stmt = db()->prepare('INSERT INTO assets (segment_id, asset_number, category_id, subcategory_id, office_type, office_id, active_status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())');
    $stmt->execute([
        asset_normalize_segment_id((int)($validated['segment_id'] ?? 0)),
        'PENDING',
        $validated['category_id'],
        $validated['subcategory_id'] > 0 ? $validated['subcategory_id'] : null,
        $ctx['office_type'],
        $ctx['office_id'],
        (int)$user['id'],
    ]);
    $assetId = (int)db()->lastInsertId();
    $assetNumber = sprintf('AST-%s-%06d', date('Y'), $assetId);
    db()->prepare('UPDATE assets SET asset_number = ? WHERE id = ?')->execute([$assetNumber, $assetId]);
    save_asset_values($assetId, $validated['field_values']);
    sync_asset_file_values($assetId, $validated['file_operations'] ?? [], $fileCleanup);
    return $assetId;
}

function update_asset(int $assetId, array $validated, array $user): void
{
    $asset = get_asset($assetId, true);
    if (!$asset) {
        throw new RuntimeException('Asset not found.');
    }
    $fieldMap = asset_field_map_for_segment(true, $segmentId);
    $logDetails = build_asset_update_log_details($asset, $validated, $fieldMap);
    $fileCleanup = ['new_paths' => [], 'delete_paths' => []];
    db()->beginTransaction();
    try {
        $stmt = db()->prepare('UPDATE assets SET category_id = ?, subcategory_id = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([
            $validated['category_id'],
            $validated['subcategory_id'] > 0 ? $validated['subcategory_id'] : null,
            (int)$user['id'],
            $assetId,
        ]);
        save_asset_values($assetId, $validated['field_values']);
        sync_asset_file_values($assetId, $validated['file_operations'] ?? [], $fileCleanup);
        db()->commit();
        finalize_asset_file_changes($fileCleanup, true);
        add_log((int)$user['id'], 'assets', $assetId, 'Asset updated.');
        add_asset_activity_log(
            $assetId,
            (int)$user['id'],
            'updated',
            $logDetails ? 'Asset updated.' : 'Asset saved without data changes.',
            $logDetails
        );
    } catch (Throwable $e) {
        db()->rollBack();
        finalize_asset_file_changes($fileCleanup, false);
        throw $e;
    }
}

function upload_asset_files_for_field(int $assetId, string $fieldKey, array $user, array $fileBag): void
{
    $asset = get_asset($assetId, true);
    if (!$asset) {
        throw new RuntimeException('Asset not found.');
    }
    if (!user_can_manage_asset($user, $asset)) {
        throw new RuntimeException('Not allowed.');
    }

    $field = asset_field_map(true)[$fieldKey] ?? null;
    if (!$field || (string)($field['data_type'] ?? '') !== 'file' || (int)($field['active_status'] ?? 0) !== 1) {
        throw new RuntimeException('Invalid file field.');
    }

    $errors = [];
    $operation = validate_asset_file_field_value($field, $assetId, [], $fileBag, $errors);
    if ($errors) {
        throw new RuntimeException(implode(' ', array_values($errors)));
    }
    if (empty($operation['uploads'])) {
        throw new RuntimeException('Please choose file(s) to upload.');
    }

    $cleanup = ['new_paths' => [], 'delete_paths' => []];
    db()->beginTransaction();
    try {
        sync_asset_file_values($assetId, [$fieldKey => $operation], $cleanup);
        db()->prepare('UPDATE assets SET updated_by = ?, updated_at = NOW() WHERE id = ?')->execute([(int)$user['id'], $assetId]);
        db()->commit();
        finalize_asset_file_changes($cleanup, true);
        add_log((int)$user['id'], 'assets', $assetId, 'Asset file uploaded.');
        $uploadedNames = array_values(array_filter(array_map(
            static fn(array $upload): string => (string)($upload['name'] ?? ''),
            $operation['uploads'] ?? []
        )));
        add_asset_activity_log(
            $assetId,
            (int)$user['id'],
            'file_upload',
            'Asset file uploaded.',
            [['field' => (string)$field['label'], 'value' => 'Uploaded: ' . implode(', ', $uploadedNames)]]
        );
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        finalize_asset_file_changes($cleanup, false);
        throw $e;
    }
}

function save_asset_values(int $assetId, array $fieldValues): void
{
    foreach ($fieldValues as $fieldKey => $normalized) {
        $field = asset_field_map(true)[$fieldKey] ?? null;
        if (!$field) {
            continue;
        }
        $exists = db()->prepare('SELECT id FROM asset_values WHERE asset_id = ? AND field_id = ? LIMIT 1');
        $exists->execute([$assetId, (int)$field['id']]);
        $existingId = (int)($exists->fetchColumn() ?: 0);
        if ($existingId > 0) {
            $stmt = db()->prepare('UPDATE asset_values SET value_text = ?, value_number = ?, value_date = ?, value_bool = ?, value_option = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([
                $normalized['value_text'],
                $normalized['value_number'],
                $normalized['value_date'],
                $normalized['value_bool'],
                $normalized['value_option'],
                $existingId,
            ]);
            continue;
        }
        $stmt = db()->prepare('INSERT INTO asset_values (asset_id, field_id, value_text, value_number, value_date, value_bool, value_option, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $assetId,
            (int)$field['id'],
            $normalized['value_text'],
            $normalized['value_number'],
            $normalized['value_date'],
            $normalized['value_bool'],
            $normalized['value_option'],
        ]);
    }
}

function get_asset(int $assetId, bool $includeDeleted = false): ?array
{
    $sql = 'SELECT a.*, c.name AS category_name, s.name AS subcategory_name, creator.email_id AS created_by_email, editor.email_id AS updated_by_email
            FROM assets a
            JOIN asset_categories c ON c.id = a.category_id AND c.segment_id = a.segment_id
            LEFT JOIN asset_subcategories s ON s.id = a.subcategory_id AND s.segment_id = a.segment_id
            JOIN users creator ON creator.id = a.created_by
            LEFT JOIN users editor ON editor.id = a.updated_by
            WHERE a.id = ?';
    if (!$includeDeleted) {
        $sql .= ' AND a.deleted_at IS NULL AND a.active_status = 1';
    }
    $stmt = db()->prepare($sql);
    $stmt->execute([$assetId]);
    $asset = $stmt->fetch();
    if (!$asset) {
        return null;
    }
    $asset['values'] = get_asset_values($assetId);
    $asset['files'] = get_asset_files($assetId);
    return $asset;
}

function get_asset_values(int $assetId): array
{
    $stmt = db()->prepare('SELECT v.*, f.field_key, f.label, f.data_type FROM asset_values v JOIN asset_fields f ON f.id = v.field_id WHERE v.asset_id = ? ORDER BY f.sort_order ASC, f.id ASC');
    $stmt->execute([$assetId]);
    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[$row['field_key']] = asset_display_value($row);
    }
    return $map;
}

function asset_display_value(array $row): string
{
    return match ($row['data_type']) {
        'number' => $row['value_number'] !== null ? rtrim(rtrim((string)$row['value_number'], '0'), '.') : '',
        'date' => (string)($row['value_date'] ?? ''),
        'yes_no' => $row['value_bool'] === null ? '' : ((int)$row['value_bool'] === 1 ? 'Yes' : 'No'),
        'dropdown', 'conditional' => (string)($row['value_option'] ?? ''),
        default => (string)($row['value_text'] ?? ''),
    };
}

function user_can_manage_asset(array $user, array $asset): bool
{
    if (is_superadmin()) {
        return true;
    }
    return user_can_view_asset($user, $asset, 'my_office');
}

function soft_delete_assets(array $assetIds, array $user): int
{
    $deleted = 0;
    foreach ($assetIds as $assetId) {
        $assetId = (int)$assetId;
        if ($assetId <= 0) {
            continue;
        }
        $asset = get_asset($assetId, true);
        if (!$asset || !user_can_manage_asset($user, $asset) || !empty($asset['deleted_at'])) {
            continue;
        }
        db()->prepare('UPDATE assets SET active_status = 0, deleted_at = NOW(), updated_by = ?, updated_at = NOW() WHERE id = ?')->execute([(int)$user['id'], $assetId]);
        add_log((int)$user['id'], 'assets', $assetId, 'Asset soft deleted.');
        $deleted++;
    }
    return $deleted;
}

function asset_filter_value(array $asset, string $fieldKey): string
{
    return trim((string)($asset['values'][$fieldKey] ?? ''));
}

function asset_file_extensions_for_asset(array $asset, string $fieldKey): array
{
    $extensions = [];
    foreach (($asset['files'][$fieldKey] ?? []) as $fileRow) {
        $ext = strtolower(trim((string)($fileRow['file_ext'] ?? '')));
        if ($ext !== '') {
            $extensions[$ext] = $ext;
        }
    }
    ksort($extensions, SORT_NATURAL | SORT_FLAG_CASE);
    return array_values($extensions);
}

function asset_date_in_range(string $value, string $from, string $to): bool
{
    $value = trim($value);
    if ($value === '') {
        return false;
    }
    if ($from !== '' && $value < $from) {
        return false;
    }
    if ($to !== '' && $value > $to) {
        return false;
    }
    return true;
}

function asset_matches_hierarchy_filters(array $asset, array $filters): bool
{
    $zoneId = (int)($filters['zone_id'] ?? 0);
    $circleId = (int)($filters['circle_id'] ?? 0);
    $divisionId = (int)($filters['division_id'] ?? 0);
    $subdivisionId = (int)($filters['subdivision_id'] ?? 0);
    if ($zoneId <= 0 && $circleId <= 0 && $divisionId <= 0 && $subdivisionId <= 0) {
        return true;
    }
    $assetOfficeType = (int)($asset['office_type'] ?? 0);
    $assetOfficeId = (int)($asset['office_id'] ?? 0);
    $resolvedZoneId = 0;
    $resolvedCircleId = 0;
    $resolvedDivisionId = 0;
    $resolvedSubdivisionId = 0;
    if ($assetOfficeType === 2) {
        $resolvedZoneId = $assetOfficeId;
    } elseif ($assetOfficeType === 3) {
        $circle = find_circle_with_zone($assetOfficeId);
        $resolvedZoneId = (int)($circle['zone_id'] ?? 0);
        $resolvedCircleId = $assetOfficeId;
    } elseif ($assetOfficeType === 4) {
        $division = find_division_with_hierarchy($assetOfficeId);
        $resolvedZoneId = (int)($division['zone_id'] ?? 0);
        $resolvedCircleId = (int)($division['circle_id'] ?? 0);
        $resolvedDivisionId = $assetOfficeId;
    } elseif ($assetOfficeType === 5) {
        $subdivision = find_subdivision_with_hierarchy($assetOfficeId);
        $resolvedZoneId = (int)($subdivision['zone_id'] ?? 0);
        $resolvedCircleId = (int)($subdivision['circle_id'] ?? 0);
        $resolvedDivisionId = (int)($subdivision['division_id'] ?? 0);
        $resolvedSubdivisionId = $assetOfficeId;
    }
    if ($zoneId > 0 && $resolvedZoneId !== $zoneId) {
        return false;
    }
    if ($circleId > 0 && $resolvedCircleId !== $circleId) {
        return false;
    }
    if ($divisionId > 0 && $resolvedDivisionId !== $divisionId) {
        return false;
    }
    if ($subdivisionId > 0 && $resolvedSubdivisionId !== $subdivisionId) {
        return false;
    }
    return true;
}

function asset_matches_dynamic_filters(array $asset, array $filters, array $fieldMap): bool
{
    foreach ($fieldMap as $fieldKey => $field) {
        if ((int)($field['active_status'] ?? 0) !== 1) {
            continue;
        }
        $filterKey = 'field_filter_' . $fieldKey;
        $value = trim((string)($filters[$filterKey] ?? ''));
        $from = trim((string)($filters[$filterKey . '_from'] ?? ''));
        $to = trim((string)($filters[$filterKey . '_to'] ?? ''));
        if ($value === '' && $from === '' && $to === '') {
            continue;
        }
        $fieldType = (string)($field['data_type'] ?? 'text');
        if ($fieldType === 'date') {
            if (!asset_date_in_range(asset_filter_value($asset, $fieldKey), $from, $to)) {
                return false;
            }
            continue;
        }
        if ($fieldType === 'file') {
            $extensions = asset_file_extensions_for_asset($asset, $fieldKey);
            if ($value === '__no_file__') {
                if ($extensions) {
                    return false;
                }
            } elseif ($value !== '' && !in_array(strtolower($value), $extensions, true)) {
                return false;
            }
            continue;
        }
        if ($fieldType === 'conditional') {
            if ($value !== '' && strcasecmp(asset_filter_value($asset, $fieldKey), $value) !== 0) {
                return false;
            }
            continue;
        }
        if (asset_is_conditional_secondary($field)) {
            if ($value !== '' && strcasecmp(asset_filter_value($asset, $fieldKey), $value) !== 0) {
                return false;
            }
            continue;
        }
        if ($value !== '' && strcasecmp(asset_filter_value($asset, $fieldKey), $value) !== 0) {
            return false;
        }
    }
    return true;
}

function asset_filter_visible_fields(array $fields, array $assets, ?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $visible = [];
    foreach ($fields as $field) {
        if ((int)($field['active_status'] ?? 0) !== 1) {
            continue;
        }
        if (asset_is_conditional_secondary($field)) {
            $parentField = get_asset_field((int)$field['secondary_of_field_id'], $segmentId);
            if ($parentField && (int)($parentField['is_filter_enabled'] ?? 0) === 1) {
                $visible[$field['field_key']] = true;
            }
            continue;
        }
        if ((int)($field['is_filter_enabled'] ?? 0) === 1) {
            $visible[$field['field_key']] = true;
        }
    }
    return $visible;
}

function build_asset_filter_catalog(array $assets, array $fields, ?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $catalog = [
        'categories' => [],
        'subcategories' => [],
        'zones' => [],
        'circles' => [],
        'divisions' => [],
        'subdivisions' => [],
        'fields' => [],
    ];
    foreach (get_asset_categories(false, $segmentId) as $category) {
        $catalog['categories'][(int)$category['id']] = ['id' => (int)$category['id'], 'name' => (string)$category['name']];
    }
    foreach (get_asset_subcategories(null, false, $segmentId) as $subcategory) {
        $catalog['subcategories'][(int)$subcategory['id']] = [
            'id' => (int)$subcategory['id'],
            'category_id' => (int)$subcategory['category_id'],
            'name' => (string)$subcategory['name'],
        ];
    }
    $visibleFields = asset_filter_visible_fields($fields, $assets, $segmentId);
    foreach ($assets as $asset) {
        $officeType = (int)($asset['office_type'] ?? 0);
        $officeId = (int)($asset['office_id'] ?? 0);
        if ($officeType === 2) {
            $catalog['zones'][$officeId] = ['id' => $officeId, 'name' => (string)$asset['office_name']];
        } elseif ($officeType === 3) {
            $circle = find_circle_with_zone($officeId);
            if ($circle) {
                $catalog['zones'][(int)$circle['zone_id']] = ['id' => (int)$circle['zone_id'], 'name' => (string)$circle['zone_name']];
                $catalog['circles'][$officeId] = ['id' => $officeId, 'zone_id' => (int)$circle['zone_id'], 'name' => (string)$circle['office_name']];
            }
        } elseif ($officeType === 4) {
            $division = find_division_with_hierarchy($officeId);
            if ($division) {
                $catalog['zones'][(int)$division['zone_id']] = ['id' => (int)$division['zone_id'], 'name' => (string)$division['zone_name']];
                $catalog['circles'][(int)$division['circle_id']] = ['id' => (int)$division['circle_id'], 'zone_id' => (int)$division['zone_id'], 'name' => (string)$division['circle_name']];
                $catalog['divisions'][$officeId] = ['id' => $officeId, 'zone_id' => (int)$division['zone_id'], 'circle_id' => (int)$division['circle_id'], 'name' => (string)$division['office_name']];
            }
        } elseif ($officeType === 5) {
            $subdivision = find_subdivision_with_hierarchy($officeId);
            if ($subdivision) {
                $catalog['zones'][(int)$subdivision['zone_id']] = ['id' => (int)$subdivision['zone_id'], 'name' => (string)$subdivision['zone_name']];
                $catalog['circles'][(int)$subdivision['circle_id']] = ['id' => (int)$subdivision['circle_id'], 'zone_id' => (int)$subdivision['zone_id'], 'name' => (string)$subdivision['circle_name']];
                $catalog['divisions'][(int)$subdivision['division_id']] = ['id' => (int)$subdivision['division_id'], 'zone_id' => (int)$subdivision['zone_id'], 'circle_id' => (int)$subdivision['circle_id'], 'name' => (string)$subdivision['division_name']];
                $catalog['subdivisions'][$officeId] = ['id' => $officeId, 'zone_id' => (int)$subdivision['zone_id'], 'circle_id' => (int)$subdivision['circle_id'], 'division_id' => (int)$subdivision['division_id'], 'name' => (string)$subdivision['office_name']];
            }
        }
        foreach ($fields as $field) {
            $fieldKey = (string)$field['field_key'];
            if (empty($visibleFields[$fieldKey]) || asset_is_conditional_secondary($field) || (int)($field['active_status'] ?? 0) !== 1) {
                continue;
            }
            $fieldType = (string)($field['data_type'] ?? 'text');
            $catalog['fields'][$fieldKey] ??= [
                'field_key' => $fieldKey,
                'label' => (string)$field['label'],
                'data_type' => $fieldType,
                'secondary_of_field_id' => (int)($field['secondary_of_field_id'] ?? 0),
                'options' => [],
                'secondary_options_map' => [],
            ];
            if ($fieldType === 'file') {
                $extensions = asset_file_extensions_for_asset($asset, $fieldKey);
                if (!$extensions) {
                    $catalog['fields'][$fieldKey]['options']['__no_file__'] = 'No file';
                }
                foreach ($extensions as $ext) {
                    $catalog['fields'][$fieldKey]['options'][$ext] = $ext;
                }
            } elseif ($fieldType === 'conditional') {
                foreach (asset_decode_conditional_map($field) as $primary => $children) {
                    $catalog['fields'][$fieldKey]['options'][$primary] = $primary;
                    $catalog['fields'][$fieldKey]['secondary_options_map'][$primary] = $children;
                }
            } elseif (in_array($fieldType, ['dropdown', 'yes_no'], true)) {
                foreach (get_asset_field_options((int)$field['id']) as $option) {
                    $catalog['fields'][$fieldKey]['options'][(string)$option['option_value']] = (string)$option['option_label'];
                }
                if ($fieldType === 'yes_no' && !$catalog['fields'][$fieldKey]['options']) {
                    $catalog['fields'][$fieldKey]['options'] = ['Yes' => 'Yes', 'No' => 'No'];
                }
            } elseif ($fieldType !== 'date') {
                $value = asset_filter_value($asset, $fieldKey);
                if ($value !== '') {
                    $catalog['fields'][$fieldKey]['options'][$value] = $value;
                }
            }
        }
    }
    foreach (['zones', 'circles', 'divisions', 'subdivisions'] as $officeKey) {
        uasort($catalog[$officeKey], static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
    }
    foreach ($catalog['fields'] as &$fieldMeta) {
        if (!empty($fieldMeta['options'])) {
            natcasesort($fieldMeta['options']);
        }
    }
    unset($fieldMeta);
    return $catalog;
}

function get_assets(array $filters = [], ?array $user = null, bool $includeDeleted = false): array
{
    $user = $user ?: current_user();
    $viewScope = (string)($filters['office_view_scope'] ?? 'my_office');
    $segmentId = asset_normalize_segment_id(isset($filters['segment_id']) ? (int)$filters['segment_id'] : null);
    $sql = 'SELECT a.*, c.name AS category_name, s.name AS subcategory_name, creator.email_id AS created_by_email, editor.email_id AS updated_by_email
            FROM assets a
            JOIN asset_categories c ON c.id = a.category_id AND c.segment_id = a.segment_id
            LEFT JOIN asset_subcategories s ON s.id = a.subcategory_id AND s.segment_id = a.segment_id
            JOIN users creator ON creator.id = a.created_by
            LEFT JOIN users editor ON editor.id = a.updated_by
            WHERE a.segment_id = ?';
    $params = [$segmentId];
    if (!$includeDeleted) {
        $sql .= ' AND a.deleted_at IS NULL AND a.active_status = 1';
    }

    if (!is_superadmin()) {
        $ctx = current_office_context($user);
        if ($ctx && $viewScope !== 'office_under_me') {
            $sql .= ' AND a.office_type = ? AND a.office_id = ?';
            $params[] = $ctx['office_type'];
            $params[] = $ctx['office_id'];
        }
    } else {
        $officeType = (int)($filters['office_type'] ?? 0);
        $officeId = (int)($filters['office_id'] ?? 0);
        if ($officeType > 0) {
            $sql .= ' AND a.office_type = ?';
            $params[] = $officeType;
        }
        if ($officeId > 0) {
            $sql .= ' AND a.office_id = ?';
            $params[] = $officeId;
        }
    }

    $categoryId = (int)($filters['category_id'] ?? 0);
    if ($categoryId > 0) {
        $sql .= ' AND a.category_id = ?';
        $params[] = $categoryId;
    }
    $subcategoryId = asset_subcategory_enabled($segmentId) ? (int)($filters['subcategory_id'] ?? 0) : 0;
    if ($subcategoryId > 0) {
        $sql .= ' AND a.subcategory_id = ?';
        $params[] = $subcategoryId;
    }

    $condition = trim((string)($filters['condition_value'] ?? ''));
    if ($condition !== '') {
        $fieldMap = asset_field_map_for_segment(true, $segmentId);
        if (isset($fieldMap['condition_value'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM asset_values v WHERE v.asset_id = a.id AND v.field_id = ? AND v.value_option = ?)';
            $params[] = (int)$fieldMap['condition_value']['id'];
            $params[] = $condition;
        }
    }

    $declaredStatus = trim((string)($filters['declared_status'] ?? ''));
    if ($declaredStatus === 'declared') {
        $sql .= ' AND EXISTS (SELECT 1 FROM office_asset_declarations d WHERE d.segment_id = ? AND d.office_type = a.office_type AND d.office_id = a.office_id AND d.declared_status = 1)';
        $params[] = $segmentId;
    } elseif ($declaredStatus === 'undeclared') {
        $sql .= ' AND NOT EXISTS (SELECT 1 FROM office_asset_declarations d WHERE d.segment_id = ? AND d.office_type = a.office_type AND d.office_id = a.office_id AND d.declared_status = 1)';
        $params[] = $segmentId;
    }

    $sql .= ' ORDER BY c.sort_order ASC, c.name ASC, a.id DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $assetIds = array_map(fn($row) => (int)$row['id'], $rows);
    $valuesByAsset = get_asset_values_for_assets($assetIds);
    $filesByAsset = get_asset_files_for_assets($assetIds);
    foreach ($rows as &$row) {
        $row['values'] = $valuesByAsset[(int)$row['id']] ?? [];
        $row['files'] = $filesByAsset[(int)$row['id']] ?? [];
        $row['office_name'] = office_name_from_type_id((int)$row['office_type'], (int)$row['office_id']);
        $row['office_type_label'] = asset_office_type_label((int)$row['office_type']);
    }
    unset($row);
    if (!is_superadmin() && $viewScope === 'office_under_me') {
        $rows = array_values(array_filter($rows, static fn(array $row): bool => user_can_view_subordinate_asset($user, $row)));
    }
    $fieldMap = asset_field_map_for_segment(true, (int)($asset['segment_id'] ?? 0));
    $rows = array_values(array_filter($rows, static function (array $row) use ($filters, $fieldMap): bool {
        if (!asset_matches_hierarchy_filters($row, $filters)) {
            return false;
        }
        if (!asset_matches_dynamic_filters($row, $filters, $fieldMap)) {
            return false;
        }
        return true;
    }));
    $sortColumn = trim((string)($filters['sort_col'] ?? ''));
    $sortDirection = strtolower(trim((string)($filters['sort_dir'] ?? 'asc')));
    if ($sortDirection !== 'desc') {
        $sortDirection = 'asc';
    }
    if ($sortColumn === '' && !is_superadmin() && $viewScope === 'office_under_me') {
        $sortColumn = 'office_name';
        $sortDirection = 'asc';
    }
    if ($sortColumn !== '') {
        $rows = sort_asset_rows($rows, $sortColumn, $sortDirection);
    }
    return $rows;
}

function sort_asset_rows(array $rows, string $sortColumn, string $sortDirection = 'asc'): array
{
    usort($rows, static function (array $left, array $right) use ($sortColumn, $sortDirection): int {
        $leftValue = asset_sort_value($left, $sortColumn);
        $rightValue = asset_sort_value($right, $sortColumn);
        if (is_numeric($leftValue) && is_numeric($rightValue)) {
            $comparison = (float)$leftValue <=> (float)$rightValue;
        } else {
            $comparison = strnatcasecmp((string)$leftValue, (string)$rightValue);
        }
        if ($comparison === 0) {
            $comparison = ((int)$left['id']) <=> ((int)$right['id']);
        }
        return $sortDirection === 'desc' ? -$comparison : $comparison;
    });
    return $rows;
}

function asset_sort_value(array $asset, string $sortColumn): string
{
    return match ($sortColumn) {
        '__sl' => (string)($asset['id'] ?? ''),
        'asset_number' => (string)($asset['asset_number'] ?? ''),
        'office_name' => (string)($asset['office_name'] ?? ''),
        'subcategory_name' => (string)($asset['subcategory_name'] ?? ''),
        'data_provider' => strtok((string)($asset['created_by_email'] ?? ''), '@') ?: '',
        default => (string)($asset['values'][$sortColumn] ?? ''),
    };
}

function get_asset_values_for_assets(array $assetIds): array
{
    if (!$assetIds) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($assetIds), '?'));
    $stmt = db()->prepare("SELECT v.*, f.field_key, f.data_type FROM asset_values v JOIN asset_fields f ON f.id = v.field_id WHERE v.asset_id IN ({$placeholders}) ORDER BY f.sort_order ASC, f.id ASC");
    $stmt->execute($assetIds);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $assetId = (int)$row['asset_id'];
        $map[$assetId][$row['field_key']] = asset_display_value($row);
    }
    return $map;
}

function get_asset_field_files(int $assetId, int $fieldId): array
{
    $stmt = db()->prepare('SELECT * FROM asset_file_values WHERE asset_id = ? AND field_id = ? ORDER BY id ASC');
    $stmt->execute([$assetId, $fieldId]);
    return $stmt->fetchAll();
}

function get_asset_files(int $assetId): array
{
    $stmt = db()->prepare('SELECT f.*, af.field_key FROM asset_file_values f JOIN asset_fields af ON af.id = f.field_id WHERE f.asset_id = ? ORDER BY af.sort_order ASC, f.id ASC');
    $stmt->execute([$assetId]);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(string)$row['field_key']][] = $row;
    }
    return $map;
}

function get_asset_files_for_assets(array $assetIds): array
{
    if (!$assetIds) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($assetIds), '?'));
    $stmt = db()->prepare("SELECT f.*, af.field_key FROM asset_file_values f JOIN asset_fields af ON af.id = f.field_id WHERE f.asset_id IN ({$placeholders}) ORDER BY af.sort_order ASC, f.id ASC");
    $stmt->execute($assetIds);
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(int)$row['asset_id']][(string)$row['field_key']][] = $row;
    }
    return $map;
}

function sync_asset_file_values(int $assetId, array $fileOperations, array &$cleanup = []): void
{
    $cleanup['new_paths'] ??= [];
    $cleanup['delete_paths'] ??= [];
    if (!$fileOperations) {
        return;
    }
    $dir = ensure_asset_file_storage_dir();
    $asset = get_asset($assetId, true);
    $segmentId = (int)($asset['segment_id'] ?? asset_default_segment_id());
    $fieldMap = asset_field_map_for_segment(true, $segmentId);
    foreach ($fileOperations as $fieldKey => $operation) {
        $field = $fieldMap[$fieldKey] ?? null;
        if (!$field || $field['data_type'] !== 'file') {
            continue;
        }

        $deleteIds = array_values(array_filter(array_map('intval', $operation['delete_ids'] ?? []), static fn(int $id): bool => $id > 0));
        if ($deleteIds) {
            $placeholders = implode(',', array_fill(0, count($deleteIds), '?'));
            $params = array_merge([$assetId, (int)$field['id']], $deleteIds);
            $stmt = db()->prepare("SELECT * FROM asset_file_values WHERE asset_id = ? AND field_id = ? AND id IN ({$placeholders})");
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
            foreach ($rows as $row) {
                $cleanup['delete_paths'][] = $dir . '/' . $row['stored_name'];
            }
            $deleteStmt = db()->prepare("DELETE FROM asset_file_values WHERE asset_id = ? AND field_id = ? AND id IN ({$placeholders})");
            $deleteStmt->execute($params);
        }

        foreach (($operation['uploads'] ?? []) as $upload) {
            $storedName = sprintf('asset_%d_field_%d_%s.%s', $assetId, (int)$field['id'], bin2hex(random_bytes(8)), $upload['extension']);
            $targetPath = $dir . '/' . $storedName;
            if (!move_uploaded_file((string)$upload['tmp_name'], $targetPath)) {
                throw new RuntimeException('Failed to store uploaded file: ' . (string)$upload['name']);
            }
            $cleanup['new_paths'][] = $targetPath;
            $stmt = db()->prepare('INSERT INTO asset_file_values (asset_id, field_id, original_name, stored_name, file_ext, mime_type, file_size, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $assetId,
                (int)$field['id'],
                $upload['name'],
                $storedName,
                $upload['extension'],
                $upload['mime_type'],
                (int)$upload['size'],
            ]);
        }
    }
}

function finalize_asset_file_changes(array $cleanup, bool $committed): void
{
    $paths = $committed ? ($cleanup['delete_paths'] ?? []) : ($cleanup['new_paths'] ?? []);
    foreach ($paths as $path) {
        if (is_string($path) && is_file($path)) {
            @unlink($path);
        }
    }
}

function get_assets_grouped_by_category(array $filters = [], ?array $user = null): array
{
    $grouped = [];
    $segmentId = asset_normalize_segment_id(isset($filters['segment_id']) ? (int)$filters['segment_id'] : null);
    $categories = get_asset_categories(false, $segmentId);
    if (!empty($filters['category_id'])) {
        $categories = array_values(array_filter($categories, fn($category) => (int)$category['id'] === (int)$filters['category_id']));
    }
    foreach ($categories as $category) {
        $filters['category_id'] = (int)$category['id'];
        $grouped[] = [
            'category' => $category,
            'assets' => get_assets($filters, $user),
        ];
    }
    return $grouped;
}

function asset_export_active_fields(?int $segmentId = null): array
{
    return array_values(array_filter(
        get_asset_fields(false, $segmentId),
        static fn(array $field): bool => (int)$field['active_status'] === 1 && (int)$field['is_import_enabled'] === 1
    ));
}

function asset_export_headers(bool $includeOfficeName = false, ?int $segmentId = null): array
{
    $headers = ['serial' => 'Serial No'];
    if ($includeOfficeName) {
        $headers['office_name'] = 'Office Name';
    }
    $headers['category'] = 'Category';
    if (asset_subcategory_enabled($segmentId)) {
        $headers['subcategory'] = 'Sub-category';
    }

    foreach (asset_export_active_fields($segmentId) as $field) {
        $rawLabel = trim((string)($field['label'] ?? ''));
        $parts = preg_split('/\s*\/\s*/u', $rawLabel);
        $headers[$field['field_key']] = trim((string)($parts[0] ?? $rawLabel));
    }

    return $headers;
}

function build_asset_export_rows(array $filters = [], ?array $user = null, bool $includeOfficeName = false): array
{
    $rows = [];
    $segmentId = asset_normalize_segment_id(isset($filters['segment_id']) ? (int)$filters['segment_id'] : null);
    $fields = asset_export_active_fields($segmentId);
    $assets = get_assets($filters, $user);

    foreach ($assets as $index => $asset) {
        $row = [
            'serial' => $index + 1,
            'category' => (string)($asset['category_name'] ?? ''),
        ];
        if (asset_subcategory_enabled($segmentId)) {
            $row['subcategory'] = (string)($asset['subcategory_name'] ?? '');
        }
        if ($includeOfficeName) {
            $row['office_name'] = trim((string)(($asset['office_type_label'] ?? '') . ' - ' . ($asset['office_name'] ?? '')), ' -');
        }
        foreach ($fields as $field) {
            $row[$field['field_key']] = (string)($asset['values'][$field['field_key']] ?? '');
        }
        $rows[] = $row;
    }

    return $rows;
}

function export_asset_data_excel(array $filters = [], ?array $user = null, bool $includeOfficeName = false): void
{
    $segmentId = asset_normalize_segment_id(isset($filters['segment_id']) ? (int)$filters['segment_id'] : null);
    $headers = asset_export_headers($includeOfficeName, $segmentId);
    $rows = build_asset_export_rows($filters, $user, $includeOfficeName);
    $suffix = $includeOfficeName ? 'superadmin' : 'office';
    export_excel($rows, $headers, 'asset_data_' . $suffix . '.xlsx', 'Asset Data');
}

function get_asset_file_record(int $fileId): ?array
{
    $stmt = db()->prepare('
        SELECT f.*, af.field_key, af.label AS field_label, a.office_type, a.office_id, a.deleted_at, a.active_status
        FROM asset_file_values f
        JOIN asset_fields af ON af.id = f.field_id
        JOIN assets a ON a.id = f.asset_id
        WHERE f.id = ?
        LIMIT 1
    ');
    $stmt->execute([$fileId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function stream_asset_file(int $fileId, ?array $user = null): void
{
    $file = get_asset_file_record($fileId);
    if (!$file) {
        http_response_code(404);
        exit('File not found.');
    }

    $asset = get_asset((int)$file['asset_id'], true);
    if (!$asset || !user_can_view_asset($user ?: current_user(), $asset, office_user_has_under_me_scope($user ?: current_user()) ? 'office_under_me' : 'my_office')) {
        http_response_code(403);
        exit('Not allowed.');
    }

    $path = asset_file_storage_dir() . '/' . $file['stored_name'];
    if (!is_file($path)) {
        http_response_code(404);
        exit('Stored file not found.');
    }

    header('Content-Type: ' . (string)$file['mime_type']);
    header('Content-Length: ' . (string)filesize($path));
    header('Content-Disposition: inline; filename="' . rawurlencode((string)$file['original_name']) . '"');
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function get_asset_declaration(int $officeType, int $officeId, ?int $segmentId = null): ?array
{
    $stmt = db()->prepare('SELECT * FROM office_asset_declarations WHERE segment_id = ? AND office_type = ? AND office_id = ? LIMIT 1');
    $stmt->execute([asset_normalize_segment_id($segmentId), $officeType, $officeId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function asset_relative_time_label(?string $datetime, string $emptyLabel = 'Never'): string
{
    $datetime = trim((string)$datetime);
    if ($datetime === '') {
        return $emptyLabel;
    }
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return $emptyLabel;
    }
    $diff = max(0, time() - $timestamp);
    if ($diff < 60) {
        return '1 min ago';
    }
    if ($diff < 3600) {
        return '1 hr ago';
    }
    if ($diff < 86400) {
        $hours = max(1, (int)floor($diff / 3600));
        return $hours . ' hrs ago';
    }
    if ($diff < 2592000) {
        $days = max(1, (int)floor($diff / 86400));
        return $days . ' days ago';
    }
    if ($diff < 31536000) {
        $months = max(1, (int)floor($diff / 2592000));
        return $months . ' month' . ($months === 1 ? '' : 's') . ' ago';
    }
    $years = max(1, (int)floor($diff / 31536000));
    return $years . ' year' . ($years === 1 ? '' : 's') . ' ago';
}

function asset_relative_age_days(?string $datetime): ?float
{
    $datetime = trim((string)$datetime);
    if ($datetime === '') {
        return null;
    }
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return null;
    }
    return max(0, (time() - $timestamp) / 86400);
}

function get_office_last_asset_update_at(int $officeType, int $officeId, ?int $segmentId = null): ?string
{
    $stmt = db()->prepare('
        SELECT MAX(COALESCE(updated_at, created_at)) AS last_update_at
        FROM assets
        WHERE segment_id = ? AND office_type = ? AND office_id = ?
    ');
    $stmt->execute([asset_normalize_segment_id($segmentId), $officeType, $officeId]);
    $value = $stmt->fetchColumn();
    return $value !== false && $value !== null ? (string)$value : null;
}

function get_office_activity_summary(int $officeType, int $officeId, ?int $segmentId = null): array
{
    $declaration = get_asset_declaration($officeType, $officeId, $segmentId);
    $lastSentAt = (string)($declaration['declared_at'] ?? '');
    $lastUpdateAt = (string)(get_office_last_asset_update_at($officeType, $officeId, $segmentId) ?? '');

    return [
        'last_sent_at' => $lastSentAt,
        'last_sent_label' => asset_relative_time_label($lastSentAt),
        'last_update_at' => $lastUpdateAt,
        'last_update_label' => asset_relative_time_label($lastUpdateAt, 'No updates yet'),
    ];
}

function declare_office_assets(int $officeType, int $officeId, int $userId, ?int $segmentId = null): void
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $user = current_user();
    $officerName = trim((string)($user['officer_name'] ?? ''));
    if ($officerName === '') {
        $officerName = (string)($user['email_id'] ?? '');
    }
    $existing = get_asset_declaration($officeType, $officeId, $segmentId);
    if ($existing) {
        db()->prepare('UPDATE office_asset_declarations SET declared_status = 1, declared_at = NOW(), declared_by = ?, declared_officer_name = ?, updated_at = NOW() WHERE id = ?')->execute([$userId, $officerName, (int)$existing['id']]);
        return;
    }
    db()->prepare('INSERT INTO office_asset_declarations (segment_id, office_type, office_id, declared_status, declared_at, declared_by, declared_officer_name, created_at) VALUES (?, ?, ?, 1, NOW(), ?, ?, NOW())')->execute([$segmentId, $officeType, $officeId, $userId, $officerName]);
}

function reset_office_asset_declarations(array $pairs, int $userId, ?int $segmentId = null): int
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $count = 0;
    foreach ($pairs as $pair) {
        $officeType = (int)($pair['office_type'] ?? 0);
        $officeId = (int)($pair['office_id'] ?? 0);
        if ($officeType <= 0 || $officeId <= 0) {
            continue;
        }
        $existing = get_asset_declaration($officeType, $officeId, $segmentId);
        if (!$existing) {
            db()->prepare('INSERT INTO office_asset_declarations (segment_id, office_type, office_id, declared_status, reset_at, reset_by, created_at) VALUES (?, ?, ?, 0, NOW(), ?, NOW())')->execute([$segmentId, $officeType, $officeId, $userId]);
            $count++;
            continue;
        }
        db()->prepare('UPDATE office_asset_declarations SET declared_status = 0, reset_at = NOW(), reset_by = ?, updated_at = NOW() WHERE id = ?')->execute([$userId, (int)$existing['id']]);
        $count++;
    }
    return $count;
}

function get_declaration_status_tables(array $filters = []): array
{
    return [
        2 => get_declarations_for_office_type(2, $filters),
        3 => get_declarations_for_office_type(3, $filters),
        4 => get_declarations_for_office_type(4, $filters),
        5 => get_declarations_for_office_type(5, $filters),
    ];
}

function get_declarations_for_office_type(int $officeType, array $filters = []): array
{
    $segmentId = asset_normalize_segment_id(isset($filters['segment_id']) ? (int)$filters['segment_id'] : null);
    $tableMap = [
        2 => 'zones',
        3 => 'circles',
        4 => 'divisions',
        5 => 'subdivisions',
    ];
    if (!isset($tableMap[$officeType])) {
        return [];
    }
    $table = $tableMap[$officeType];
    $sql = "SELECT o.id AS office_id, o.office_name, o.active_status, d.declared_status, d.declared_at, d.declared_officer_name, d.reset_at
        FROM {$table} o
        LEFT JOIN office_asset_declarations d
            ON d.segment_id = ? AND d.office_type = ? AND d.office_id = o.id
        ORDER BY o.office_name ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute([$segmentId, $officeType]);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $officeId = (int)$row['office_id'];
        $lastSentAt = (string)($row['declared_at'] ?? '');
        $lastUpdateAt = (string)(get_office_last_asset_update_at($officeType, $officeId, $segmentId) ?? '');
        $status = !empty($row['declared_status']) ? 'declared' : 'undeclared';

        $sentAge = asset_relative_age_days($lastSentAt);
        $updatedAge = asset_relative_age_days($lastUpdateAt);

        if (!empty($filters['status']) && $filters['status'] !== $status) {
            continue;
        }
        if (isset($filters['sent_earlier']) && $filters['sent_earlier'] !== null && ($sentAge === null || $sentAge < (int)$filters['sent_earlier'])) {
            continue;
        }
        if (isset($filters['sent_sooner']) && $filters['sent_sooner'] !== null && ($sentAge === null || $sentAge > (int)$filters['sent_sooner'])) {
            continue;
        }
        if (isset($filters['updated_earlier']) && $filters['updated_earlier'] !== null && ($updatedAge === null || $updatedAge < (int)$filters['updated_earlier'])) {
            continue;
        }
        if (isset($filters['updated_sooner']) && $filters['updated_sooner'] !== null && ($updatedAge === null || $updatedAge > (int)$filters['updated_sooner'])) {
            continue;
        }

        $row['status_key'] = $status;
        $row['last_sent_at'] = $lastSentAt;
        $row['last_sent_label'] = asset_relative_time_label($lastSentAt);
        $row['last_update_at'] = $lastUpdateAt;
        $row['last_update_label'] = asset_relative_time_label($lastUpdateAt, 'No updates yet');
        $rows[] = $row;
    }

    return $rows;
}

function office_kind_to_type(string $kind): int
{
    return match ($kind) {
        'zone' => 2,
        'circle' => 3,
        'division' => 4,
        'subdivision' => 5,
        'sub-division' => 5,
        default => 0,
    };
}

function office_type_to_kind(int $officeType): string
{
    return match ($officeType) {
        2 => 'zone',
        3 => 'circle',
        4 => 'division',
        5 => 'subdivision',
        default => '',
    };
}

function office_table_for_kind(string $kind): ?string
{
    return match ($kind) {
        'zone' => 'zones',
        'circle' => 'circles',
        'division' => 'divisions',
        'subdivision', 'sub-division' => 'subdivisions',
        default => null,
    };
}

function office_management_flag_column(): string
{
    return 'allow_office_user_management';
}

function office_user_access_options(): array
{
    return [
        1 => 'Office Head',
        2 => 'Full Access',
        3 => 'View Only',
    ];
}

function office_user_access_label(int $level): string
{
    return office_user_access_options()[$level] ?? 'Unknown';
}

function office_allows_user_management(int $officeType, int $officeId): bool
{
    $kind = office_type_to_kind($officeType);
    $table = $kind !== '' ? office_table_for_kind($kind) : null;
    if (!$table || $officeId <= 0) {
        return false;
    }
    $stmt = db()->prepare("SELECT " . office_management_flag_column() . " FROM {$table} WHERE id = ? LIMIT 1");
    $stmt->execute([$officeId]);
    $value = $stmt->fetchColumn();
    return $value === false ? false : (int)$value === 1;
}

function set_office_user_management_flag(string $kind, int $officeId, int $allowed): void
{
    $table = office_table_for_kind($kind);
    if (!$table || $officeId <= 0) {
        throw new RuntimeException('Invalid office.');
    }
    db()->prepare("UPDATE {$table} SET " . office_management_flag_column() . " = ?, updated_at = NOW() WHERE id = ?")->execute([$allowed === 1 ? 1 : 0, $officeId]);
}

function user_can_manage_office_users(?array $user = null, ?int $officeType = null, ?int $officeId = null): bool
{
    $user = $user ?: current_user();
    if (!$user) {
        return false;
    }
    if (is_superadmin()) {
        return true;
    }
    $ctx = current_office_context($user);
    if (!$ctx) {
        return false;
    }
    $targetOfficeType = $officeType ?? (int)$ctx['office_type'];
    $targetOfficeId = $officeId ?? (int)$ctx['office_id'];
    if ((int)$ctx['office_type'] !== $targetOfficeType || (int)$ctx['office_id'] !== $targetOfficeId) {
        return false;
    }
    return (int)($user['is_primary_office_user'] ?? 0) === 1
        && (int)($user['office_access_level'] ?? 2) === 1
        && office_allows_user_management($targetOfficeType, $targetOfficeId);
}

function office_user_column_for_type(int $officeType): ?string
{
    return match ($officeType) {
        2 => 'zone_id',
        3 => 'circle_id',
        4 => 'division_id',
        5 => 'subdivision_id',
        default => null,
    };
}

function get_office_users(int $officeType, int $officeId): array
{
    $column = office_user_column_for_type($officeType);
    if (!$column || $officeId <= 0) {
        return [];
    }
    $stmt = db()->prepare("SELECT * FROM users WHERE office_type = ? AND {$column} = ? ORDER BY is_primary_office_user DESC, id ASC");
    $stmt->execute([$officeType, $officeId]);
    return $stmt->fetchAll();
}

function office_user_scope_payload(int $officeType, int $officeId): array
{
    if ($officeType === 2) {
        return ['zone_id' => $officeId, 'circle_id' => null, 'division_id' => null, 'subdivision_id' => null];
    }
    if ($officeType === 3) {
        $circle = find_circle_with_zone($officeId);
        return [
            'zone_id' => $circle ? (int)$circle['zone_id'] : null,
            'circle_id' => $officeId,
            'division_id' => null,
            'subdivision_id' => null,
        ];
    }
    if ($officeType === 4) {
        $division = find_division_with_hierarchy($officeId);
        return [
            'zone_id' => $division ? (int)$division['zone_id'] : null,
            'circle_id' => $division ? (int)$division['circle_id'] : null,
            'division_id' => $officeId,
            'subdivision_id' => null,
        ];
    }
    if ($officeType === 5) {
        $subdivision = find_subdivision_with_hierarchy($officeId);
        return [
            'zone_id' => $subdivision ? (int)$subdivision['zone_id'] : null,
            'circle_id' => $subdivision ? (int)$subdivision['circle_id'] : null,
            'division_id' => $subdivision ? (int)$subdivision['division_id'] : null,
            'subdivision_id' => $officeId,
        ];
    }
    return ['zone_id' => null, 'circle_id' => null, 'division_id' => null, 'subdivision_id' => null];
}

function office_default_password(string $email): string
{
    $local = trim((string)strtok($email, '@'));
    if ($local === '') {
        return '1Password';
    }
    return '1' . ucfirst($local);
}

function find_circle_with_zone(int $circleId): ?array
{
    $stmt = db()->prepare('SELECT c.*, z.office_name AS zone_name FROM circles c LEFT JOIN zones z ON z.id = c.zone_id WHERE c.id = ? LIMIT 1');
    $stmt->execute([$circleId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_division_with_hierarchy(int $divisionId): ?array
{
    $stmt = db()->prepare('SELECT d.*, c.office_name AS circle_name, z.office_name AS zone_name FROM divisions d LEFT JOIN circles c ON c.id = d.circle_id LEFT JOIN zones z ON z.id = d.zone_id WHERE d.id = ? LIMIT 1');
    $stmt->execute([$divisionId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_subdivision_with_hierarchy(int $subdivisionId): ?array
{
    $stmt = db()->prepare('SELECT s.*, d.office_name AS division_name, c.office_name AS circle_name, z.office_name AS zone_name FROM subdivisions s LEFT JOIN divisions d ON d.id = s.division_id LEFT JOIN circles c ON c.id = s.circle_id LEFT JOIN zones z ON z.id = s.zone_id WHERE s.id = ? LIMIT 1');
    $stmt->execute([$subdivisionId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function find_primary_office_user(int $officeType, int $officeId): ?array
{
    if ($officeType <= 0 || $officeId <= 0) {
        return null;
    }
    if ($officeType === 2) {
        $stmt = db()->prepare('SELECT * FROM users WHERE office_type = 2 AND zone_id = ? ORDER BY is_primary_office_user DESC, id ASC LIMIT 1');
    } elseif ($officeType === 3) {
        $stmt = db()->prepare('SELECT * FROM users WHERE office_type = 3 AND circle_id = ? ORDER BY is_primary_office_user DESC, id ASC LIMIT 1');
    } elseif ($officeType === 4) {
        $stmt = db()->prepare('SELECT * FROM users WHERE office_type = 4 AND division_id = ? ORDER BY is_primary_office_user DESC, id ASC LIMIT 1');
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE office_type = 5 AND subdivision_id = ? ORDER BY is_primary_office_user DESC, id ASC LIMIT 1');
    }
    $stmt->execute([$officeId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function office_user_payload(string $kind, int $officeId, int $zoneId, ?int $circleId, ?int $divisionId, ?int $subdivisionId): array
{
    $officeType = office_kind_to_type($kind);
    return [
        'office_type' => $officeType,
        'office_role' => 1,
        'zone_id' => $zoneId > 0 ? $zoneId : null,
        'circle_id' => $circleId && $circleId > 0 ? $circleId : null,
        'division_id' => $divisionId && $divisionId > 0 ? $divisionId : null,
        'subdivision_id' => $subdivisionId && $subdivisionId > 0 ? $subdivisionId : null,
        'office_id' => $officeId,
    ];
}

function insert_office_user(string $email, array $payload, int $activeStatus = 1): int
{
    $stmt = db()->prepare('INSERT INTO users (email_id, officer_name, password, office_type, office_role, zone_id, circle_id, division_id, subdivision_id, is_primary_office_user, office_access_level, active_status, created_at) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, 1, 1, ?, NOW())');
    $stmt->execute([
        $email,
        password_hash(office_default_password($email), PASSWORD_DEFAULT),
        $payload['office_type'],
        $payload['office_role'],
        $payload['zone_id'],
        $payload['circle_id'],
        $payload['division_id'],
        $payload['subdivision_id'],
        $activeStatus,
    ]);
    return (int)db()->lastInsertId();
}

function save_office_user(string $kind, int $officeId, string $email, int $zoneId, ?int $circleId, ?int $divisionId, ?int $subdivisionId, int $activeStatus): void
{
    $payload = office_user_payload($kind, $officeId, $zoneId, $circleId, $divisionId, $subdivisionId);
    $officeType = $payload['office_type'];
    $existing = find_primary_office_user($officeType, $officeId);
    $emailCheck = db()->prepare('SELECT id FROM users WHERE email_id = ? LIMIT 1');
    $emailCheck->execute([$email]);
    $emailOwnerId = (int)($emailCheck->fetchColumn() ?: 0);
    if ($emailOwnerId > 0 && (!$existing || $emailOwnerId !== (int)$existing['id'])) {
        throw new RuntimeException('The email is already used by another user.');
    }

    if (!$existing) {
        insert_office_user($email, $payload, $activeStatus);
        return;
    }

    db()->prepare('UPDATE users SET email_id = ?, office_type = ?, office_role = ?, zone_id = ?, circle_id = ?, division_id = ?, subdivision_id = ?, is_primary_office_user = 1, office_access_level = 1, active_status = ?, updated_at = NOW() WHERE id = ?')->execute([
        $email,
        $payload['office_type'],
        $payload['office_role'],
        $payload['zone_id'],
        $payload['circle_id'],
        $payload['division_id'],
        $payload['subdivision_id'],
        $activeStatus,
        (int)$existing['id'],
    ]);
}

function create_or_update_additional_office_user(int $officeType, int $officeId, string $email, string $officerName, int $accessLevel, ?int $userId = null): void
{
    if (!in_array($accessLevel, [2, 3], true)) {
        throw new RuntimeException('Invalid access level.');
    }
    $email = trim($email);
    $officerName = trim($officerName);
    if ($email === '') {
        throw new RuntimeException('Email ID is required.');
    }

    $column = office_user_column_for_type($officeType);
    if (!$column || $officeId <= 0) {
        throw new RuntimeException('Invalid office scope.');
    }

    $scope = office_user_scope_payload($officeType, $officeId);
    $emailCheck = db()->prepare('SELECT id FROM users WHERE email_id = ? LIMIT 1');
    $emailCheck->execute([$email]);
    $emailOwnerId = (int)($emailCheck->fetchColumn() ?: 0);
    if ($emailOwnerId > 0 && ($userId === null || $emailOwnerId !== $userId)) {
        throw new RuntimeException('Email is already used by another user.');
    }

    db()->beginTransaction();
    try {
        if ($userId && $userId > 0) {
            $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND office_type = ? AND {$column} = ? LIMIT 1");
            $stmt->execute([$userId, $officeType, $officeId]);
            $existing = $stmt->fetch();
            if (!$existing) {
                throw new RuntimeException('Office user not found.');
            }
            if ((int)($existing['is_primary_office_user'] ?? 0) === 1) {
                throw new RuntimeException('Primary office head cannot be edited from this form.');
            }
            db()->prepare('UPDATE users SET email_id = ?, officer_name = ?, office_access_level = ?, updated_at = NOW() WHERE id = ?')->execute([
                $email,
                $officerName === '' ? null : $officerName,
                $accessLevel,
                $userId,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO users (email_id, officer_name, password, office_type, office_role, zone_id, circle_id, division_id, subdivision_id, is_primary_office_user, office_access_level, active_status, created_at) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?, 0, ?, 1, NOW())');
            $stmt->execute([
                $email,
                $officerName === '' ? null : $officerName,
                password_hash('1234', PASSWORD_DEFAULT),
                $officeType,
                $scope['zone_id'],
                $scope['circle_id'],
                $scope['division_id'],
                $scope['subdivision_id'],
                $accessLevel,
            ]);
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function get_manageable_office_targets(?array $user = null): array
{
    $user = $user ?: current_user();
    if (!$user) {
        return [];
    }

    if (!is_superadmin()) {
        $ctx = current_office_context($user);
        if (!$ctx) {
            return [];
        }
        return [[
            'office_type' => (int)$ctx['office_type'],
            'office_id' => (int)$ctx['office_id'],
            'office_name' => office_name_from_type_id((int)$ctx['office_type'], (int)$ctx['office_id']),
            'office_type_label' => asset_office_type_label((int)$ctx['office_type']),
            'allow_user_management' => office_allows_user_management((int)$ctx['office_type'], (int)$ctx['office_id']) ? 1 : 0,
        ]];
    }

    $targets = [];
    foreach ([
        2 => ['table' => 'zones', 'name' => 'office_name'],
        3 => ['table' => 'circles', 'name' => 'office_name'],
        4 => ['table' => 'divisions', 'name' => 'office_name'],
        5 => ['table' => 'subdivisions', 'name' => 'office_name'],
    ] as $officeType => $meta) {
        $stmt = db()->query("SELECT id, {$meta['name']} AS office_name, " . office_management_flag_column() . " AS allow_user_management FROM {$meta['table']} ORDER BY office_name");
        foreach ($stmt->fetchAll() as $row) {
            $targets[] = [
                'office_type' => $officeType,
                'office_id' => (int)$row['id'],
                'office_name' => (string)$row['office_name'],
                'office_type_label' => asset_office_type_label($officeType),
                'allow_user_management' => (int)($row['allow_user_management'] ?? 1),
            ];
        }
    }

    return $targets;
}

function get_superadmin_additional_users(): array
{
    $stmt = db()->query('SELECT * FROM users WHERE office_role = 3 AND office_access_level = 3 AND is_primary_office_user = 0 ORDER BY id ASC');
    return $stmt->fetchAll();
}

function create_or_update_superadmin_additional_user(string $email, string $officerName, ?int $userId = null): void
{
    $email = trim($email);
    $officerName = trim($officerName);
    if ($email === '') {
        throw new RuntimeException('Email ID is required.');
    }

    $emailCheck = db()->prepare('SELECT id FROM users WHERE email_id = ? LIMIT 1');
    $emailCheck->execute([$email]);
    $emailOwnerId = (int)($emailCheck->fetchColumn() ?: 0);
    if ($emailOwnerId > 0 && ($userId === null || $emailOwnerId !== $userId)) {
        throw new RuntimeException('Email is already used by another user.');
    }

    db()->beginTransaction();
    try {
        if ($userId && $userId > 0) {
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ? AND office_role = 3 AND office_access_level = 3 AND is_primary_office_user = 0 LIMIT 1');
            $stmt->execute([$userId]);
            $existing = $stmt->fetch();
            if (!$existing) {
                throw new RuntimeException('Superadmin additional user not found.');
            }
            db()->prepare('UPDATE users SET email_id = ?, officer_name = ?, updated_at = NOW() WHERE id = ?')->execute([
                $email,
                $officerName === '' ? null : $officerName,
                $userId,
            ]);
        } else {
            $stmt = db()->prepare('INSERT INTO users (email_id, officer_name, password, office_type, office_role, is_primary_office_user, office_access_level, active_status, created_at) VALUES (?, ?, ?, 1, 3, 0, 3, 1, NOW())');
            $stmt->execute([
                $email,
                $officerName === '' ? null : $officerName,
                password_hash('1234', PASSWORD_DEFAULT),
            ]);
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function reset_superadmin_additional_user_password(int $userId): void
{
    $stmt = db()->prepare('SELECT id FROM users WHERE id = ? AND office_role = 3 AND office_access_level = 3 AND is_primary_office_user = 0 LIMIT 1');
    $stmt->execute([$userId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Superadmin additional user not found.');
    }
    db()->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')->execute([
        password_hash('1234', PASSWORD_DEFAULT),
        $userId,
    ]);
}

function toggle_superadmin_additional_user_status(int $userId, int $activeStatus): void
{
    $stmt = db()->prepare('SELECT id FROM users WHERE id = ? AND office_role = 3 AND office_access_level = 3 AND is_primary_office_user = 0 LIMIT 1');
    $stmt->execute([$userId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Superadmin additional user not found.');
    }
    db()->prepare('UPDATE users SET active_status = ?, updated_at = NOW() WHERE id = ?')->execute([$activeStatus === 1 ? 1 : 0, $userId]);
}

function get_office_user_management_tables(): array
{
    $overview = get_offices_overview();
    $tables = [
        2 => [],
        3 => [],
        4 => [],
        5 => [],
    ];
    foreach ($overview['zones'] as $row) {
        $tables[2][] = [
            'office_id' => (int)$row['id'],
            'office_name' => (string)$row['office_name'],
            'officer_name' => (string)($row['linked_user']['officer_name'] ?? ''),
            'email_id' => (string)($row['linked_user']['email_id'] ?? ''),
            'allow_user_management' => (int)($row['allow_office_user_management'] ?? 1),
        ];
    }
    foreach ($overview['circles'] as $row) {
        $tables[3][] = [
            'office_id' => (int)$row['id'],
            'office_name' => (string)$row['office_name'],
            'officer_name' => (string)($row['linked_user']['officer_name'] ?? ''),
            'email_id' => (string)($row['linked_user']['email_id'] ?? ''),
            'allow_user_management' => (int)($row['allow_office_user_management'] ?? 1),
        ];
    }
    foreach ($overview['divisions'] as $row) {
        $tables[4][] = [
            'office_id' => (int)$row['id'],
            'office_name' => (string)$row['office_name'],
            'officer_name' => (string)($row['linked_user']['officer_name'] ?? ''),
            'email_id' => (string)($row['linked_user']['email_id'] ?? ''),
            'allow_user_management' => (int)($row['allow_office_user_management'] ?? 1),
        ];
    }
    foreach ($overview['subdivisions'] as $row) {
        $tables[5][] = [
            'office_id' => (int)$row['id'],
            'office_name' => (string)$row['office_name'],
            'officer_name' => (string)($row['linked_user']['officer_name'] ?? ''),
            'email_id' => (string)($row['linked_user']['email_id'] ?? ''),
            'allow_user_management' => (int)($row['allow_office_user_management'] ?? 1),
        ];
    }
    return $tables;
}

function bulk_set_office_user_management_permissions(array $pairs, int $allowed): int
{
    $updated = 0;
    db()->beginTransaction();
    try {
        foreach ($pairs as $pair) {
            $officeType = (int)($pair['office_type'] ?? 0);
            $officeId = (int)($pair['office_id'] ?? 0);
            if ($officeType <= 0 || $officeId <= 0) {
                continue;
            }
            $kind = office_type_to_kind($officeType);
            if ($kind === '') {
                continue;
            }
            set_office_user_management_flag($kind, $officeId, $allowed);
            $updated++;
        }
        db()->commit();
        return $updated;
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function reset_additional_office_user_password(int $officeType, int $officeId, int $userId): void
{
    $column = office_user_column_for_type($officeType);
    if (!$column || $officeId <= 0 || $userId <= 0) {
        throw new RuntimeException('Invalid office user.');
    }
    $stmt = db()->prepare("SELECT id FROM users WHERE id = ? AND office_type = ? AND {$column} = ? LIMIT 1");
    $stmt->execute([$userId, $officeType, $officeId]);
    if (!$stmt->fetchColumn()) {
        throw new RuntimeException('Office user not found.');
    }
    db()->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')->execute([
        password_hash('1234', PASSWORD_DEFAULT),
        $userId,
    ]);
}

function toggle_additional_office_user_status(int $officeType, int $officeId, int $userId, int $activeStatus): void
{
    $column = office_user_column_for_type($officeType);
    if (!$column || $officeId <= 0 || $userId <= 0) {
        throw new RuntimeException('Invalid office user.');
    }
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ? AND office_type = ? AND {$column} = ? LIMIT 1");
    $stmt->execute([$userId, $officeType, $officeId]);
    $existing = $stmt->fetch();
    if (!$existing) {
        throw new RuntimeException('Office user not found.');
    }
    if ((int)($existing['is_primary_office_user'] ?? 0) === 1) {
        throw new RuntimeException('Primary office head cannot be disabled here.');
    }
    db()->prepare('UPDATE users SET active_status = ?, updated_at = NOW() WHERE id = ?')->execute([$activeStatus === 1 ? 1 : 0, $userId]);
}

function create_office_with_user(string $kind, string $name, string $address, string $email, ?int $zoneId = null, ?int $circleId = null, ?int $divisionId = null): void
{
    $kind = strtolower(trim($kind));
    $table = office_table_for_kind($kind);
    if ($table === null) {
        throw new RuntimeException('Invalid office type.');
    }
    if ($email === '') {
        throw new RuntimeException('User email is required.');
    }

    db()->beginTransaction();
    try {
        $addressValue = $address === '' ? null : $address;
        if ($kind === 'zone') {
            db()->prepare('INSERT INTO zones (office_name, office_address, office_type, active_status, created_at) VALUES (?, ?, 2, 1, NOW())')->execute([$name, $addressValue]);
            $officeId = (int)db()->lastInsertId();
            save_office_user('zone', $officeId, $email, $officeId, null, null, null, 1);
        } elseif ($kind === 'circle') {
            if (($zoneId ?? 0) <= 0) {
                throw new RuntimeException('Circle requires a zone.');
            }
            db()->prepare('INSERT INTO circles (office_name, office_address, office_type, zone_id, active_status, created_at) VALUES (?, ?, 3, ?, 1, NOW())')->execute([$name, $addressValue, $zoneId]);
            $officeId = (int)db()->lastInsertId();
            save_office_user('circle', $officeId, $email, $zoneId, $officeId, null, null, 1);
        } elseif ($kind === 'division') {
            $circle = find_circle_with_zone((int)$circleId);
            if (!$circle) {
                throw new RuntimeException('Division requires a valid circle.');
            }
            $zoneId = (int)$circle['zone_id'];
            $circleId = (int)$circle['id'];
            db()->prepare('INSERT INTO divisions (office_name, office_address, office_type, zone_id, circle_id, field_office, active_status, created_at) VALUES (?, ?, 4, ?, ?, 1, 1, NOW())')->execute([$name, $addressValue, $zoneId, $circleId]);
            $officeId = (int)db()->lastInsertId();
            save_office_user('division', $officeId, $email, $zoneId, $circleId, $officeId, null, 1);
        } else {
            $division = find_division_with_hierarchy((int)$divisionId);
            if (!$division) {
                throw new RuntimeException('Sub-division requires a valid division.');
            }
            $zoneId = (int)$division['zone_id'];
            $circleId = (int)$division['circle_id'];
            $divisionId = (int)$division['id'];
            db()->prepare('INSERT INTO subdivisions (office_name, office_address, office_type, zone_id, circle_id, division_id, active_status, created_at) VALUES (?, ?, 5, ?, ?, ?, 1, NOW())')->execute([$name, $addressValue, $zoneId, $circleId, $divisionId]);
            $officeId = (int)db()->lastInsertId();
            save_office_user('subdivision', $officeId, $email, $zoneId, $circleId, $divisionId, $officeId, 1);
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function update_office_with_user(string $kind, int $officeId, string $name, string $address, string $email, ?int $zoneId = null, ?int $circleId = null, ?int $divisionId = null): void
{
    $kind = strtolower(trim($kind));
    $table = office_table_for_kind($kind);
    if ($table === null) {
        throw new RuntimeException('Invalid office type.');
    }
    if ($officeId <= 0) {
        throw new RuntimeException('Invalid office.');
    }

    db()->beginTransaction();
    try {
        $addressValue = $address === '' ? null : $address;
        if ($kind === 'zone') {
            $statusStmt = db()->prepare('SELECT active_status FROM zones WHERE id = ? LIMIT 1');
            $statusStmt->execute([$officeId]);
            $activeStatus = (int)($statusStmt->fetchColumn() ?: 1);
            db()->prepare('UPDATE zones SET office_name = ?, office_address = ?, updated_at = NOW() WHERE id = ?')->execute([$name, $addressValue, $officeId]);
            if ($email !== '') {
                save_office_user('zone', $officeId, $email, $officeId, null, null, null, $activeStatus);
            }
        } elseif ($kind === 'circle') {
            if (($zoneId ?? 0) <= 0) {
                throw new RuntimeException('Circle requires a zone.');
            }
            $statusStmt = db()->prepare('SELECT active_status FROM circles WHERE id = ? LIMIT 1');
            $statusStmt->execute([$officeId]);
            $activeStatus = (int)($statusStmt->fetchColumn() ?: 1);
            db()->prepare('UPDATE circles SET office_name = ?, office_address = ?, zone_id = ?, updated_at = NOW() WHERE id = ?')->execute([$name, $addressValue, $zoneId, $officeId]);
            if ($email !== '') {
                save_office_user('circle', $officeId, $email, $zoneId, $officeId, null, null, $activeStatus);
            }
        } elseif ($kind === 'division') {
            $circle = find_circle_with_zone((int)$circleId);
            if (!$circle) {
                throw new RuntimeException('Division requires a valid circle.');
            }
            $zoneId = (int)$circle['zone_id'];
            $circleId = (int)$circle['id'];
            $statusStmt = db()->prepare('SELECT active_status FROM divisions WHERE id = ? LIMIT 1');
            $statusStmt->execute([$officeId]);
            $activeStatus = (int)($statusStmt->fetchColumn() ?: 1);
            db()->prepare('UPDATE divisions SET office_name = ?, office_address = ?, zone_id = ?, circle_id = ?, updated_at = NOW() WHERE id = ?')->execute([$name, $addressValue, $zoneId, $circleId, $officeId]);
            if ($email !== '') {
                save_office_user('division', $officeId, $email, $zoneId, $circleId, $officeId, null, $activeStatus);
            }
        } else {
            $division = find_division_with_hierarchy((int)$divisionId);
            if (!$division) {
                throw new RuntimeException('Sub-division requires a valid division.');
            }
            $zoneId = (int)$division['zone_id'];
            $circleId = (int)$division['circle_id'];
            $divisionId = (int)$division['id'];
            $statusStmt = db()->prepare('SELECT active_status FROM subdivisions WHERE id = ? LIMIT 1');
            $statusStmt->execute([$officeId]);
            $activeStatus = (int)($statusStmt->fetchColumn() ?: 1);
            db()->prepare('UPDATE subdivisions SET office_name = ?, office_address = ?, zone_id = ?, circle_id = ?, division_id = ?, updated_at = NOW() WHERE id = ?')->execute([$name, $addressValue, $zoneId, $circleId, $divisionId, $officeId]);
            if ($email !== '') {
                save_office_user('subdivision', $officeId, $email, $zoneId, $circleId, $divisionId, $officeId, $activeStatus);
            }
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function toggle_office_active_status(string $kind, int $officeId, int $activeStatus): void
{
    $kind = strtolower(trim($kind));
    $table = office_table_for_kind($kind);
    $officeType = office_kind_to_type($kind);
    if ($table === null || $officeType === 0 || $officeId <= 0) {
        throw new RuntimeException('Invalid office.');
    }

    db()->beginTransaction();
    try {
        db()->prepare("UPDATE {$table} SET active_status = ?, updated_at = NOW() WHERE id = ?")->execute([$activeStatus, $officeId]);
        if ($officeType === 2) {
            db()->prepare('UPDATE users SET active_status = ?, updated_at = NOW() WHERE office_type = 2 AND zone_id = ?')->execute([$activeStatus, $officeId]);
        } elseif ($officeType === 3) {
            db()->prepare('UPDATE users SET active_status = ?, updated_at = NOW() WHERE office_type = 3 AND circle_id = ?')->execute([$activeStatus, $officeId]);
        } elseif ($officeType === 4) {
            db()->prepare('UPDATE users SET active_status = ?, updated_at = NOW() WHERE office_type = 4 AND division_id = ?')->execute([$activeStatus, $officeId]);
        } else {
            db()->prepare('UPDATE users SET active_status = ?, updated_at = NOW() WHERE office_type = 5 AND subdivision_id = ?')->execute([$activeStatus, $officeId]);
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function reset_office_user_password(string $kind, int $officeId): string
{
    $officeType = office_kind_to_type($kind);
    $user = find_primary_office_user($officeType, $officeId);
    if (!$user) {
        throw new RuntimeException('No linked user found for this office.');
    }
    $password = office_default_password((string)$user['email_id']);
    db()->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), (int)$user['id']]);
    return $password;
}

function get_offices_overview(): array
{
    $zones = db()->query('SELECT * FROM zones ORDER BY office_name')->fetchAll();
    $circles = db()->query('SELECT c.*, z.office_name AS zone_name FROM circles c LEFT JOIN zones z ON z.id = c.zone_id ORDER BY c.office_name')->fetchAll();
    $divisions = db()->query('SELECT d.*, z.office_name AS zone_name, c.office_name AS circle_name FROM divisions d LEFT JOIN zones z ON z.id = d.zone_id LEFT JOIN circles c ON c.id = d.circle_id ORDER BY d.office_name')->fetchAll();
    $subdivisions = db()->query('SELECT s.*, z.office_name AS zone_name, c.office_name AS circle_name, d.office_name AS division_name FROM subdivisions s LEFT JOIN zones z ON z.id = s.zone_id LEFT JOIN circles c ON c.id = s.circle_id LEFT JOIN divisions d ON d.id = s.division_id ORDER BY s.office_name')->fetchAll();

    $users = db()->query('SELECT * FROM users WHERE office_type IN (2, 3, 4, 5) ORDER BY office_role ASC, id ASC')->fetchAll();
    $userMap = [];
    foreach ($users as $user) {
        $officeType = (int)$user['office_type'];
        $officeId = match ($officeType) {
            2 => (int)($user['zone_id'] ?? 0),
            3 => (int)($user['circle_id'] ?? 0),
            4 => (int)($user['division_id'] ?? 0),
            5 => (int)($user['subdivision_id'] ?? 0),
            default => 0,
        };
        if ($officeId <= 0) {
            continue;
        }
        $key = $officeType . ':' . $officeId;
        if (!isset($userMap[$key])) {
            $userMap[$key] = $user;
        }
    }

    foreach ($zones as &$zone) {
        $zone['linked_user'] = $userMap['2:' . (int)$zone['id']] ?? null;
    }
    unset($zone);
    foreach ($circles as &$circle) {
        $circle['linked_user'] = $userMap['3:' . (int)$circle['id']] ?? null;
    }
    unset($circle);
    foreach ($divisions as &$division) {
        $division['linked_user'] = $userMap['4:' . (int)$division['id']] ?? null;
    }
    unset($division);
    foreach ($subdivisions as &$subdivision) {
        $subdivision['linked_user'] = $userMap['5:' . (int)$subdivision['id']] ?? null;
    }
    unset($subdivision);

    return [
        'zones' => $zones,
        'circles' => $circles,
        'divisions' => $divisions,
        'subdivisions' => $subdivisions,
    ];
}

function asset_template_storage_dir(): string
{
    return dirname(__DIR__, 2) . '/storage/templates';
}

function asset_template_storage_path(?int $segmentId = null): string
{
    return asset_template_storage_dir() . '/asset_import_template_segment_' . asset_normalize_segment_id($segmentId) . '.xlsx';
}

function asset_template_uploaded_info(?int $segmentId = null): ?array
{
    $path = asset_template_storage_path($segmentId);
    if (!is_file($path)) {
        return null;
    }
    return [
        'path' => $path,
        'filename' => basename($path),
        'updated_at' => date('Y-m-d H:i:s', (int)filemtime($path)),
        'size' => (int)filesize($path),
    ];
}

function asset_template_core_columns(?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $columns = [
        ['key' => 'category', 'label' => 'Category / Category'],
    ];
    if (asset_subcategory_enabled($segmentId)) {
        $columns[] = ['key' => 'subcategory', 'label' => 'Sub-category / Sub-category'];
    }
    foreach (get_asset_fields(false, $segmentId) as $field) {
        if ((int)$field['is_import_enabled'] !== 1 || (int)$field['active_status'] !== 1) {
            continue;
        }
        $columns[] = ['key' => $field['field_key'], 'label' => $field['label']];
    }
    return $columns;
}

function asset_template_columns(?int $segmentId = null): array
{
    return array_merge(
        [['key' => 'serial', 'label' => 'Serial No']],
        asset_template_core_columns($segmentId),
        [['key' => 'instruction', 'label' => 'Instruction']]
    );
}

function asset_import_expected_keys(?int $segmentId = null): array
{
    return array_column(asset_template_core_columns($segmentId), 'key');
}

function validate_uploaded_asset_template(string $tmpName, ?int $segmentId = null): array
{
    ensure_library('PhpOffice\\PhpSpreadsheet\\IOFactory', 'PhpSpreadsheet is not installed.');
    $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
    if (!$rows) {
        return ['Template file is empty.'];
    }
    $headerRow = array_values($rows[1] ?? []);
    $expectedCount = count(asset_template_columns($segmentId));
    if (count($headerRow) < $expectedCount) {
        return ['Template column count is less than the current required column sequence.'];
    }
    return [];
}

function find_asset_category_by_name(string $name, ?int $segmentId = null): ?array
{
    foreach (get_asset_categories(true, asset_normalize_segment_id($segmentId)) as $category) {
        if (strcasecmp(trim((string)$category['name']), trim($name)) === 0) {
            return $category;
        }
    }
    return null;
}

function find_asset_subcategory_by_name(int $categoryId, string $name, ?int $segmentId = null): ?array
{
    foreach (get_asset_subcategories($categoryId, true, asset_normalize_segment_id($segmentId)) as $subcategory) {
        if (strcasecmp(trim((string)$subcategory['name']), trim($name)) === 0) {
            return $subcategory;
        }
    }
    return null;
}

function sync_asset_template_info_sheet(string $tmpName, ?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    ensure_library('PhpOffice\\PhpSpreadsheet\\IOFactory', 'PhpSpreadsheet is not installed.');
    $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);
    $sheet = null;
    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
        if (strcasecmp(trim((string)$worksheet->getTitle()), 'info') === 0) {
            $sheet = $worksheet;
            break;
        }
    }
    if (!$sheet) {
        return ['categories_created' => 0, 'subcategories_created' => 0];
    }

    $rows = $sheet->toArray(null, true, true, true);
    if (!$rows) {
        return ['categories_created' => 0, 'subcategories_created' => 0];
    }

    $headerRow = $rows[1] ?? [];
    $categoriesCreated = 0;
    $subcategoriesCreated = 0;

    db()->beginTransaction();
    try {
        foreach ($headerRow as $column => $categoryNameRaw) {
            $categoryName = trim((string)$categoryNameRaw);
            if ($categoryName === '') {
                continue;
            }

            $category = find_asset_category_by_name($categoryName, $segmentId);
            if (!$category) {
                create_asset_category($categoryName, $segmentId);
                $category = find_asset_category_by_name($categoryName, $segmentId);
                $categoriesCreated++;
            } elseif ((int)($category['active_status'] ?? 1) !== 1) {
                set_asset_category_status((int)$category['id'], 1, $segmentId);
                $category = get_asset_category((int)$category['id'], $segmentId) ?? $category;
            }

            if (!$category) {
                continue;
            }

            $seenSubcategories = [];
            foreach ($rows as $rowNumber => $row) {
                if ($rowNumber === 1) {
                    continue;
                }
                $subcategoryName = trim((string)($row[$column] ?? ''));
                if ($subcategoryName === '') {
                    continue;
                }
                $subKey = mb_strtolower($subcategoryName, 'UTF-8');
                if (isset($seenSubcategories[$subKey])) {
                    continue;
                }
                $seenSubcategories[$subKey] = true;

                $subcategory = find_asset_subcategory_by_name((int)$category['id'], $subcategoryName, $segmentId);
                if (!$subcategory) {
                    create_asset_subcategory((int)$category['id'], $subcategoryName, $segmentId);
                    $subcategoriesCreated++;
                } elseif ((int)($subcategory['active_status'] ?? 1) !== 1) {
                    set_asset_subcategory_status((int)$subcategory['id'], 1, $segmentId);
                }
            }
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }

    return ['categories_created' => $categoriesCreated, 'subcategories_created' => $subcategoriesCreated];
}

function save_uploaded_asset_template(array $file, ?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Please choose a valid Excel template file.');
    }
    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'xls'], true)) {
        throw new RuntimeException('Template file must be an Excel file.');
    }
    $errors = validate_uploaded_asset_template($file['tmp_name'], $segmentId);
    if ($errors) {
        throw new RuntimeException(implode(' ', $errors));
    }
    $syncSummary = sync_asset_template_info_sheet($file['tmp_name'], $segmentId);
    $dir = asset_template_storage_dir();
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create template storage directory.');
    }
    $target = asset_template_storage_path($segmentId);
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Failed to save template file.');
    }
    return $syncSummary;
}

function output_asset_template_download(bool $preferUploaded = true, ?int $segmentId = null): void
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $stored = asset_template_uploaded_info($segmentId);
    if ($preferUploaded && $stored) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="asset_import_template.xlsx"');
        header('Content-Length: ' . (string)$stored['size']);
        header('Cache-Control: max-age=0');
        readfile($stored['path']);
        exit;
    }
    $headers = [];
    foreach (asset_template_columns($segmentId) as $column) {
        $headers[$column['key']] = $column['label'];
    }
    $rows = [[
        'serial' => '1',
        'category' => '',
        'instruction' => 'Fill data columns only. Keep heading row unchanged.',
    ]];
    if (asset_subcategory_enabled($segmentId)) {
        $rows[0]['subcategory'] = '';
    }
    export_excel($rows, $headers, 'asset_import_template.xlsx', 'Asset Template');
}

function asset_template_headers(?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $headers = [
        'category' => 'Category / শ্রেণি',
        'subcategory' => 'Sub-category / উপ-শ্রেণি',
    ];
    foreach (get_asset_fields(false, $segmentId) as $field) {
        if ((int)$field['is_import_enabled'] !== 1) {
            continue;
        }
        $headers[$field['field_key']] = $field['label'];
    }
    if (!asset_subcategory_enabled($segmentId)) {
        unset($headers['subcategory']);
    }
    return $headers;
}

function build_asset_template_rows(?int $segmentId = null): array
{
    if (!asset_subcategory_enabled($segmentId)) {
        return [[
            'category' => '',
        ]];
    }
    return [[
        'category' => '',
        'subcategory' => '',
    ]];
}

function parse_asset_import_file(string $tmpName, string $originalName, array $user, ?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    ensure_library('PhpOffice\\PhpSpreadsheet\\IOFactory', 'PhpSpreadsheet is not installed.');
    $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
    if (!$rows) {
        return ['errors' => ['Uploaded file is empty.'], 'rows' => []];
    }
    array_shift($rows);
    $expectedKeys = asset_import_expected_keys($segmentId);
    if (!$expectedKeys) {
        return ['errors' => ['No active import columns are configured.'], 'rows' => []];
    }

    $importRows = [];
    $topErrors = [];
    foreach ($rows as $rowIndex => $cells) {
        $values = array_values($cells);
        if (count($values) >= 2) {
            array_shift($values);
            array_pop($values);
        }
        $payload = [];
        $hasValue = false;
        foreach ($expectedKeys as $position => $key) {
            $value = trim((string)($values[$position] ?? ''));
            if ($value !== '') {
                $hasValue = true;
            }
            $payload[$key] = $value;
        }
        if (!$hasValue) {
            continue;
        }
        $importRows[] = stage_asset_import_row($payload, $rowIndex + 2, $segmentId);
    }

    if (!$importRows) {
        $topErrors[] = 'No importable rows were found in the file.';
    }

    $_SESSION['asset_import_review'] = [
        'filename' => $originalName,
        'segment_id' => $segmentId,
        'rows' => $importRows,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    return ['errors' => $topErrors, 'rows' => $importRows];
}

function validate_import_review_row(array $row, ?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $payload = [
        'category' => trim((string)($row['category'] ?? '')),
    ];
    if (asset_subcategory_enabled($segmentId)) {
        $payload['subcategory'] = trim((string)($row['subcategory'] ?? ''));
    }
    foreach (get_asset_fields(false, $segmentId) as $field) {
        if ((int)$field['is_import_enabled'] !== 1 || (int)$field['active_status'] !== 1) {
            continue;
        }
        $payload[$field['field_key']] = trim((string)($row['fields'][$field['field_key']] ?? ''));
    }
    return stage_asset_import_row($payload, (int)($row['row_number'] ?? 0), $segmentId);
}

function stage_asset_import_row(array $input, int $rowNumber, ?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $categoryName = trim((string)($input['category'] ?? ''));
    $subcategoryEnabled = asset_subcategory_enabled($segmentId);
    $subcategoryName = $subcategoryEnabled ? trim((string)($input['subcategory'] ?? '')) : '';

    $categoryId = 0;
    foreach (get_asset_categories(false, $segmentId) as $category) {
        if (strcasecmp($category['name'], $categoryName) === 0) {
            $categoryId = (int)$category['id'];
            break;
        }
    }

    $subcategoryId = 0;
    if ($subcategoryEnabled && $categoryId > 0) {
        foreach (get_asset_subcategories($categoryId, false, $segmentId) as $subcategory) {
            if (strcasecmp($subcategory['name'], $subcategoryName) === 0) {
                $subcategoryId = (int)$subcategory['id'];
                break;
            }
        }
    }

    $fieldInputs = [];
    foreach (get_asset_fields(false, $segmentId) as $field) {
        if ((int)$field['is_import_enabled'] !== 1) {
            continue;
        }
        $fieldInputs[$field['field_key']] = $input[$field['field_key']] ?? '';
    }

    $validated = validate_asset_payload([
        'segment_id' => $segmentId,
        'category_id' => $categoryId,
        'subcategory_id' => $subcategoryId,
        'fields' => $fieldInputs,
    ], null, [], true);

    return [
        'row_number' => $rowNumber,
        'category' => $categoryName,
        'subcategory' => $subcategoryName,
        'category_id' => $categoryId,
        'subcategory_id' => $subcategoryId,
        'fields' => $fieldInputs,
        'errors' => $validated['errors'],
    ];
}

function restage_asset_import_rows(array $submittedRows): array
{
    $review = $_SESSION['asset_import_review'] ?? [];
    $segmentId = asset_normalize_segment_id((int)($review['segment_id'] ?? 0));
    $reviewRows = [];
    foreach ($submittedRows as $index => $row) {
        $row['row_number'] = (int)($row['row_number'] ?? ($index + 1));
        $reviewRows[] = validate_import_review_row($row, $segmentId);
    }
    if ($reviewRows) {
        $_SESSION['asset_import_review']['rows'] = $reviewRows;
    } else {
        unset($_SESSION['asset_import_review']);
    }
    return $reviewRows;
}

function commit_asset_import_review(array $user): array
{
    $review = $_SESSION['asset_import_review'] ?? null;
    if (!$review || empty($review['rows'])) {
        return ['saved' => 0, 'errors' => ['No staged import rows found.']];
    }
    $segmentId = asset_normalize_segment_id((int)($review['segment_id'] ?? 0));
    $_SESSION['asset_active_segment_id'] = $segmentId;

    $saved = 0;
    $errors = [];
    $invalidRows = [];
    $batchStmt = db()->prepare('INSERT INTO asset_import_batches (segment_id, office_type, office_id, uploaded_by, original_filename, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
    $ctx = current_office_context($user);
    if (!$ctx) {
        return ['saved' => 0, 'errors' => ['Office context not found.']];
    }
    $batchStmt->execute([$segmentId, $ctx['office_type'], $ctx['office_id'], (int)$user['id'], (string)($review['filename'] ?? 'import.xlsx'), 'completed']);
    $batchId = (int)db()->lastInsertId();

    $validRows = [];
    $seenUniqueValues = [];
    $importFieldMap = asset_field_map_for_segment(false, $segmentId);
    foreach ($review['rows'] as $row) {
        $restagedRow = validate_import_review_row($row, $segmentId);
        if (!empty($restagedRow['errors'])) {
            $invalidRows[] = $restagedRow;
            $errors[] = 'Row ' . $row['row_number'] . ' still has validation errors.';
            continue;
        }
        $validated = validate_asset_payload([
            'segment_id' => $segmentId,
            'category_id' => (int)$restagedRow['category_id'],
            'subcategory_id' => (int)$restagedRow['subcategory_id'],
            'fields' => $restagedRow['fields'] ?? [],
        ], null, [], true);
        if (!empty($validated['errors'])) {
            $restagedRow['errors'] = $validated['errors'];
            $invalidRows[] = $restagedRow;
            $errors[] = 'Row ' . $row['row_number'] . ' still has validation errors.';
            continue;
        }
        $batchErrors = [];
        validate_asset_unique_values_within_batch($importFieldMap, $validated['payload']['field_values'] ?? [], $seenUniqueValues, $batchErrors);
        if (!empty($batchErrors)) {
            $restagedRow['errors'] = $batchErrors;
            $invalidRows[] = $restagedRow;
            $errors[] = 'Row ' . $row['row_number'] . ' still has validation errors.';
            continue;
        }
        $validRows[] = ['row' => $restagedRow, 'payload' => $validated['payload']];
    }

    if ($validRows) {
        db()->beginTransaction();
        try {
            foreach ($validRows as $item) {
                persist_asset_record($item['payload'], $user);
                $saved++;
            }
            db()->prepare('UPDATE asset_import_batches SET imported_count = ?, skipped_count = ?, updated_at = NOW() WHERE id = ?')->execute([$saved, count($invalidRows), $batchId]);
            db()->commit();
        } catch (Throwable $e) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            foreach ($validRows as $item) {
                $failedRow = $item['row'];
                $failedRow['errors'] = ['_db' => 'Database save failed for this row.'];
                $invalidRows[] = $failedRow;
            }
            $saved = 0;
            $errors[] = 'Database save failed. No rows were imported. ' . $e->getMessage();
        }
    } else {
        db()->prepare('UPDATE asset_import_batches SET imported_count = ?, skipped_count = ?, updated_at = NOW() WHERE id = ?')->execute([$saved, count($invalidRows), $batchId]);
    }

    if ($invalidRows) {
        $_SESSION['asset_import_review']['rows'] = $invalidRows;
    } else {
        unset($_SESSION['asset_import_review']);
    }
    if ($saved > 0 && !$errors) {
        add_log((int)$user['id'], 'asset_import_batches', $batchId, 'Bulk asset import completed.');
    }
    return ['saved' => $saved, 'errors' => $errors, 'remaining' => count($invalidRows)];
}
