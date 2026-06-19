<?php

function ensure_asset_schema(): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;
    if (asset_schema_cache_matches()) {
        return;
    }

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
            mandatory_scope TINYINT NOT NULL DEFAULT 0,
            field_information LONGTEXT DEFAULT NULL,
            video_tutorial_url VARCHAR(1000) DEFAULT NULL,
            number_format_rule VARCHAR(30) DEFAULT NULL,
            text_max_length INT DEFAULT NULL,
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
        "CREATE TABLE IF NOT EXISTS segment_office_scope_visibility (
            id INT AUTO_INCREMENT PRIMARY KEY,
            segment_id INT NOT NULL,
            office_type TINYINT NOT NULL,
            show_my_office TINYINT NOT NULL DEFAULT 1,
            show_office_under_me TINYINT NOT NULL DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            UNIQUE KEY uniq_segment_office_scope_visibility (segment_id, office_type)
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

    db()->exec(
        "CREATE TABLE IF NOT EXISTS bimh_data (
            bimh_id VARCHAR(100) NOT NULL PRIMARY KEY,
            establishment_name VARCHAR(500) DEFAULT NULL,
            project_name TEXT DEFAULT NULL,
            concerned_ministry VARCHAR(255) DEFAULT NULL,
            establishment_type VARCHAR(255) DEFAULT NULL,
            constructed_by VARCHAR(255) DEFAULT NULL,
            division_name VARCHAR(255) DEFAULT NULL,
            district VARCHAR(255) DEFAULT NULL,
            upazila_thana VARCHAR(255) DEFAULT NULL,
            union_ward VARCHAR(255) DEFAULT NULL,
            pwd_civil_zone VARCHAR(255) DEFAULT NULL,
            pwd_civil_circle VARCHAR(255) DEFAULT NULL,
            pwd_civil_division VARCHAR(255) DEFAULT NULL,
            pwd_civil_subdivision VARCHAR(255) DEFAULT NULL,
            pwd_mechanical_zone VARCHAR(255) DEFAULT NULL,
            pwd_mechanical_circle VARCHAR(255) DEFAULT NULL,
            pwd_mechanical_division VARCHAR(255) DEFAULT NULL,
            pwd_mechanical_subdivision VARCHAR(255) DEFAULT NULL,
            latitude VARCHAR(100) DEFAULT NULL,
            longitude VARCHAR(100) DEFAULT NULL,
            structural_drawing_id VARCHAR(255) DEFAULT NULL,
            architectural_drawing_id VARCHAR(255) DEFAULT NULL,
            year_of_construction VARCHAR(50) DEFAULT NULL,
            approximately VARCHAR(255) DEFAULT NULL,
            uses_of_establishment TEXT DEFAULT NULL,
            civil_other_information TEXT DEFAULT NULL,
            establishment_height VARCHAR(100) DEFAULT NULL,
            boundary_height VARCHAR(100) DEFAULT NULL,
            boundary_length VARCHAR(100) DEFAULT NULL,
            drainage_length VARCHAR(100) DEFAULT NULL,
            park_area VARCHAR(100) DEFAULT NULL,
            road_length VARCHAR(100) DEFAULT NULL,
            road_area VARCHAR(100) DEFAULT NULL,
            above_ground VARCHAR(100) DEFAULT NULL,
            under_ground VARCHAR(100) DEFAULT NULL,
            plinth_area VARCHAR(100) DEFAULT NULL,
            total_floor_area VARCHAR(100) DEFAULT NULL,
            structure_type VARCHAR(255) DEFAULT NULL,
            foundation_type VARCHAR(255) DEFAULT NULL,
            foundation_design_for TEXT DEFAULT NULL,
            details TEXT DEFAULT NULL,
            lift_no VARCHAR(100) DEFAULT NULL,
            ac_no VARCHAR(100) DEFAULT NULL,
            ac_capacity VARCHAR(100) DEFAULT NULL,
            motor_no VARCHAR(100) DEFAULT NULL,
            motor_capacity VARCHAR(100) DEFAULT NULL,
            substation_no VARCHAR(100) DEFAULT NULL,
            substation_capacity VARCHAR(100) DEFAULT NULL,
            generator_no VARCHAR(100) DEFAULT NULL,
            generator_capacity VARCHAR(100) DEFAULT NULL,
            fire_detection_system VARCHAR(255) DEFAULT NULL,
            fire_protection_system VARCHAR(255) DEFAULT NULL,
            em_other_info TEXT DEFAULT NULL,
            pwd_civil_division_key VARCHAR(255) DEFAULT NULL,
            pwd_mechanical_division_key VARCHAR(255) DEFAULT NULL,
            address_key VARCHAR(255) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL,
            KEY idx_bimh_civil_division_key (pwd_civil_division_key),
            KEY idx_bimh_mechanical_division_key (pwd_mechanical_division_key),
            KEY idx_bimh_address_key (address_key),
            KEY idx_bimh_establishment_name (establishment_name(191))
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
    asset_ensure_column('info', 'ui_theme_key', 'VARCHAR(50) DEFAULT NULL');
    asset_ensure_column('info', 'asset_subcategory_enabled', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('info', 'asset_number_visible_to_users', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('info', 'asset_filter_distinct_threshold', 'INT NOT NULL DEFAULT 20');
    asset_ensure_column('info', 'download_default_filters_json', 'LONGTEXT DEFAULT NULL');
    asset_ensure_column('info', 'download_naming_tokens_text', 'LONGTEXT DEFAULT NULL');
    asset_ensure_column('segments', 'asset_subcategory_enabled', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('segments', 'show_office_scope_switch', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('segments', 'show_filter_card', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('segments', 'show_filter_card_superadmin', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('segments', 'show_filter_card_users', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('segments', 'allow_bulk_import', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('segments', 'asset_number_visible_to_users', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('segments', 'show_data_provider_superadmin', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('segments', 'template_source', "VARCHAR(20) NOT NULL DEFAULT 'autogenerated'");
    asset_ensure_column('segments', 'download_filter_configured', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('segments', 'download_sort_configured', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('segments', 'download_token_configured', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'is_unique', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'is_filter_enabled', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'filter_scope', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'mandatory_scope', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'field_information', 'LONGTEXT DEFAULT NULL');
    asset_ensure_column('asset_fields', 'video_tutorial_url', 'VARCHAR(1000) DEFAULT NULL');
    asset_ensure_column('asset_fields', 'number_format_rule', 'VARCHAR(30) DEFAULT NULL');
    asset_ensure_column('asset_fields', 'text_max_length', 'INT DEFAULT NULL');
    asset_ensure_column('asset_fields', 'secondary_of_field_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_fields', 'conditional_map_json', 'LONGTEXT DEFAULT NULL');
    asset_ensure_column('asset_fields', 'is_common_download_field', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'is_download_level1', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'is_download_filter', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'is_download_sort', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'is_download_zip_file_selectable', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_fields', 'is_download_token', 'TINYINT NOT NULL DEFAULT 0');
    asset_ensure_column('asset_categories', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_subcategories', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_fields', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('assets', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('office_asset_declarations', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_import_batches', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_activity_logs', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('asset_table_column_preferences', 'segment_id', 'INT DEFAULT NULL');
    asset_ensure_column('bimh_data', 'pwd_civil_division_key', 'VARCHAR(255) DEFAULT NULL');
    asset_ensure_column('bimh_data', 'pwd_mechanical_division_key', 'VARCHAR(255) DEFAULT NULL');
    asset_ensure_column('bimh_data', 'address_key', 'VARCHAR(255) DEFAULT NULL');
    asset_ensure_segment_indexes();
    asset_ensure_index('bimh_data', 'idx_bimh_civil_division_key', ['pwd_civil_division_key']);
    asset_ensure_index('bimh_data', 'idx_bimh_mechanical_division_key', ['pwd_mechanical_division_key']);
    asset_ensure_index('bimh_data', 'idx_bimh_address_key', ['address_key']);
    asset_relax_category_requirement();
    asset_relax_subcategory_requirement();
    asset_backfill_segment_assignments();
    asset_backfill_filter_scopes();
    asset_backfill_mandatory_scopes();
    asset_bimh_backfill_helper_keys();

    asset_seed_default_fields();
    asset_backfill_office_user_access_levels();
    asset_mark_schema_cache_ready();
}

function asset_schema_version(): string
{
    return '2026-06-19-1';
}

function asset_schema_cache_file(): string
{
    return __DIR__ . '/../../storage/cache/asset_schema_version.txt';
}

function asset_schema_cache_matches(): bool
{
    $file = asset_schema_cache_file();
    if (!is_file($file)) {
        return false;
    }
    return trim((string)file_get_contents($file)) === asset_schema_version();
}

function asset_mark_schema_cache_ready(): void
{
    $file = asset_schema_cache_file();
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    @file_put_contents($file, asset_schema_version());
}

function asset_ensure_column(string $table, string $column, string $definition): void
{
    $stmt = db()->prepare('SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
    $stmt->execute([$table, $column]);
    if ($stmt->fetch()) {
        return;
    }
    try {
        db()->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    } catch (PDOException $e) {
        if ((string)$e->getCode() !== '42S21' && (int)($e->errorInfo[1] ?? 0) !== 1060) {
            throw $e;
        }
    }
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

function create_asset_segment(string $segmentName, ?int $sortOrder = null): int
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
    $sortOrder = $sortOrder !== null && $sortOrder > 0
        ? $sortOrder
        : ((int)db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM segments')->fetchColumn()) + 10;
    $insert = db()->prepare('INSERT INTO segments (segment_name, active_status, sort_order, created_at) VALUES (?, 1, ?, NOW())');
    $insert->execute([$segmentName, $sortOrder]);
    return (int)db()->lastInsertId();
}

function update_asset_segment(int $segmentId, string $segmentName, ?int $sortOrder = null): void
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
    $normalizedSortOrder = $sortOrder !== null && $sortOrder > 0
        ? $sortOrder
        : (int)((get_asset_segment($segmentId)['sort_order'] ?? 0) ?: next_sort_order_for_filters('segments'));
    db()->prepare('UPDATE segments SET segment_name = ?, sort_order = ?, updated_at = NOW() WHERE id = ?')->execute([$segmentName, $normalizedSortOrder, $segmentId]);
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

function asset_relax_category_requirement(): void
{
    $stmt = db()->prepare('SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1');
    $stmt->execute(['assets', 'category_id']);
    $isNullable = strtoupper((string)$stmt->fetchColumn());
    if ($isNullable !== 'YES') {
        db()->exec('ALTER TABLE assets MODIFY COLUMN category_id INT DEFAULT NULL');
    }
}

function asset_backfill_filter_scopes(): void
{
    db()->exec('UPDATE asset_fields SET filter_scope = 2 WHERE (filter_scope IS NULL OR filter_scope = 0) AND is_filter_enabled = 1');
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
    return ['text', 'number', 'date', 'dropdown', 'yes_no', 'file', 'conditional', 'bimh'];
}

function asset_mandatory_scope_optional(): int
{
    return 0;
}

function asset_mandatory_scope_input(): int
{
    return 1;
}

function asset_mandatory_scope_final_submission(): int
{
    return 2;
}

function asset_mandatory_scope_options(): array
{
    return [
        asset_mandatory_scope_optional() => 'Optional',
        asset_mandatory_scope_input() => 'Mandatory at Input',
        asset_mandatory_scope_final_submission() => 'Mandatory at Final Submission',
    ];
}

function asset_normalize_mandatory_scope(int|string|null $value): int
{
    $scope = (int)$value;
    if (!in_array($scope, [
        asset_mandatory_scope_optional(),
        asset_mandatory_scope_input(),
        asset_mandatory_scope_final_submission(),
    ], true)) {
        return asset_mandatory_scope_optional();
    }
    return $scope;
}

function asset_field_mandatory_scope(array $field): int
{
    if (array_key_exists('mandatory_scope', $field)) {
        return asset_normalize_mandatory_scope($field['mandatory_scope']);
    }
    return (int)($field['is_required'] ?? 0) === 1
        ? asset_mandatory_scope_input()
        : asset_mandatory_scope_optional();
}

function asset_is_input_required(array $field): bool
{
    return asset_field_mandatory_scope($field) === asset_mandatory_scope_input();
}

function asset_is_final_submission_required(array $field): bool
{
    return asset_field_mandatory_scope($field) === asset_mandatory_scope_final_submission();
}

function asset_backfill_mandatory_scopes(): void
{
    db()->exec('UPDATE asset_fields SET mandatory_scope = 1 WHERE (mandatory_scope IS NULL OR mandatory_scope = 0) AND is_required = 1');
}

function asset_label_for_submission_message(array $field): string
{
    $label = trim((string)($field['label'] ?? $field['field_key'] ?? 'Field'));
    if ($label === '') {
        return 'Field';
    }
    $parts = preg_split('/\s*\/\s*/u', $label);
    return trim((string)($parts[0] ?? $label));
}

function asset_quote_label_list(array $labels): string
{
    $labels = array_values(array_filter(array_map(static fn($label): string => trim((string)$label), $labels), static fn(string $label): bool => $label !== ''));
    $labels = array_values(array_unique($labels));
    if (!$labels) {
        return '"Required field"';
    }
    $quoted = array_map(static fn(string $label): string => '"' . $label . '"', $labels);
    $count = count($quoted);
    if ($count === 1) {
        return $quoted[0];
    }
    if ($count === 2) {
        return $quoted[0] . ' and ' . $quoted[1];
    }
    $last = array_pop($quoted);
    return implode(', ', $quoted) . ', and ' . $last;
}

function asset_theme_options(): array
{
    return [
        'ocean_blue' => 'Ocean Blue',
        'slate' => 'Slate',
        'emerald' => 'Emerald',
        'teal' => 'Teal',
        'indigo' => 'Indigo',
        'amber' => 'Amber',
        'crimson' => 'Crimson',
        'graphite' => 'Graphite',
    ];
}

function asset_default_theme_key(): string
{
    return 'ocean_blue';
}

function asset_normalize_theme_key(?string $value): string
{
    $key = trim((string)$value);
    $options = asset_theme_options();
    return array_key_exists($key, $options) ? $key : asset_default_theme_key();
}

function asset_normalize_help_text(?string $value): string
{
    return trim((string)$value);
}

function asset_normalize_tutorial_url(?string $value): string
{
    return trim((string)$value);
}

function asset_is_valid_tutorial_url(?string $value): bool
{
    $url = asset_normalize_tutorial_url($value);
    return $url === '' || filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function asset_youtube_embed_url(?string $value): ?string
{
    $url = asset_normalize_tutorial_url($value);
    if ($url === '') {
        return null;
    }
    $parts = parse_url($url);
    if (!$parts || empty($parts['host'])) {
        return null;
    }
    $host = strtolower((string)$parts['host']);
    $videoId = '';
    if (str_contains($host, 'youtu.be')) {
        $videoId = trim((string)($parts['path'] ?? ''), '/');
    } elseif (str_contains($host, 'youtube.com')) {
        parse_str((string)($parts['query'] ?? ''), $query);
        $videoId = trim((string)($query['v'] ?? ''));
        if ($videoId === '' && !empty($parts['path']) && preg_match('#/embed/([^/?]+)#', (string)$parts['path'], $matches)) {
            $videoId = trim((string)($matches[1] ?? ''));
        }
    }
    if ($videoId === '') {
        return null;
    }
    return 'https://www.youtube.com/embed/' . rawurlencode($videoId);
}

function asset_field_has_help(array $field): bool
{
    return asset_normalize_help_text((string)($field['field_information'] ?? '')) !== ''
        || asset_normalize_tutorial_url((string)($field['video_tutorial_url'] ?? '')) !== '';
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

function asset_normalize_conditional_option_value(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    return preg_replace('/\s+/u', '_', $value) ?? $value;
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

function asset_text_max_length(array $field): ?int
{
    $value = (int)($field['text_max_length'] ?? 0);
    return $value > 0 ? $value : null;
}

function asset_format_number_display(?string $value): string
{
    $value = $value === null ? '' : trim((string)$value);
    if ($value === '') {
        return '';
    }
    if (!str_contains($value, '.')) {
        return $value;
    }
    return rtrim(rtrim($value, '0'), '.');
}

function asset_bimh_workbook_path(): ?string
{
    $root = dirname(__DIR__, 2);
    $candidates = [
        asset_bimh_uploaded_path(),
        $root . '/[edited] Establishment All Bangladesh.xlsx',
        $root . '/Establishment All Bangladesh.xlsx',
    ];
    foreach ($candidates as $path) {
        if (is_file($path)) {
            return $path;
        }
    }
    return null;
}

function asset_bimh_storage_dir(): string
{
    return dirname(__DIR__, 2) . '/storage/bimh';
}

function asset_bimh_uploaded_path(): string
{
    return asset_bimh_storage_dir() . '/bimh_source_latest.xlsx';
}

function asset_bimh_normalize_division_key(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    $parts = explode(',', $value, 2);
    $value = trim((string)($parts[0] ?? ''));
    $value = mb_strtolower($value, 'UTF-8');
    return preg_replace('/[^[:alnum:]]+/u', '', $value) ?? '';
}

function asset_bimh_normalize_address_key(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    $parts = explode(',', $value, 2);
    $value = trim((string)($parts[1] ?? ''));
    if ($value === '') {
        return '';
    }
    $value = mb_strtolower($value, 'UTF-8');
    return preg_replace('/[^[:alnum:]]+/u', '', $value) ?? '';
}

function asset_bimh_normalize_scope_address_key(?string $value): string
{
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    $value = mb_strtolower($value, 'UTF-8');
    return preg_replace('/[^[:alnum:]]+/u', '', $value) ?? '';
}

function asset_bimh_build_address_key(?string $civilDivision, ?string $mechanicalDivision): string
{
    $civilKey = asset_bimh_normalize_address_key($civilDivision);
    if ($civilKey !== '') {
        return $civilKey;
    }
    return asset_bimh_normalize_address_key($mechanicalDivision);
}

function asset_bimh_backfill_helper_keys(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    try {
        $rows = db()->query('SELECT bimh_id, pwd_civil_division, pwd_mechanical_division, pwd_civil_division_key, pwd_mechanical_division_key, address_key FROM bimh_data')->fetchAll();
        if (!$rows) {
            return;
        }

        $stmt = db()->prepare('UPDATE bimh_data SET pwd_civil_division_key = ?, pwd_mechanical_division_key = ?, address_key = ? WHERE bimh_id = ?');
        db()->beginTransaction();
        foreach ($rows as $row) {
            $civilKey = asset_bimh_normalize_division_key((string)($row['pwd_civil_division'] ?? ''));
            $mechanicalKey = asset_bimh_normalize_division_key((string)($row['pwd_mechanical_division'] ?? ''));
            $addressKey = asset_bimh_build_address_key(
                $row['pwd_civil_division'] ?? null,
                $row['pwd_mechanical_division'] ?? null
            );

            if (
                (string)($row['pwd_civil_division_key'] ?? '') === $civilKey
                && (string)($row['pwd_mechanical_division_key'] ?? '') === $mechanicalKey
                && (string)($row['address_key'] ?? '') === $addressKey
            ) {
                continue;
            }

            $stmt->execute([$civilKey !== '' ? $civilKey : null, $mechanicalKey !== '' ? $mechanicalKey : null, $addressKey !== '' ? $addressKey : null, (string)$row['bimh_id']]);
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
    }
}

function asset_bimh_sheet_column_map(): array
{
    return [
        'BIMH ID' => 'bimh_id',
        'Establishment Name' => 'establishment_name',
        'Project Name' => 'project_name',
        'Concerned Ministry' => 'concerned_ministry',
        'Establishment Type' => 'establishment_type',
        'Constructed By' => 'constructed_by',
        'Division' => 'division_name',
        'District' => 'district',
        'Upazila/Thana' => 'upazila_thana',
        'Union/Ward' => 'union_ward',
        'PWD Civil Zone' => 'pwd_civil_zone',
        'PWD Civil Circle' => 'pwd_civil_circle',
        'PWD Civil Division' => 'pwd_civil_division',
        'PWD Civil Subdivision' => 'pwd_civil_subdivision',
        'PWD Mechanical Zone' => 'pwd_mechanical_zone',
        'PWD Mechanical Circle' => 'pwd_mechanical_circle',
        'PWD Mechanical Division' => 'pwd_mechanical_division',
        'PWD Mechanical Subdivision' => 'pwd_mechanical_subdivision',
        'Latitude' => 'latitude',
        'Longitude' => 'longitude',
        'Structural Drawing ID' => 'structural_drawing_id',
        'Architectural Drawing ID' => 'architectural_drawing_id',
        'Year Of Construction' => 'year_of_construction',
        'Approximately' => 'approximately',
        'Uses Of Establishment' => 'uses_of_establishment',
        'Civil Other Information' => 'civil_other_information',
        'Establishment Height' => 'establishment_height',
        'Boundary Height' => 'boundary_height',
        'Boundary Length' => 'boundary_length',
        'Drainage Length' => 'drainage_length',
        'Park Area' => 'park_area',
        'Road Length' => 'road_length',
        'Road Area' => 'road_area',
        'Above Ground' => 'above_ground',
        'Under Ground' => 'under_ground',
        'Plinth Area' => 'plinth_area',
        'Total Floor Area' => 'total_floor_area',
        'Structure Type' => 'structure_type',
        'Foundation Type' => 'foundation_type',
        'Foundation Design For' => 'foundation_design_for',
        'Details' => 'details',
        'Lift No' => 'lift_no',
        'AC No' => 'ac_no',
        'AC Capacity' => 'ac_capacity',
        'Motor No' => 'motor_no',
        'Motor Capacity' => 'motor_capacity',
        'Substation No' => 'substation_no',
        'Substation Capacity' => 'substation_capacity',
        'Generator No' => 'generator_no',
        'Generator Capacity' => 'generator_capacity',
        'Fire Detection System' => 'fire_detection_system',
        'Fire Protection System' => 'fire_protection_system',
        'E/M Other Info' => 'em_other_info',
    ];
}

function asset_bimh_sync_from_workbook(?string $path = null): array
{
    $path = $path && is_file($path) ? $path : asset_bimh_workbook_path();
    if (!$path || !is_file($path)) {
        throw new RuntimeException('BIMH workbook file was not found.');
    }

    ensure_library('PhpOffice\\PhpSpreadsheet\\IOFactory', 'PhpSpreadsheet is not installed.');
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    if (method_exists($reader, 'setReadEmptyCells')) {
        $reader->setReadEmptyCells(false);
    }
    $spreadsheet = $reader->load($path);
    $sheet = $spreadsheet->getSheetByName('Sheet1') ?: $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, false, false, false);
    if (!$rows) {
        throw new RuntimeException('BIMH workbook is empty.');
    }

    $headers = array_map(static fn($value): string => trim((string)$value), array_values($rows[0] ?? []));
    $columnMap = asset_bimh_sheet_column_map();
    $headerIndexes = [];
    foreach ($headers as $index => $header) {
        if (isset($columnMap[$header])) {
            $headerIndexes[$columnMap[$header]] = $index;
        }
    }
    if (!array_key_exists('bimh_id', $headerIndexes) || !array_key_exists('establishment_name', $headerIndexes)) {
        throw new RuntimeException('BIMH workbook Sheet1 headers do not match the expected structure.');
    }

    $dbColumns = array_values($columnMap);
    $dbColumns[] = 'pwd_civil_division_key';
    $dbColumns[] = 'pwd_mechanical_division_key';
    $dbColumns[] = 'address_key';
    $dbColumns[] = 'updated_at';
    $placeholderSql = implode(', ', array_fill(0, count($dbColumns), '?'));
    $updateSql = implode(', ', array_map(static fn(string $column): string => $column . ' = VALUES(' . $column . ')', array_filter($dbColumns, static fn(string $column): bool => $column !== 'bimh_id')));
    $stmt = db()->prepare('INSERT INTO bimh_data (' . implode(', ', $dbColumns) . ') VALUES (' . $placeholderSql . ') ON DUPLICATE KEY UPDATE ' . $updateSql);
    $existingIds = array_fill_keys(array_map(
        static fn(array $row): string => (string)$row['bimh_id'],
        db()->query('SELECT bimh_id FROM bimh_data')->fetchAll()
    ), true);

    $imported = 0;
    $inserted = 0;
    $updated = 0;
    db()->beginTransaction();
    try {
        foreach (array_slice($rows, 1) as $row) {
            $record = [];
            foreach ($columnMap as $excelHeader => $dbColumn) {
                $columnIndex = $headerIndexes[$dbColumn] ?? null;
                $record[$dbColumn] = $columnIndex === null ? null : trim((string)($row[$columnIndex] ?? ''));
                if ($record[$dbColumn] === '') {
                    $record[$dbColumn] = null;
                }
            }
            $bimhId = trim((string)($record['bimh_id'] ?? ''));
            if ($bimhId === '') {
                continue;
            }
            $wasExisting = isset($existingIds[$bimhId]);
            $record['bimh_id'] = $bimhId;
            $record['pwd_civil_division_key'] = asset_bimh_normalize_division_key((string)($record['pwd_civil_division'] ?? ''));
            $record['pwd_mechanical_division_key'] = asset_bimh_normalize_division_key((string)($record['pwd_mechanical_division'] ?? ''));
            $record['address_key'] = asset_bimh_build_address_key(
                $record['pwd_civil_division'] ?? null,
                $record['pwd_mechanical_division'] ?? null
            );
            $record['updated_at'] = date('Y-m-d H:i:s');
            $params = [];
            foreach ($dbColumns as $column) {
                $params[] = $record[$column] ?? null;
            }
            $stmt->execute($params);
            $imported++;
            if ($wasExisting) {
                $updated++;
            } else {
                $inserted++;
                $existingIds[$bimhId] = true;
            }
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }

    return ['imported' => $imported, 'inserted' => $inserted, 'updated' => $updated, 'path' => $path];
}

function asset_bimh_bootstrap_if_empty(): void
{
    static $bootstrapped = false;
    if ($bootstrapped) {
        return;
    }
    $bootstrapped = true;
    try {
        $count = (int)db()->query('SELECT COUNT(*) FROM bimh_data')->fetchColumn();
        if ($count > 0) {
            return;
        }
        $path = asset_bimh_workbook_path();
        if (!$path) {
            return;
        }
        asset_bimh_sync_from_workbook($path);
    } catch (Throwable $e) {
    }
}

function save_uploaded_bimh_workbook(array $file): array
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Please choose a valid BIMH Excel file.');
    }
    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'xls'], true)) {
        throw new RuntimeException('BIMH file must be an Excel file.');
    }

    $summary = asset_bimh_sync_from_workbook((string)$file['tmp_name']);

    $dir = asset_bimh_storage_dir();
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create BIMH storage directory.');
    }
    $target = asset_bimh_uploaded_path();
    if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
        throw new RuntimeException('BIMH data imported but the uploaded source file could not be saved.');
    }

    return $summary + ['stored_path' => $target];
}

function asset_bimh_lookup_many(array $ids): array
{
    asset_bimh_bootstrap_if_empty();
    $normalizedIds = [];
    foreach ($ids as $id) {
        $value = trim((string)$id);
        if ($value !== '') {
            $normalizedIds[$value] = $value;
        }
    }
    if (!$normalizedIds) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($normalizedIds), '?'));
    $stmt = db()->prepare("SELECT bimh_id, establishment_name FROM bimh_data WHERE bimh_id IN ({$placeholders})");
    $stmt->execute(array_values($normalizedIds));
    $map = [];
    foreach ($stmt->fetchAll() as $row) {
        $map[(string)$row['bimh_id']] = trim((string)($row['establishment_name'] ?? ''));
    }
    return $map;
}

function asset_bimh_lookup(string $bimhId): ?array
{
    $bimhId = trim($bimhId);
    if ($bimhId === '') {
        return null;
    }
    asset_bimh_bootstrap_if_empty();
    $stmt = db()->prepare('SELECT * FROM bimh_data WHERE bimh_id = ? LIMIT 1');
    $stmt->execute([$bimhId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function asset_bimh_est_name_for_id(?string $bimhId): string
{
    $bimhId = trim((string)$bimhId);
    if ($bimhId === '') {
        return '';
    }
    $row = asset_bimh_lookup($bimhId);
    if (!$row) {
        return 'BIMH ID is not in the Database.';
    }
    return trim((string)($row['establishment_name'] ?? '')) ?: 'BIMH ID is not in the Database.';
}

function asset_bimh_accessible_divisions(array $user): array
{
    $officeType = (int)($user['office_type'] ?? 0);
    if (is_superadmin()) {
        return db()->query('SELECT id, office_name, office_address, zone_id, circle_id FROM divisions ORDER BY office_name ASC')->fetchAll();
    }
    if ($officeType === 5 && !empty($user['subdivision_id'])) {
        $subdivision = find_subdivision_with_hierarchy((int)$user['subdivision_id']);
        if (!$subdivision || empty($subdivision['division_id'])) {
            return [];
        }
        $stmt = db()->prepare('SELECT id, office_name, office_address, zone_id, circle_id FROM divisions WHERE id = ?');
        $stmt->execute([(int)$subdivision['division_id']]);
        return $stmt->fetchAll();
    }
    return get_divisions_for_user($user);
}

function asset_bimh_picker_scope(array $user): array
{
    return match ((int)($user['office_type'] ?? 0)) {
        2 => ['show_circle_filter' => true, 'show_division_filter' => true],
        3 => ['show_circle_filter' => false, 'show_division_filter' => true],
        default => ['show_circle_filter' => false, 'show_division_filter' => false],
    };
}

function asset_bimh_picker_rows(array $user): array
{
    asset_bimh_bootstrap_if_empty();
    $divisions = asset_bimh_accessible_divisions($user);
    if (!$divisions) {
        return [];
    }

    $divisionKeyMap = [];
    $addressDivisionKeyMap = [];
    $addressKeys = [];
    $blankAddressDivisionKeys = [];
    foreach ($divisions as $division) {
        $divisionKey = asset_bimh_normalize_division_key((string)($division['office_name'] ?? ''));
        if ($divisionKey === '') {
            continue;
        }
        $divisionMeta = [
            'division_id' => (int)($division['id'] ?? 0),
            'division_name' => (string)($division['office_name'] ?? ''),
            'circle_id' => (int)($division['circle_id'] ?? 0),
            'zone_id' => (int)($division['zone_id'] ?? 0),
            'address_key' => asset_bimh_normalize_scope_address_key((string)($division['office_address'] ?? '')),
        ];
        $divisionKeyMap[$divisionKey] = $divisionMeta;
        if ($divisionMeta['address_key'] !== '') {
            $addressKeys[$divisionMeta['address_key']] = $divisionMeta['address_key'];
            $addressDivisionKeyMap[$divisionMeta['address_key']][$divisionKey] = $divisionMeta;
        } else {
            $blankAddressDivisionKeys[$divisionKey] = $divisionKey;
        }
    }
    if (!$divisionKeyMap) {
        return [];
    }

    $keys = array_keys($divisionKeyMap);
    $rows = [];
    $collectRows = static function (array $resultSet, string $source) use (&$rows, $addressDivisionKeyMap, $divisionKeyMap): void {
        foreach ($resultSet as $row) {
            $bimhId = trim((string)($row['bimh_id'] ?? ''));
            if ($bimhId === '') {
                continue;
            }
            if (isset($rows[$bimhId]) && $rows[$bimhId]['match_source'] === 'mechanical') {
                continue;
            }
            $rowAddressKey = trim((string)($row['address_key'] ?? ''));
            $matchedKey = trim((string)($row['matched_key'] ?? ''));
            $divisionMeta = null;
            if ($rowAddressKey !== '' && isset($addressDivisionKeyMap[$rowAddressKey][$matchedKey])) {
                $divisionMeta = $addressDivisionKeyMap[$rowAddressKey][$matchedKey];
            } elseif (isset($divisionKeyMap[$matchedKey])) {
                $divisionMeta = $divisionKeyMap[$matchedKey];
            }
            if (!$divisionMeta) {
                continue;
            }
            $rows[$bimhId] = [
                'bimh_id' => $bimhId,
                'est_name' => trim((string)($row['establishment_name'] ?? '')),
                'upazila_thana' => trim((string)($row['upazila_thana'] ?? '')),
                'district' => trim((string)($row['district'] ?? '')),
                'circle_id' => (int)($divisionMeta['circle_id'] ?? 0),
                'division_id' => (int)($divisionMeta['division_id'] ?? 0),
                'circle_name' => trim((string)($row['matched_circle'] ?? '')),
                'division_name' => trim((string)($divisionMeta['division_name'] ?? ($row['matched_division'] ?? ''))),
                'match_source' => $source,
            ];
        }
    };

    $runQuerySet = static function (string $divisionColumn, string $circleColumn, string $divisionKeyColumn, array $divisionKeys, ?array $filterAddressKeys = null): array {
        if (!$divisionKeys) {
            return [];
        }
        $divisionPlaceholders = implode(',', array_fill(0, count($divisionKeys), '?'));
        $params = array_values($divisionKeys);
        $where = "{$divisionKeyColumn} IN ({$divisionPlaceholders})";
        if ($filterAddressKeys !== null && $filterAddressKeys) {
            $addressPlaceholders = implode(',', array_fill(0, count($filterAddressKeys), '?'));
            $where .= " AND address_key IN ({$addressPlaceholders})";
            $params = array_merge($params, array_values($filterAddressKeys));
        }
        $sql = "SELECT bimh_id, establishment_name, upazila_thana, district, address_key, {$circleColumn} AS matched_circle, {$divisionColumn} AS matched_division, {$divisionKeyColumn} AS matched_key FROM bimh_data WHERE {$where} ORDER BY establishment_name ASC, bimh_id ASC";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    };

    $mechanicalRows = [];
    $civilRows = [];
    if ($addressKeys) {
        $mechanicalRows = array_merge(
            $mechanicalRows,
            $runQuerySet('pwd_mechanical_division', 'pwd_mechanical_circle', 'pwd_mechanical_division_key', $keys, $addressKeys)
        );
        $civilRows = array_merge(
            $civilRows,
            $runQuerySet('pwd_civil_division', 'pwd_civil_circle', 'pwd_civil_division_key', $keys, $addressKeys)
        );
    }
    if ($blankAddressDivisionKeys) {
        $fallbackKeys = array_values($blankAddressDivisionKeys);
        $mechanicalRows = array_merge(
            $mechanicalRows,
            $runQuerySet('pwd_mechanical_division', 'pwd_mechanical_circle', 'pwd_mechanical_division_key', $fallbackKeys)
        );
        $civilRows = array_merge(
            $civilRows,
            $runQuerySet('pwd_civil_division', 'pwd_civil_circle', 'pwd_civil_division_key', $fallbackKeys)
        );
    }

    foreach ([['mechanical', $mechanicalRows], ['civil', $civilRows]] as [$source, $resultSet]) {
        if (!is_array($resultSet)) {
            continue;
        }
        $collectRows($resultSet, $source);
    }

    uasort($rows, static function (array $a, array $b): int {
        $nameCompare = strnatcasecmp((string)($a['est_name'] ?? ''), (string)($b['est_name'] ?? ''));
        if ($nameCompare !== 0) {
            return $nameCompare;
        }
        return strnatcasecmp((string)($a['bimh_id'] ?? ''), (string)($b['bimh_id'] ?? ''));
    });

    return array_values($rows);
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
    if (asset_active_subcategory_count($normalizedSegmentId) <= 0) {
        return false;
    }
    $segment = get_asset_segment($normalizedSegmentId);
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

function asset_active_category_count(?int $segmentId = null): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM asset_categories WHERE segment_id = ? AND deleted_at IS NULL AND active_status = 1');
    $stmt->execute([asset_normalize_segment_id($segmentId)]);
    return (int)$stmt->fetchColumn();
}

function asset_active_subcategory_count(?int $segmentId = null): int
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM asset_subcategories WHERE segment_id = ? AND deleted_at IS NULL AND active_status = 1');
    $stmt->execute([asset_normalize_segment_id($segmentId)]);
    return (int)$stmt->fetchColumn();
}

function asset_category_selection_enabled(?int $segmentId = null): bool
{
    return asset_active_category_count($segmentId) > 1;
}

function asset_single_category_id(?int $segmentId = null): int
{
    $stmt = db()->prepare('SELECT id FROM asset_categories WHERE segment_id = ? AND deleted_at IS NULL AND active_status = 1 ORDER BY sort_order ASC, id ASC LIMIT 1');
    $stmt->execute([asset_normalize_segment_id($segmentId)]);
    return (int)($stmt->fetchColumn() ?: 0);
}

function asset_scope_switch_enabled(?int $segmentId = null): bool
{
    $segment = get_asset_segment(asset_normalize_segment_id($segmentId));
    return (int)($segment['show_office_scope_switch'] ?? 1) === 1;
}

function set_asset_scope_switch_enabled(int $status, ?int $segmentId = null): void
{
    db()->prepare('UPDATE segments SET show_office_scope_switch = ?, updated_at = NOW() WHERE id = ?')->execute([
        $status === 1 ? 1 : 0,
        asset_normalize_segment_id($segmentId),
    ]);
}

function asset_scope_visibility_default_for_office_type(int $officeType): array
{
    return [
        'show_my_office' => 1,
        'show_office_under_me' => in_array($officeType, [2, 3, 4], true) ? 1 : 0,
    ];
}

function asset_scope_visibility_office_types(): array
{
    return [2, 3, 4, 5];
}

function asset_ensure_scope_visibility_rows(?int $segmentId = null): void
{
    $normalizedSegmentId = asset_normalize_segment_id($segmentId);
    $stmt = db()->prepare(
        'INSERT INTO segment_office_scope_visibility (segment_id, office_type, show_my_office, show_office_under_me, created_at)
         VALUES (?, ?, ?, ?, NOW())
         ON DUPLICATE KEY UPDATE updated_at = updated_at'
    );
    foreach (asset_scope_visibility_office_types() as $officeType) {
        $defaults = asset_scope_visibility_default_for_office_type($officeType);
        $stmt->execute([
            $normalizedSegmentId,
            $officeType,
            $defaults['show_my_office'],
            $defaults['show_office_under_me'],
        ]);
    }
}

function asset_scope_visibility_settings(?int $segmentId = null): array
{
    $normalizedSegmentId = asset_normalize_segment_id($segmentId);
    asset_ensure_scope_visibility_rows($normalizedSegmentId);
    $stmt = db()->prepare('SELECT * FROM segment_office_scope_visibility WHERE segment_id = ? ORDER BY office_type ASC');
    $stmt->execute([$normalizedSegmentId]);
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[(int)$row['office_type']] = [
            'office_type' => (int)$row['office_type'],
            'show_my_office' => (int)($row['show_my_office'] ?? 0) === 1,
            'show_office_under_me' => (int)($row['show_office_under_me'] ?? 0) === 1,
        ];
    }
    foreach (asset_scope_visibility_office_types() as $officeType) {
        if (isset($settings[$officeType])) {
            continue;
        }
        $defaults = asset_scope_visibility_default_for_office_type($officeType);
        $settings[$officeType] = [
            'office_type' => $officeType,
            'show_my_office' => (bool)$defaults['show_my_office'],
            'show_office_under_me' => (bool)$defaults['show_office_under_me'],
        ];
    }
    ksort($settings);
    return $settings;
}

function asset_scope_visibility_for_office_type(int $officeType, ?int $segmentId = null): array
{
    $settings = asset_scope_visibility_settings($segmentId);
    if (isset($settings[$officeType])) {
        return $settings[$officeType];
    }
    $defaults = asset_scope_visibility_default_for_office_type($officeType);
    return [
        'office_type' => $officeType,
        'show_my_office' => (bool)$defaults['show_my_office'],
        'show_office_under_me' => (bool)$defaults['show_office_under_me'],
    ];
}

function save_asset_scope_visibility_settings(array $settings, ?int $segmentId = null): void
{
    $normalizedSegmentId = asset_normalize_segment_id($segmentId);
    asset_ensure_scope_visibility_rows($normalizedSegmentId);
    $stmt = db()->prepare(
        'INSERT INTO segment_office_scope_visibility (segment_id, office_type, show_my_office, show_office_under_me, created_at, updated_at)
         VALUES (?, ?, ?, ?, NOW(), NOW())
         ON DUPLICATE KEY UPDATE show_my_office = VALUES(show_my_office), show_office_under_me = VALUES(show_office_under_me), updated_at = NOW()'
    );
    foreach (asset_scope_visibility_office_types() as $officeType) {
        $row = $settings[$officeType] ?? [];
        $stmt->execute([
            $normalizedSegmentId,
            $officeType,
            !empty($row['show_my_office']) ? 1 : 0,
            !empty($row['show_office_under_me']) ? 1 : 0,
        ]);
    }
}

function asset_filter_card_enabled(?int $segmentId = null): bool
{
    $segment = get_asset_segment(asset_normalize_segment_id($segmentId));
    return (int)($segment['show_filter_card'] ?? 1) === 1;
}

function set_asset_filter_card_enabled(int $status, ?int $segmentId = null): void
{
    db()->prepare('UPDATE segments SET show_filter_card = ?, updated_at = NOW() WHERE id = ?')->execute([
        $status === 1 ? 1 : 0,
        asset_normalize_segment_id($segmentId),
    ]);
}

function asset_filter_card_enabled_for_superadmin(?int $segmentId = null): bool
{
    $segment = get_asset_segment(asset_normalize_segment_id($segmentId));
    if (!$segment) {
        return true;
    }
    if (array_key_exists('show_filter_card_superadmin', $segment)) {
        return (int)($segment['show_filter_card_superadmin'] ?? 1) === 1;
    }
    return (int)($segment['show_filter_card'] ?? 1) === 1;
}

function asset_filter_card_enabled_for_users(?int $segmentId = null): bool
{
    $segment = get_asset_segment(asset_normalize_segment_id($segmentId));
    if (!$segment) {
        return true;
    }
    if (array_key_exists('show_filter_card_users', $segment)) {
        return (int)($segment['show_filter_card_users'] ?? 1) === 1;
    }
    return (int)($segment['show_filter_card'] ?? 1) === 1;
}

function set_asset_filter_card_visibility(int $superadminStatus, int $userStatus, ?int $segmentId = null): void
{
    db()->prepare('UPDATE segments SET show_filter_card_superadmin = ?, show_filter_card_users = ?, show_filter_card = ?, updated_at = NOW() WHERE id = ?')->execute([
        $superadminStatus === 1 ? 1 : 0,
        $userStatus === 1 ? 1 : 0,
        ($superadminStatus === 1 || $userStatus === 1) ? 1 : 0,
        asset_normalize_segment_id($segmentId),
    ]);
}

function asset_bulk_import_enabled(?int $segmentId = null): bool
{
    $segment = get_asset_segment(asset_normalize_segment_id($segmentId));
    return (int)($segment['allow_bulk_import'] ?? 1) === 1;
}

function set_asset_bulk_import_enabled(int $status, ?int $segmentId = null): void
{
    db()->prepare('UPDATE segments SET allow_bulk_import = ?, updated_at = NOW() WHERE id = ?')->execute([
        $status === 1 ? 1 : 0,
        asset_normalize_segment_id($segmentId),
    ]);
}

function asset_number_visible_to_users(?int $segmentId = null): bool
{
    $normalizedSegmentId = asset_normalize_segment_id($segmentId);
    $segment = get_asset_segment($normalizedSegmentId);
    if ($segment && array_key_exists('asset_number_visible_to_users', $segment)) {
        return (int)($segment['asset_number_visible_to_users'] ?? 1) === 1;
    }
    $info = get_info_row();
    $value = $info['asset_number_visible_to_users'] ?? null;
    if ($value === null || $value === '') {
        return true;
    }
    return (int)$value === 1;
}

function set_asset_number_visible_to_users(int $status, ?int $segmentId = null): void
{
    db()->prepare('UPDATE segments SET asset_number_visible_to_users = ?, updated_at = NOW() WHERE id = ?')->execute([
        $status === 1 ? 1 : 0,
        asset_normalize_segment_id($segmentId),
    ]);
}

function asset_data_provider_visible(?int $segmentId = null): bool
{
    $segment = get_asset_segment(asset_normalize_segment_id($segmentId));
    return (int)($segment['show_data_provider_superadmin'] ?? 1) === 1;
}

function set_asset_data_provider_visible(int $status, ?int $segmentId = null): void
{
    db()->prepare('UPDATE segments SET show_data_provider_superadmin = ?, updated_at = NOW() WHERE id = ?')->execute([
        $status === 1 ? 1 : 0,
        asset_normalize_segment_id($segmentId),
    ]);
}

function asset_template_source_autogenerated(): string
{
    return 'autogenerated';
}

function asset_template_source_uploaded(): string
{
    return 'uploaded';
}

function asset_template_source_options(?int $segmentId = null): array
{
    $options = [
        asset_template_source_autogenerated() => 'Autogenerated Template',
    ];
    if (asset_template_uploaded_info($segmentId)) {
        $options[asset_template_source_uploaded()] = 'Uploaded Template';
    }
    return $options;
}

function asset_template_source(?int $segmentId = null): string
{
    $segment = get_asset_segment(asset_normalize_segment_id($segmentId));
    $source = strtolower(trim((string)($segment['template_source'] ?? asset_template_source_autogenerated())));
    if ($source !== asset_template_source_uploaded()) {
        return asset_template_source_autogenerated();
    }
    return asset_template_uploaded_info($segmentId)
        ? asset_template_source_uploaded()
        : asset_template_source_autogenerated();
}

function set_asset_template_source(string $source, ?int $segmentId = null): void
{
    $normalizedSource = strtolower(trim($source));
    if ($normalizedSource === asset_template_source_uploaded() && !asset_template_uploaded_info($segmentId)) {
        $normalizedSource = asset_template_source_autogenerated();
    }
    if ($normalizedSource !== asset_template_source_uploaded()) {
        $normalizedSource = asset_template_source_autogenerated();
    }
    db()->prepare('UPDATE segments SET template_source = ?, updated_at = NOW() WHERE id = ?')->execute([
        $normalizedSource,
        asset_normalize_segment_id($segmentId),
    ]);
}

function asset_template_prefers_uploaded(?int $segmentId = null): bool
{
    return asset_template_source($segmentId) === asset_template_source_uploaded();
}

function asset_filter_scope_none(): int
{
    return 0;
}

function asset_filter_scope_superadmin_only(): int
{
    return 1;
}

function asset_filter_scope_all(): int
{
    return 2;
}

function asset_filter_scope_options(): array
{
    return [
        asset_filter_scope_none() => 'No Filter',
        asset_filter_scope_superadmin_only() => 'Filter for superadmin only',
        asset_filter_scope_all() => 'Filter for all',
    ];
}

function asset_normalize_filter_scope(int|string|null $value): int
{
    $scope = (int)$value;
    if (!in_array($scope, [asset_filter_scope_none(), asset_filter_scope_superadmin_only(), asset_filter_scope_all()], true)) {
        return asset_filter_scope_none();
    }
    return $scope;
}

function asset_download_default_filter_options(): array
{
    return [
        'office_hierarchy' => 'Office hierarchy',
        'category' => 'Category',
        'subcategory' => 'Sub-category',
    ];
}

function asset_download_default_filters(): array
{
    $info = get_info_row() ?? [];
    $raw = (string)($info['download_default_filters_json'] ?? '');
    if ($raw === '') {
        return ['office_hierarchy', 'category', 'subcategory'];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['office_hierarchy', 'category', 'subcategory'];
    }
    $allowed = array_keys(asset_download_default_filter_options());
    $selected = [];
    foreach ($decoded as $value) {
        $value = (string)$value;
        if (in_array($value, $allowed, true)) {
            $selected[] = $value;
        }
    }
    return array_values(array_unique($selected));
}

function asset_download_available_naming_tokens(): array
{
    return [
        'office_name',
        'sub-division',
        'division',
        'circle',
        'zone',
        'segment',
        'field_name',
        'office_type',
        'asset_number',
    ];
}

function asset_download_token_key_from_label(string $label): string
{
    $token = strtolower(trim($label));
    $token = preg_replace('/[^a-z0-9]+/i', '_', $token);
    $token = trim((string)$token, '_');
    return $token !== '' ? $token : 'field';
}

function asset_download_declared_field_token_map(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $map = [];
    $used = [];
    foreach (asset_download_selected_token_labels() as $label) {
        if ($label === '' || isset($map[$label])) {
            continue;
        }
        $base = asset_download_token_key_from_label($label);
        $token = $base;
        $suffix = 2;
        while (isset($used[$token])) {
            $token = $base . '_' . $suffix;
            $suffix++;
        }
        $used[$token] = true;
        $map[$label] = $token;
    }
    return $cache = $map;
}

function asset_download_dynamic_naming_tokens(): array
{
    return array_values(asset_download_declared_field_token_map());
}

function asset_download_selected_token_labels(): array
{
    $labels = [];
    foreach (get_asset_segments(false) as $segment) {
        $segmentId = (int)$segment['id'];
        $selectedIds = array_flip(asset_download_segment_selected_field_ids($segmentId, 'token'));
        foreach (get_asset_fields(false, $segmentId) as $field) {
            $fieldId = (int)$field['id'];
            if (!isset($selectedIds[$fieldId])) {
                continue;
            }
            $label = trim((string)($field['label'] ?? ''));
            if ($label !== '') {
                $labels[$label] = true;
            }
        }
    }
    $result = array_keys($labels);
    sort($result, SORT_NATURAL | SORT_FLAG_CASE);
    return $result;
}

function asset_download_filename_template(): string
{
    $info = get_info_row() ?? [];
    $raw = trim((string)($info['download_naming_tokens_text'] ?? ''));
    if ($raw === '') {
        return '{segment}_{field_name}_{office_name}_{asset_number}';
    }
    return $raw;
}

function asset_download_naming_tokens(): array
{
    $raw = asset_download_filename_template();
    $allowed = array_unique(array_merge(
        asset_download_available_naming_tokens(),
        asset_download_dynamic_naming_tokens()
    ));
    if (str_contains($raw, '{')) {
        preg_match_all('/\{([a-z0-9_-]+)\}/i', $raw, $matches);
        $tokens = array_map('strtolower', array_map('trim', $matches[1] ?? []));
        return array_values(array_filter($tokens, static fn(string $token): bool => in_array($token, $allowed, true)));
    }
    $parts = preg_split('/[\r\n,]+/', $raw) ?: [];
    $tokens = [];
    foreach ($parts as $part) {
        $part = trim((string)$part);
        if ($part !== '' && in_array($part, $allowed, true)) {
            $tokens[] = $part;
        }
    }
    return $tokens ?: ['segment', 'field_name', 'office_name', 'asset_number'];
}

function save_asset_download_settings(array $selectedDefaultFilters, string $namingTokensText): void
{
    $allowedFilters = array_keys(asset_download_default_filter_options());
    $filters = [];
    foreach ($selectedDefaultFilters as $value) {
        $value = (string)$value;
        if (in_array($value, $allowedFilters, true)) {
            $filters[] = $value;
        }
    }
    $filters = array_values(array_unique($filters));

    $info = get_info_row() ?? [];
    save_info_row(
        (string)($info['video_tutorial_url'] ?? ''),
        (string)($info['login_message'] ?? ''),
        [
            'site_name' => $info['site_name'] ?? null,
            'welcome_message' => $info['welcome_message'] ?? null,
            'ui_theme_key' => $info['ui_theme_key'] ?? asset_default_theme_key(),
            'asset_subcategory_enabled' => $info['asset_subcategory_enabled'] ?? 1,
            'asset_number_visible_to_users' => $info['asset_number_visible_to_users'] ?? 1,
            'asset_filter_distinct_threshold' => $info['asset_filter_distinct_threshold'] ?? 20,
            'download_default_filters_json' => json_encode($filters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'download_naming_tokens_text' => trim($namingTokensText),
            'i_opr_repair' => $info['i_opr_repair'] ?? null,
            'i_opr_other' => $info['i_opr_other'] ?? null,
            'i_dev_pw' => $info['i_dev_pw'] ?? null,
            'i_opr_min' => $info['i_opr_min'] ?? null,
            'i_dev_min' => $info['i_dev_min'] ?? null,
            'i_opr' => $info['i_opr'] ?? null,
            'i_dev' => $info['i_dev'] ?? null,
        ]
    );
}

function save_asset_download_naming_template(string $template): void
{
    $template = trim($template);
    if ($template === '') {
        $template = '{segment}_{field_name}_{office_name}_{asset_number}';
    }
    $info = get_info_row() ?? [];
    save_info_row(
        (string)($info['video_tutorial_url'] ?? ''),
        (string)($info['login_message'] ?? ''),
        [
            'site_name' => $info['site_name'] ?? null,
            'welcome_message' => $info['welcome_message'] ?? null,
            'ui_theme_key' => $info['ui_theme_key'] ?? asset_default_theme_key(),
            'asset_subcategory_enabled' => $info['asset_subcategory_enabled'] ?? 1,
            'asset_number_visible_to_users' => $info['asset_number_visible_to_users'] ?? 1,
            'asset_filter_distinct_threshold' => $info['asset_filter_distinct_threshold'] ?? 20,
            'download_default_filters_json' => $info['download_default_filters_json'] ?? null,
            'download_naming_tokens_text' => $template,
            'i_opr_repair' => $info['i_opr_repair'] ?? null,
            'i_opr_other' => $info['i_opr_other'] ?? null,
            'i_dev_pw' => $info['i_dev_pw'] ?? null,
            'i_opr_min' => $info['i_opr_min'] ?? null,
            'i_dev_min' => $info['i_dev_min'] ?? null,
            'i_opr' => $info['i_opr'] ?? null,
            'i_dev' => $info['i_dev'] ?? null,
        ]
    );
}

function asset_download_common_fields(?int $segmentId = null, bool $includeInactive = false): array
{
    return array_values(array_filter(
        get_asset_fields($includeInactive, $segmentId),
        static fn(array $field): bool => (int)($field['is_common_download_field'] ?? 0) === 1
            && ($includeInactive || (int)($field['active_status'] ?? 0) === 1)
    ));
}

function asset_download_level1_fields(?int $segmentId = null, bool $includeInactive = false): array
{
    return array_values(array_filter(
        get_asset_fields($includeInactive, $segmentId),
        static fn(array $field): bool => (int)($field['is_common_download_field'] ?? 0) === 1
            && (int)($field['is_download_level1'] ?? 0) === 1
            && ($includeInactive || (int)($field['active_status'] ?? 0) === 1)
    ));
}

function asset_download_filter_fields(?int $segmentId = null, bool $includeInactive = false): array
{
    return array_values(array_filter(
        get_asset_fields($includeInactive, $segmentId),
        static fn(array $field): bool => (int)($field['is_download_filter'] ?? 0) === 1
            && ($includeInactive || (int)($field['active_status'] ?? 0) === 1)
    ));
}

function asset_download_effective_filter_fields(?int $segmentId = null, bool $includeInactive = false): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $selectedIds = array_flip(asset_download_segment_selected_field_ids($segmentId, 'filter'));
    if (!$selectedIds) {
        return [];
    }
    return array_values(array_filter(
        get_asset_fields($includeInactive, $segmentId),
        static fn(array $field): bool => isset($selectedIds[(int)$field['id']])
            && ($includeInactive || (int)($field['active_status'] ?? 0) === 1)
    ));
}

function asset_download_sort_fields(?int $segmentId = null, bool $includeInactive = false): array
{
    return array_values(array_filter(
        get_asset_fields($includeInactive, $segmentId),
        static fn(array $field): bool => (int)($field['is_download_sort'] ?? 0) === 1
            && ($includeInactive || (int)($field['active_status'] ?? 0) === 1)
    ));
}

function asset_download_zip_selectable_fields(?int $segmentId = null, bool $includeInactive = false): array
{
    return array_values(array_filter(
        get_asset_fields($includeInactive, $segmentId),
        static fn(array $field): bool => (string)($field['data_type'] ?? '') === 'file'
            && (int)($field['is_download_zip_file_selectable'] ?? 0) === 1
            && ($includeInactive || (int)($field['active_status'] ?? 0) === 1)
    ));
}

function asset_download_common_label_candidates(): array
{
    $segments = get_asset_segments(false);
    if (!$segments) {
        return [];
    }
    $segmentCount = count($segments);
    $labelMap = [];
    foreach ($segments as $segment) {
        $seen = [];
        foreach (get_asset_fields(false, (int)$segment['id']) as $field) {
            $label = trim((string)($field['label'] ?? ''));
            if ($label === '' || isset($seen[$label])) {
                continue;
            }
            $seen[$label] = true;
            if (!isset($labelMap[$label])) {
                $labelMap[$label] = 0;
            }
            $labelMap[$label]++;
        }
    }
    $labels = [];
    foreach ($labelMap as $label => $count) {
        if ($count === $segmentCount) {
            $labels[] = $label;
        }
    }
    sort($labels, SORT_NATURAL | SORT_FLAG_CASE);
    return $labels;
}

function asset_download_selected_level1_labels(): array
{
    $candidates = asset_download_common_label_candidates();
    if (!$candidates) {
        return [];
    }
    $selected = [];
    foreach (get_asset_segments(false) as $segment) {
        foreach (get_asset_fields(false, (int)$segment['id']) as $field) {
            $label = trim((string)($field['label'] ?? ''));
            if ($label === '' || !in_array($label, $candidates, true)) {
                continue;
            }
            if ((int)($field['is_download_level1'] ?? 0) === 1) {
                $selected[$label] = true;
            }
        }
    }
    $labels = array_keys($selected);
    sort($labels, SORT_NATURAL | SORT_FLAG_CASE);
    return $labels;
}

function asset_download_common_option_map(): array
{
    $options = ['__office__' => 'Office'];
    foreach (asset_download_common_label_candidates() as $label) {
        $options[$label] = $label;
    }
    return $options;
}

function save_asset_download_level1_labels(array $labels): void
{
    $candidates = asset_download_common_label_candidates();
    $selected = [];
    foreach ($labels as $label) {
        $label = trim((string)$label);
        if ($label !== '' && in_array($label, $candidates, true)) {
            $selected[$label] = true;
        }
    }
    db()->beginTransaction();
    try {
        foreach (get_asset_segments(false) as $segment) {
            foreach (get_asset_fields(true, (int)$segment['id']) as $field) {
                $label = trim((string)($field['label'] ?? ''));
                $isCommon = in_array($label, $candidates, true) ? 1 : 0;
                $isLevel1 = $isCommon && isset($selected[$label]) ? 1 : 0;
                db()->prepare('UPDATE asset_fields SET is_common_download_field = ?, is_download_level1 = ?, updated_at = NOW() WHERE id = ?')
                    ->execute([$isCommon, $isLevel1, (int)$field['id']]);
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

function asset_download_segment_selected_field_ids(int $segmentId, string $mode): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $flag = match ($mode) {
        'sort' => 'is_download_sort',
        'token' => 'is_download_token',
        default => 'is_download_filter',
    };
    $configuredColumn = match ($mode) {
        'sort' => 'download_sort_configured',
        'token' => 'download_token_configured',
        default => 'download_filter_configured',
    };
    $fields = get_asset_fields(false, $segmentId);
    if (!$fields) {
        return [];
    }
    $segment = get_asset_segment($segmentId);
    $isConfigured = (int)($segment[$configuredColumn] ?? 0) === 1;
    $selected = [];
    foreach ($fields as $field) {
        if ((int)($field[$flag] ?? 0) === 1) {
            $selected[] = (int)$field['id'];
        }
    }
    if ($selected) {
        return $selected;
    }
    if ($isConfigured) {
        return $selected;
    }
    if ($mode === 'token') {
        $commonLabels = array_fill_keys(asset_download_common_label_candidates(), true);
        $defaults = [];
        foreach ($fields as $field) {
            $label = trim((string)($field['label'] ?? ''));
            if ($label !== '' && isset($commonLabels[$label])) {
                $defaults[] = (int)$field['id'];
            }
        }
        return $defaults;
    }
    return array_map(static fn(array $field): int => (int)$field['id'], $fields);
}

function asset_download_segment_matrix_fields(int $segmentId, string $mode, bool $includeInactive = false): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $fields = get_asset_fields($includeInactive, $segmentId);
    if ($mode !== 'filter') {
        return $fields;
    }
    $commonLabels = array_fill_keys(asset_download_common_label_candidates(), true);
    return array_values(array_filter(
        $fields,
        static function (array $field) use ($commonLabels): bool {
            $label = trim((string)($field['label'] ?? ''));
            return $label === '' || !isset($commonLabels[$label]);
        }
    ));
}

function save_asset_download_segment_matrix(array $matrix): void
{
    db()->beginTransaction();
    try {
        foreach (get_asset_segments(false) as $segment) {
            $segmentId = (int)$segment['id'];
            $fields = get_asset_fields(false, $segmentId);
            $filterEligibleIds = array_fill_keys(
                array_map(
                    static fn(array $field): int => (int)$field['id'],
                    asset_download_segment_matrix_fields($segmentId, 'filter')
                ),
                true
            );
            $filterSelected = array_flip(array_map('intval', $matrix[$segmentId]['filter'] ?? []));
            $sortProvided = array_key_exists('sort', $matrix[$segmentId] ?? []);
            $tokenProvided = array_key_exists('token', $matrix[$segmentId] ?? []);
            $sortSelected = array_flip(array_map('intval', $matrix[$segmentId]['sort'] ?? []));
            $tokenSelected = array_flip(array_map('intval', $matrix[$segmentId]['token'] ?? []));
            foreach ($fields as $field) {
                $fieldId = (int)$field['id'];
                $filterValue = (int)($field['is_download_filter'] ?? 0);
                if (isset($filterEligibleIds[$fieldId])) {
                    $filterValue = isset($filterSelected[$fieldId]) ? 1 : 0;
                }
                $sortValue = $sortProvided ? (isset($sortSelected[$fieldId]) ? 1 : 0) : (int)($field['is_download_sort'] ?? 0);
                $tokenValue = $tokenProvided ? (isset($tokenSelected[$fieldId]) ? 1 : 0) : (int)($field['is_download_token'] ?? 0);
                db()->prepare('UPDATE asset_fields SET is_download_filter = ?, is_download_sort = ?, is_download_token = ?, updated_at = NOW() WHERE id = ?')
                    ->execute([
                        $filterValue,
                        $sortValue,
                        $tokenValue,
                        $fieldId,
                    ]);
            }
            db()->prepare('UPDATE segments SET download_filter_configured = 1, download_sort_configured = ?, download_token_configured = ?, updated_at = NOW() WHERE id = ?')
                ->execute([
                    $sortProvided ? 1 : (int)($segment['download_sort_configured'] ?? 0),
                    $tokenProvided ? 1 : (int)($segment['download_token_configured'] ?? 0),
                    $segmentId,
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

function asset_table_preference_category_id(int $categoryId, string $tableScope = 'my_office'): int
{
    return $tableScope === 'office_under_me' ? $categoryId + 1000000 : $categoryId;
}

function asset_table_available_columns(array $fields, array $uiFieldLabels, string $tableScope = 'my_office', ?int $segmentId = null): array
{
    $columns = [];
    if (is_superadmin() || asset_number_visible_to_users($segmentId)) {
        $columns[] = ['key' => 'asset_number', 'label' => 'Asset Number', 'type' => 'fixed'];
    }
    if (is_superadmin() || $tableScope === 'office_under_me') {
        $columns[] = ['key' => 'office_name', 'label' => 'Office', 'type' => 'fixed'];
    }
    if (asset_subcategory_enabled($segmentId)) {
        $columns[] = ['key' => 'subcategory_name', 'label' => 'Sub-category', 'type' => 'fixed'];
    }
    if (asset_data_provider_visible($segmentId)) {
        $columns[] = ['key' => 'data_provider', 'label' => 'Data Provider', 'type' => 'fixed'];
    }
    foreach ($fields as $field) {
        if ((int)$field['is_displayed'] !== 1 || (int)$field['active_status'] !== 1) {
            continue;
        }
        $columns[] = [
            'key' => (string)$field['field_key'],
            'label' => (string)($uiFieldLabels[$field['field_key']] ?? $field['label']),
            'type' => 'field',
        ];
        if ((string)($field['data_type'] ?? '') === 'bimh') {
            $columns[] = [
                'key' => (string)$field['field_key'] . '__est_name',
                'label' => 'Est Name',
                'type' => 'field',
            ];
        }
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
        'number' => asset_format_number_display($normalized['value_number'] !== null ? (string)$normalized['value_number'] : null),
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
    static $cache = [];

    if ($officeId <= 0) {
        return '-';
    }
    $cacheKey = $officeType . ':' . $officeId;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
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
    $cache[$cacheKey] = (string)($stmt->fetchColumn() ?: '-');
    return $cache[$cacheKey];
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
            $stmt = db()->prepare('INSERT INTO asset_fields (segment_id, field_key, label, data_type, field_information, video_tutorial_url, number_format_rule, text_max_length, secondary_of_field_id, conditional_map_json, mandatory_scope, is_required, is_displayed, is_import_enabled, is_unique, is_filter_enabled, filter_scope, is_common_download_field, is_download_level1, is_download_filter, is_download_sort, is_download_zip_file_selectable, is_download_token, active_status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())');
            $stmt->execute([
                $segmentId,
                $payload['field_key'],
                $payload['label'],
                'conditional',
                $payload['field_information'] ?: null,
                $payload['video_tutorial_url'] ?: null,
                json_encode($payload['conditional_map'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $payload['mandatory_scope'],
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $payload['is_filter_enabled'],
                $payload['filter_scope'],
                $payload['is_common_download_field'],
                $payload['is_download_level1'],
                $payload['is_download_filter'],
                $payload['is_download_sort'],
                0,
                $payload['is_download_token'],
                $fieldSortOrder,
            ]);
            $fieldId = (int)db()->lastInsertId();
            replace_asset_field_options($fieldId, $payload['options'] ?? []);

            $childStmt = db()->prepare('INSERT INTO asset_fields (segment_id, field_key, label, data_type, field_information, video_tutorial_url, number_format_rule, text_max_length, secondary_of_field_id, conditional_map_json, mandatory_scope, is_required, is_displayed, is_import_enabled, is_unique, is_filter_enabled, filter_scope, is_common_download_field, is_download_level1, is_download_filter, is_download_sort, is_download_zip_file_selectable, is_download_token, active_status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, ?, NULL, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())');
            $childStmt->execute([
                $segmentId,
                $payload['secondary_field_key'],
                $payload['secondary_label'],
                'dropdown',
                $payload['secondary_field_information'] ?: null,
                $payload['secondary_video_tutorial_url'] ?: null,
                $fieldId,
                $payload['mandatory_scope'],
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $payload['is_filter_enabled'],
                $payload['filter_scope'],
                $payload['is_common_download_field'],
                0,
                $payload['is_download_filter'],
                $payload['is_download_sort'],
                0,
                $payload['is_download_token'],
                $fieldSortOrder + 1,
            ]);
            $childId = (int)db()->lastInsertId();
            replace_asset_field_options($childId, $payload['secondary_options'] ?? []);
        } else {
            $stmt = db()->prepare('INSERT INTO asset_fields (segment_id, field_key, label, data_type, field_information, video_tutorial_url, number_format_rule, text_max_length, secondary_of_field_id, conditional_map_json, mandatory_scope, is_required, is_displayed, is_import_enabled, is_unique, is_filter_enabled, filter_scope, is_common_download_field, is_download_level1, is_download_filter, is_download_sort, is_download_zip_file_selectable, is_download_token, active_status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())');
            $stmt->execute([
                $segmentId,
                $payload['field_key'],
                $payload['label'],
                $payload['data_type'],
                $payload['field_information'] ?: null,
                $payload['video_tutorial_url'] ?: null,
                $payload['number_format_rule'] ?: null,
                $payload['text_max_length'],
                $payload['mandatory_scope'],
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $payload['is_unique'],
                $payload['is_filter_enabled'],
                $payload['filter_scope'],
                $payload['is_common_download_field'],
                $payload['is_download_level1'],
                $payload['is_download_filter'],
                $payload['is_download_sort'],
                $payload['is_download_zip_file_selectable'],
                $payload['is_download_token'],
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
            $stmt = db()->prepare('UPDATE asset_fields SET label = ?, data_type = ?, field_information = ?, video_tutorial_url = ?, number_format_rule = NULL, text_max_length = NULL, conditional_map_json = ?, mandatory_scope = ?, is_required = ?, is_displayed = ?, is_import_enabled = ?, is_unique = 0, is_filter_enabled = ?, filter_scope = ?, is_common_download_field = ?, is_download_level1 = ?, is_download_filter = ?, is_download_sort = ?, is_download_zip_file_selectable = 0, is_download_token = ?, sort_order = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([
                $payload['label'],
                'conditional',
                $payload['field_information'] ?: null,
                $payload['video_tutorial_url'] ?: null,
                json_encode($payload['conditional_map'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $payload['mandatory_scope'],
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $payload['is_filter_enabled'],
                $payload['filter_scope'],
                $payload['is_common_download_field'],
                $payload['is_download_level1'],
                $payload['is_download_filter'],
                $payload['is_download_sort'],
                $payload['is_download_token'],
                $payload['sort_order'],
                $id,
            ]);
            replace_asset_field_options($id, $payload['options'] ?? []);
            delete_asset_field_file_rule($id);

            if (!$childField) {
                throw new RuntimeException('Conditional child field not found.');
            }
            $childStmt = db()->prepare('UPDATE asset_fields SET label = ?, data_type = ?, field_information = ?, video_tutorial_url = ?, number_format_rule = NULL, text_max_length = NULL, mandatory_scope = ?, is_required = ?, is_displayed = ?, is_import_enabled = ?, is_unique = 0, is_filter_enabled = ?, filter_scope = ?, is_common_download_field = ?, is_download_level1 = 0, is_download_filter = ?, is_download_sort = ?, is_download_zip_file_selectable = 0, is_download_token = ?, sort_order = ?, updated_at = NOW() WHERE id = ?');
            $childStmt->execute([
                $payload['secondary_label'],
                'dropdown',
                $payload['secondary_field_information'] ?: null,
                $payload['secondary_video_tutorial_url'] ?: null,
                $payload['mandatory_scope'],
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $payload['is_filter_enabled'],
                $payload['filter_scope'],
                $payload['is_common_download_field'],
                $payload['is_download_filter'],
                $payload['is_download_sort'],
                $payload['is_download_token'],
                $payload['sort_order'] + 1,
                (int)$childField['id'],
            ]);
            replace_asset_field_options((int)$childField['id'], $payload['secondary_options'] ?? []);
            delete_asset_field_file_rule((int)$childField['id']);
        } else {
            $stmt = db()->prepare('UPDATE asset_fields SET label = ?, data_type = ?, field_information = ?, video_tutorial_url = ?, number_format_rule = ?, text_max_length = ?, conditional_map_json = NULL, mandatory_scope = ?, is_required = ?, is_displayed = ?, is_import_enabled = ?, is_unique = ?, is_filter_enabled = ?, filter_scope = ?, is_common_download_field = ?, is_download_level1 = ?, is_download_filter = ?, is_download_sort = ?, is_download_zip_file_selectable = ?, is_download_token = ?, sort_order = ?, updated_at = NOW() WHERE id = ?');
            $stmt->execute([
                $payload['label'],
                $payload['data_type'],
                $payload['field_information'] ?: null,
                $payload['video_tutorial_url'] ?: null,
                $payload['number_format_rule'] ?: null,
                $payload['text_max_length'],
                $payload['mandatory_scope'],
                $payload['is_required'],
                $payload['is_displayed'],
                $payload['is_import_enabled'],
                $payload['is_unique'],
                $payload['is_filter_enabled'],
                $payload['filter_scope'],
                $payload['is_common_download_field'],
                $payload['is_download_level1'],
                $payload['is_download_filter'],
                $payload['is_download_sort'],
                $payload['is_download_zip_file_selectable'],
                $payload['is_download_token'],
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
    $fieldInformation = asset_normalize_help_text((string)($input['field_information'] ?? ''));
    $videoTutorialUrl = asset_normalize_tutorial_url((string)($input['video_tutorial_url'] ?? ''));
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
    if (!asset_is_valid_tutorial_url($videoTutorialUrl)) {
        $errors[] = 'Tutorial URL must be a valid URL.';
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

    $textMaxLength = null;
    if ($dataType === 'text') {
        $textMaxLengthRaw = trim((string)($input['text_max_length'] ?? ''));
        if ($textMaxLengthRaw !== '') {
            if (!ctype_digit($textMaxLengthRaw) || (int)$textMaxLengthRaw <= 0) {
                $errors[] = 'Text max characters must be a positive whole number.';
            } else {
                $textMaxLength = (int)$textMaxLengthRaw;
            }
        }
    }

    $secondaryLabel = trim((string)($input['secondary_label'] ?? ''));
    $secondaryFieldInformation = asset_normalize_help_text((string)($input['secondary_field_information'] ?? ''));
    $secondaryVideoTutorialUrl = asset_normalize_tutorial_url((string)($input['secondary_video_tutorial_url'] ?? ''));
    $conditionalMap = [];
    $secondaryOptions = [];
    $secondaryFieldKey = '';
    if ($dataType === 'conditional') {
        if (!asset_is_valid_tutorial_url($secondaryVideoTutorialUrl)) {
            $errors[] = 'Secondary tutorial URL must be a valid URL.';
        }
        $rawPrimaryOptions = preg_split('/\r\n|\r|\n/', (string)($input['conditional_primary_options_text'] ?? ''));
        $primaryOptions = [];
        foreach ($rawPrimaryOptions as $option) {
            $option = asset_normalize_conditional_option_value((string)$option);
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
            $primaryKey = strtolower(asset_normalize_conditional_option_value((string)$parts[0]));
            if (!isset($primaryLookup[$primaryKey])) {
                $errors[] = 'Conditional rules reference an unknown primary option.';
                continue;
            }
            $children = array_values(array_filter(array_map(
                static fn(string $item): string => asset_normalize_conditional_option_value($item),
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

    $filterScope = $dataType === 'conditional'
        ? asset_normalize_filter_scope($input['filter_scope'] ?? asset_filter_scope_all())
        : asset_normalize_filter_scope($input['filter_scope'] ?? asset_filter_scope_none());
    $isCommonDownloadField = !empty($input['is_common_download_field']) ? 1 : 0;
    $isDownloadLevel1 = $isCommonDownloadField && !empty($input['is_download_level1']) ? 1 : 0;
    $isDownloadFilter = !empty($input['is_download_filter']) ? 1 : 0;
    $isDownloadSort = !empty($input['is_download_sort']) ? 1 : 0;
    $isDownloadZipFileSelectable = $dataType === 'file' && !empty($input['is_download_zip_file_selectable']) ? 1 : 0;
    $isDownloadToken = !empty($input['is_download_token']) ? 1 : 0;

    return [
        'errors' => $errors,
        'payload' => [
            'segment_id' => $segmentId,
            'field_key' => $fieldKey,
            'label' => $label,
            'data_type' => $dataType,
            'field_information' => $fieldInformation,
            'video_tutorial_url' => $videoTutorialUrl,
            'number_format_rule' => $numberFormatRule,
            'text_max_length' => $textMaxLength,
            'mandatory_scope' => asset_normalize_mandatory_scope($input['mandatory_scope'] ?? asset_mandatory_scope_optional()),
            'is_required' => asset_normalize_mandatory_scope($input['mandatory_scope'] ?? asset_mandatory_scope_optional()) === asset_mandatory_scope_input() ? 1 : 0,
            'is_displayed' => !empty($input['is_displayed']) ? 1 : 0,
            'is_import_enabled' => in_array($dataType, ['file'], true) ? 0 : (!empty($input['is_import_enabled']) ? 1 : 0),
            'is_unique' => in_array($dataType, ['file', 'conditional'], true) ? 0 : (!empty($input['is_unique']) ? 1 : 0),
            'is_filter_enabled' => $filterScope === asset_filter_scope_none() ? 0 : 1,
            'filter_scope' => $filterScope,
            'is_common_download_field' => $isCommonDownloadField,
            'is_download_level1' => $isDownloadLevel1,
            'is_download_filter' => $isDownloadFilter,
            'is_download_sort' => $isDownloadSort,
            'is_download_zip_file_selectable' => $isDownloadZipFileSelectable,
            'is_download_token' => $isDownloadToken,
            'sort_order' => $sortOrder,
            'options' => $options,
            'file_rule' => $fileRule,
            'secondary_label' => $secondaryLabel,
            'secondary_field_information' => $secondaryFieldInformation,
            'secondary_video_tutorial_url' => $secondaryVideoTutorialUrl,
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
    $categorySelectionEnabled = asset_category_selection_enabled($segmentId);
    $categoryId = $categorySelectionEnabled ? (int)($input['category_id'] ?? 0) : asset_single_category_id($segmentId);
    $subcategoryEnabled = asset_subcategory_enabled($segmentId);
    $subcategoryId = $subcategoryEnabled ? (int)($input['subcategory_id'] ?? 0) : 0;
    $category = $categoryId > 0 ? get_asset_category($categoryId, $segmentId) : null;
    $subcategory = $subcategoryEnabled && $subcategoryId > 0 ? get_asset_subcategory($subcategoryId, $segmentId) : null;
    if ($categorySelectionEnabled && (!$category || (!is_superadmin() && (int)$category['active_status'] !== 1))) {
        $errors['category_id'] = 'Valid category is required.';
    }
    if ($subcategoryEnabled && (!$subcategory || ($categoryId > 0 && (int)($subcategory['category_id'] ?? 0) !== $categoryId) || (!is_superadmin() && (int)$subcategory['active_status'] !== 1))) {
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
    if (asset_is_input_required($field) && $finalCount === 0) {
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
    if (asset_is_input_required($field) && $isEmpty) {
        $errors[$key] = "{$label} is required.";
        return $normalized;
    }
    if ($isEmpty) {
        return $normalized;
    }

    if ($type === 'text') {
        $maxLength = asset_text_max_length($field);
        if ($maxLength !== null && mb_strlen((string)$value) > $maxLength) {
            $errors[$key] = "{$label} must not exceed {$maxLength} characters.";
            return $normalized;
        }
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
        $normalized['value_number'] = $valueString;
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
        'number' => (($normalized = asset_normalize_number_string($value)) !== null) ? asset_format_number_display($normalized) : null,
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
    $segmentId = asset_normalize_segment_id((int)($validated['segment_id'] ?? 0));
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
        $validated['category_id'] > 0 ? $validated['category_id'] : null,
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
    $segmentId = asset_normalize_segment_id((int)($validated['segment_id'] ?? ($asset['segment_id'] ?? 0)));
    $fieldMap = asset_field_map_for_segment(true, $segmentId);
    $logDetails = build_asset_update_log_details($asset, $validated, $fieldMap);
    $fileCleanup = ['new_paths' => [], 'delete_paths' => []];
    db()->beginTransaction();
    try {
        $stmt = db()->prepare('UPDATE assets SET category_id = ?, subcategory_id = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([
            $validated['category_id'] > 0 ? $validated['category_id'] : null,
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

    $field = asset_field_map_for_segment(true, (int)($asset['segment_id'] ?? 0))[$fieldKey] ?? null;
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
            LEFT JOIN asset_categories c ON c.id = a.category_id AND c.segment_id = a.segment_id
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
    $bimhValues = [];
    foreach ($rows as $row) {
        $fieldKey = (string)$row['field_key'];
        $display = asset_display_value($row);
        $map[$fieldKey] = $display;
        if ((string)($row['data_type'] ?? '') === 'bimh' && $display !== '') {
            $bimhValues[$fieldKey] = $display;
        }
    }
    if ($bimhValues) {
        $nameMap = asset_bimh_lookup_many(array_values($bimhValues));
        foreach ($bimhValues as $fieldKey => $bimhId) {
            $map[$fieldKey . '__est_name'] = $nameMap[$bimhId] ?? 'BIMH ID is not in the Database.';
        }
    }
    return $map;
}

function asset_display_value(array $row): string
{
    return match ($row['data_type']) {
        'number' => asset_format_number_display($row['value_number'] !== null ? (string)$row['value_number'] : null),
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
            } elseif ($value === '__blank__') {
                if ($extensions) {
                    return false;
                }
            } elseif ($value !== '' && !in_array(strtolower($value), $extensions, true)) {
                return false;
            }
            continue;
        }
        if ($fieldType === 'conditional') {
            $currentValue = asset_filter_value($asset, $fieldKey);
            if ($value === '__blank__' && $currentValue !== '') {
                return false;
            }
            if ($value !== '' && $value !== '__blank__' && strcasecmp($currentValue, $value) !== 0) {
                return false;
            }
            continue;
        }
        if (asset_is_conditional_secondary($field)) {
            $currentValue = asset_filter_value($asset, $fieldKey);
            if ($value === '__blank__' && $currentValue !== '') {
                return false;
            }
            if ($value !== '' && $value !== '__blank__' && strcasecmp($currentValue, $value) !== 0) {
                return false;
            }
            continue;
        }
        $currentValue = asset_filter_value($asset, $fieldKey);
        if ($value === '__blank__' && $currentValue !== '') {
            return false;
        }
        if ($value !== '' && $value !== '__blank__' && strcasecmp($currentValue, $value) !== 0) {
            return false;
        }
    }
    return true;
}

function asset_filter_visible_fields(array $fields, array $assets, ?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $canSeeSuperadminOnly = is_superadmin();
    $visible = [];
    foreach ($fields as $field) {
        if ((int)($field['active_status'] ?? 0) !== 1) {
            continue;
        }
        if (asset_is_conditional_secondary($field)) {
            $parentField = get_asset_field((int)$field['secondary_of_field_id'], $segmentId);
            $parentScope = asset_normalize_filter_scope($parentField['filter_scope'] ?? (($parentField['is_filter_enabled'] ?? 0) ? asset_filter_scope_all() : asset_filter_scope_none()));
            if ($parentField && ($parentScope === asset_filter_scope_all() || ($canSeeSuperadminOnly && $parentScope === asset_filter_scope_superadmin_only()))) {
                $visible[$field['field_key']] = true;
            }
            continue;
        }
        $scope = asset_normalize_filter_scope($field['filter_scope'] ?? (($field['is_filter_enabled'] ?? 0) ? asset_filter_scope_all() : asset_filter_scope_none()));
        if ($scope === asset_filter_scope_all() || ($canSeeSuperadminOnly && $scope === asset_filter_scope_superadmin_only())) {
            $visible[$field['field_key']] = true;
        }
    }
    return $visible;
}

function build_asset_filter_catalog(array $assets, array $fields, ?int $segmentId = null, bool $respectBoardFilterVisibility = true): array
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
    $visibleFields = $respectBoardFilterVisibility
        ? asset_filter_visible_fields($fields, $assets, $segmentId)
        : array_fill_keys(array_map(static fn(array $field): string => (string)$field['field_key'], $fields), true);
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
                'field_id' => (int)($field['id'] ?? 0),
                'secondary_of_field_id' => (int)($field['secondary_of_field_id'] ?? 0),
                'child_key' => null,
                'child_label' => null,
                'options' => [],
                'secondary_options_map' => [],
                'has_blank' => false,
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
                $primaryValue = asset_filter_value($asset, $fieldKey);
                if ($primaryValue === '') {
                    $catalog['fields'][$fieldKey]['has_blank'] = true;
                }
                $childField = get_asset_conditional_child_field((int)$field['id'], $segmentId);
                if ($childField && (int)($childField['active_status'] ?? 0) === 1) {
                    $childKey = (string)$childField['field_key'];
                    $catalog['fields'][$fieldKey]['child_key'] = $childKey;
                    $catalog['fields'][$fieldKey]['child_label'] = (string)$childField['label'];
                    $catalog['fields'][$childKey] ??= [
                        'field_key' => $childKey,
                        'label' => (string)$childField['label'],
                        'data_type' => (string)($childField['data_type'] ?? 'dropdown'),
                        'field_id' => (int)($childField['id'] ?? 0),
                        'secondary_of_field_id' => (int)($childField['secondary_of_field_id'] ?? 0),
                        'parent_field_key' => $fieldKey,
                        'parent_label' => (string)$field['label'],
                        'options' => [],
                        'secondary_options_map' => [],
                        'has_blank' => false,
                    ];
                    $childValue = asset_filter_value($asset, $childKey);
                    if ($childValue === '') {
                        $catalog['fields'][$childKey]['has_blank'] = true;
                    }
                    if ($primaryValue !== '') {
                        $catalog['fields'][$fieldKey]['options'][$primaryValue] = $primaryValue;
                        $catalog['fields'][$fieldKey]['secondary_options_map'][$primaryValue] ??= [];
                    }
                    if ($primaryValue !== '' && $childValue !== '') {
                        $catalog['fields'][$fieldKey]['secondary_options_map'][$primaryValue][$childValue] = $childValue;
                        $catalog['fields'][$childKey]['options'][$childValue] = $childValue;
                    }
                }
            } elseif (in_array($fieldType, ['dropdown', 'yes_no'], true)) {
                $value = asset_filter_value($asset, $fieldKey);
                if ($value === '') {
                    $catalog['fields'][$fieldKey]['has_blank'] = true;
                } else {
                    $catalog['fields'][$fieldKey]['options'][$value] = $value;
                }
                if ($fieldType === 'yes_no' && !$catalog['fields'][$fieldKey]['options'] && !$catalog['fields'][$fieldKey]['has_blank']) {
                    $catalog['fields'][$fieldKey]['options'] = ['Yes' => 'Yes', 'No' => 'No'];
                }
            } elseif ($fieldType !== 'date') {
                $value = asset_filter_value($asset, $fieldKey);
                if ($value !== '') {
                    $catalog['fields'][$fieldKey]['options'][$value] = $value;
                } else {
                    $catalog['fields'][$fieldKey]['has_blank'] = true;
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

function asset_download_common_field_catalog(array $user, string $viewScope = 'my_office'): array
{
    $catalog = [];
    $allAssetsBySegment = [];
    foreach (get_asset_segments(false) as $segment) {
        $segmentId = (int)$segment['id'];
        $allAssetsBySegment[$segmentId] = asset_download_accessible_assets_for_segment($segmentId, $user, $viewScope);
    }

    $zoneOptions = [];
    $circleOptions = [];
    $divisionOptions = [];
    $subdivisionOptions = [];
    foreach ($allAssetsBySegment as $assets) {
        foreach ($assets as $asset) {
            $meta = asset_download_office_hierarchy($asset);
            if ((int)($meta['zone_id'] ?? 0) > 0) {
                $zoneOptions[(string)$meta['zone_id']] = [
                    'id' => (string)$meta['zone_id'],
                    'name' => (string)($meta['zone_name'] ?? ''),
                ];
            }
            if ((int)($meta['circle_id'] ?? 0) > 0) {
                $circleOptions[(string)$meta['circle_id']] = [
                    'id' => (string)$meta['circle_id'],
                    'zone_id' => (string)($meta['zone_id'] ?? ''),
                    'name' => (string)($meta['circle_name'] ?? ''),
                ];
            }
            if ((int)($meta['division_id'] ?? 0) > 0) {
                $divisionOptions[(string)$meta['division_id']] = [
                    'id' => (string)$meta['division_id'],
                    'zone_id' => (string)($meta['zone_id'] ?? ''),
                    'circle_id' => (string)($meta['circle_id'] ?? ''),
                    'name' => (string)($meta['division_name'] ?? ''),
                ];
            }
            if ((int)($meta['subdivision_id'] ?? 0) > 0) {
                $subdivisionOptions[(string)$meta['subdivision_id']] = [
                    'id' => (string)$meta['subdivision_id'],
                    'zone_id' => (string)($meta['zone_id'] ?? ''),
                    'circle_id' => (string)($meta['circle_id'] ?? ''),
                    'division_id' => (string)($meta['division_id'] ?? ''),
                    'name' => (string)($meta['subdivision_name'] ?? ''),
                ];
            }
        }
    }
    uasort($zoneOptions, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
    uasort($circleOptions, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
    uasort($divisionOptions, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
    uasort($subdivisionOptions, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
    $catalog['__office__'] = [
        'identifier' => '__office__',
        'label' => 'Office',
        'data_type' => 'office',
        'zones' => $zoneOptions,
        'circles' => $circleOptions,
        'divisions' => $divisionOptions,
        'subdivisions' => $subdivisionOptions,
    ];

    foreach (asset_download_common_label_candidates() as $label) {
        $fieldMeta = null;
        $fieldMetaSegmentId = null;
        $options = [];
        $secondaryOptionsMap = [];
        $hasBlank = false;
        foreach (get_asset_segments(false) as $segment) {
            $segmentId = (int)$segment['id'];
            $field = asset_download_field_for_label($label, $segmentId);
            if (!$field) {
                continue;
            }
            if ($fieldMeta === null) {
                $fieldMeta = $field;
                $fieldMetaSegmentId = $segmentId;
            }
            foreach ($allAssetsBySegment[$segmentId] ?? [] as $asset) {
                $fieldKey = (string)$field['field_key'];
                $fieldType = (string)($field['data_type'] ?? 'text');
                if ($fieldType === 'file') {
                    $extensions = asset_file_extensions_for_asset($asset, $fieldKey);
                    if ($extensions) {
                        $options['__has_file__'] = 'Have file';
                    } else {
                        $options['__no_file__'] = 'No file';
                    }
                    continue;
                }
                if ($fieldType === 'bimh') {
                    $value = trim((string)($asset['values'][$fieldKey . '__est_name'] ?? ''));
                } else {
                    $value = asset_filter_value($asset, $fieldKey);
                }
                if ($fieldType === 'conditional') {
                    $childField = get_asset_conditional_child_field((int)$field['id'], true, $segmentId);
                    $childKey = $childField ? (string)$childField['field_key'] : '';
                    $childValue = $childKey !== '' ? asset_filter_value($asset, $childKey) : '';
                    if ($value !== '') {
                        $options[$value] = $value;
                        $secondaryOptionsMap[$value] ??= [];
                    }
                    if ($value !== '' && $childValue !== '') {
                        $secondaryOptionsMap[$value][$childValue] = $childValue;
                    }
                }
                if ($value === '') {
                    $hasBlank = true;
                } elseif (!in_array($fieldType, ['date', 'number', 'conditional'], true)) {
                    $options[$value] = $value;
                }
            }
        }
        if ($fieldMeta === null) {
            continue;
        }
        if ($options) {
            natcasesort($options);
        }
        $catalog[$label] = [
            'identifier' => $label,
            'label' => $label,
            'data_type' => (string)($fieldMeta['data_type'] ?? 'text'),
            'options' => $options,
            'secondary_options_map' => $secondaryOptionsMap,
            'has_blank' => $hasBlank,
        ];
        if ((string)($fieldMeta['data_type'] ?? '') === 'conditional') {
            $childField = get_asset_conditional_child_field((int)$fieldMeta['id'], true, $fieldMetaSegmentId);
            if ($childField) {
                $catalog[$label]['child_identifier'] = (string)$childField['label'];
                $catalog[$label]['child_label'] = (string)$childField['label'];
                $catalog[(string)$childField['label']] = [
                    'identifier' => (string)$childField['label'],
                    'label' => (string)$childField['label'],
                    'data_type' => 'conditional_secondary',
                    'options' => [],
                    'secondary_options_map' => $secondaryOptionsMap,
                    'has_blank' => $hasBlank,
                    'parent_identifier' => $label,
                    'parent_label' => $label,
                ];
            }
        }
    }

    return $catalog;
}

function get_assets(array $filters = [], ?array $user = null, bool $includeDeleted = false): array
{
    $user = $user ?: current_user();
    $viewScope = (string)($filters['office_view_scope'] ?? 'my_office');
    $segmentId = asset_normalize_segment_id(isset($filters['segment_id']) ? (int)$filters['segment_id'] : null);
    $sql = 'SELECT a.*, c.name AS category_name, s.name AS subcategory_name, creator.email_id AS created_by_email, editor.email_id AS updated_by_email
            FROM assets a
            LEFT JOIN asset_categories c ON c.id = a.category_id AND c.segment_id = a.segment_id
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
    $bimhValues = [];
    foreach ($stmt->fetchAll() as $row) {
        $assetId = (int)$row['asset_id'];
        $fieldKey = (string)$row['field_key'];
        $display = asset_display_value($row);
        $map[$assetId][$fieldKey] = $display;
        if ((string)($row['data_type'] ?? '') === 'bimh' && $display !== '') {
            $bimhValues[$assetId . ':' . $fieldKey] = $display;
        }
    }
    if ($bimhValues) {
        $nameMap = asset_bimh_lookup_many(array_values($bimhValues));
        foreach ($bimhValues as $compositeKey => $bimhId) {
            [$assetId, $fieldKey] = explode(':', $compositeKey, 2);
            $map[(int)$assetId][$fieldKey . '__est_name'] = $nameMap[$bimhId] ?? 'BIMH ID is not in the Database.';
        }
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
    if (!$categories) {
        $fallbackAssets = get_assets($filters, $user);
        if ($fallbackAssets) {
            $grouped[] = [
                'category' => ['id' => 0, 'name' => 'Assets'],
                'assets' => $fallbackAssets,
            ];
        }
        return $grouped;
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
    if (asset_category_selection_enabled($segmentId)) {
        $headers['category'] = 'Category';
    }
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
        $row = ['serial' => $index + 1];
        if (asset_category_selection_enabled($segmentId)) {
            $row['category'] = (string)($asset['category_name'] ?? '');
        }
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

function asset_download_safe_name(string $value, string $fallback = 'Blank'): string
{
    $value = trim($value);
    if ($value === '') {
        $value = $fallback;
    }
    $value = preg_replace('/[\\\\\\/\\:\\*\\?\"\\<\\>\\|]+/', '_', $value);
    $value = preg_replace('/\\s+/', ' ', (string)$value);
    $value = trim((string)$value);
    return $value === '' ? $fallback : $value;
}

function asset_download_field_for_label(string $label, int $segmentId): ?array
{
    foreach (get_asset_fields(false, $segmentId) as $field) {
        if (trim((string)($field['label'] ?? '')) === $label) {
            return $field;
        }
    }
    return null;
}

function asset_download_office_hierarchy(array $asset): array
{
    static $zoneCache = [];
    static $circleCache = [];
    static $divisionCache = [];
    static $subdivisionCache = [];

    $officeType = (int)($asset['office_type'] ?? 0);
    $officeId = (int)($asset['office_id'] ?? 0);
    $meta = [
        'zone_id' => 0,
        'zone_name' => '',
        'circle_id' => 0,
        'circle_name' => '',
        'division_id' => 0,
        'division_name' => '',
        'subdivision_id' => 0,
        'subdivision_name' => '',
        'office_name' => trim((string)(($asset['office_type_label'] ?? '') . ' - ' . ($asset['office_name'] ?? '')), ' -'),
    ];

    if ($officeType === 2) {
        $meta['zone_id'] = $officeId;
        $meta['zone_name'] = (string)($asset['office_name'] ?? '');
        return $meta;
    }
    if ($officeType === 3) {
        $circleCache[$officeId] ??= find_circle_with_zone($officeId) ?: [];
        $circle = $circleCache[$officeId];
        $meta['zone_id'] = (int)($circle['zone_id'] ?? 0);
        $meta['zone_name'] = (string)($circle['zone_name'] ?? '');
        $meta['circle_id'] = $officeId;
        $meta['circle_name'] = (string)($circle['office_name'] ?? '');
        return $meta;
    }
    if ($officeType === 4) {
        $divisionCache[$officeId] ??= find_division_with_hierarchy($officeId) ?: [];
        $division = $divisionCache[$officeId];
        $meta['zone_id'] = (int)($division['zone_id'] ?? 0);
        $meta['zone_name'] = (string)($division['zone_name'] ?? '');
        $meta['circle_id'] = (int)($division['circle_id'] ?? 0);
        $meta['circle_name'] = (string)($division['circle_name'] ?? '');
        $meta['division_id'] = $officeId;
        $meta['division_name'] = (string)($division['office_name'] ?? '');
        return $meta;
    }
    if ($officeType === 5) {
        $subdivisionCache[$officeId] ??= find_subdivision_with_hierarchy($officeId) ?: [];
        $subdivision = $subdivisionCache[$officeId];
        $meta['zone_id'] = (int)($subdivision['zone_id'] ?? 0);
        $meta['zone_name'] = (string)($subdivision['zone_name'] ?? '');
        $meta['circle_id'] = (int)($subdivision['circle_id'] ?? 0);
        $meta['circle_name'] = (string)($subdivision['circle_name'] ?? '');
        $meta['division_id'] = (int)($subdivision['division_id'] ?? 0);
        $meta['division_name'] = (string)($subdivision['division_name'] ?? '');
        $meta['subdivision_id'] = $officeId;
        $meta['subdivision_name'] = (string)($subdivision['office_name'] ?? '');
        return $meta;
    }
    return $meta;
}

function asset_download_level1_value_for_asset(array $asset, string $label, int $segmentId): string
{
    if ($label === 'Office') {
        $value = trim((string)(asset_download_office_hierarchy($asset)['office_name'] ?? ''));
        return $value === '' ? 'Blank' : $value;
    }
    $field = asset_download_field_for_label($label, $segmentId);
    if (!$field) {
        return 'Blank';
    }
    $value = trim((string)($asset['values'][(string)$field['field_key']] ?? ''));
    return $value === '' ? 'Blank' : $value;
}

function asset_download_accessible_assets_for_segment(int $segmentId, array $user, string $viewScope = 'my_office'): array
{
    $filters = ['segment_id' => $segmentId];
    if (!is_superadmin()) {
        $filters['office_view_scope'] = $viewScope;
    }
    return get_assets($filters, $user);
}

function asset_download_level1_catalog(array $user, string $viewScope = 'my_office'): array
{
    $catalog = [];
    $officeValues = [];
    foreach (get_asset_segments(false) as $segment) {
        foreach (asset_download_accessible_assets_for_segment((int)$segment['id'], $user, $viewScope) as $asset) {
            $value = trim((string)(asset_download_office_hierarchy($asset)['office_name'] ?? ''));
            $value = $value === '' ? 'Blank' : $value;
            $officeValues[$value] = $value;
        }
    }
    $officeItems = array_values($officeValues);
    usort($officeItems, 'strnatcasecmp');
    $catalog['Office'] = $officeItems;
    foreach (asset_download_selected_level1_labels() as $label) {
        $values = [];
        foreach (get_asset_segments(false) as $segment) {
            $segmentId = (int)$segment['id'];
            $field = asset_download_field_for_label($label, $segmentId);
            if (!$field) {
                continue;
            }
            foreach (asset_download_accessible_assets_for_segment($segmentId, $user, $viewScope) as $asset) {
                $value = asset_download_level1_value_for_asset($asset, $label, $segmentId);
                $values[$value] = $value;
            }
        }
        $items = array_values($values);
        usort($items, 'strnatcasecmp');
        $catalog[$label] = $items;
    }
    ksort($catalog, SORT_NATURAL | SORT_FLAG_CASE);
    return $catalog;
}

function asset_download_sort_option_map(int $segmentId): array
{
    $options = [
        '__office_name' => 'Office',
        '__category' => 'Category',
    ];
    if (asset_subcategory_enabled($segmentId)) {
        $options['__subcategory'] = 'Sub-category';
    }
    foreach (asset_download_sort_fields($segmentId) as $field) {
        $options[(string)$field['field_key']] = (string)$field['label'];
    }
    return $options;
}

function asset_download_matches_multi_hierarchy_filters(array $asset, array $filters): bool
{
    $meta = asset_download_office_hierarchy($asset);
    $map = [
        'zone_ids' => 'zone_id',
        'circle_ids' => 'circle_id',
        'division_ids' => 'division_id',
        'subdivision_ids' => 'subdivision_id',
    ];
    foreach ($map as $inputKey => $metaKey) {
        $values = array_values(array_filter(array_map('intval', (array)($filters[$inputKey] ?? []))));
        if ($values && !in_array((int)$meta[$metaKey], $values, true)) {
            return false;
        }
    }
    return true;
}

function asset_download_matches_segment_filters(array $asset, array $fieldMap, array $filters): bool
{
    foreach ($fieldMap as $fieldKey => $field) {
        $criteria = (array)($filters[$fieldKey] ?? []);
        $type = (string)($field['data_type'] ?? 'text');
        $selected = array_values(array_map('strval', (array)($criteria['values'] ?? [])));
        if ($type === 'date') {
            $from = trim((string)($criteria['from'] ?? ''));
            $to = trim((string)($criteria['to'] ?? ''));
            $allowBlank = !empty($criteria['blank']);
            $current = asset_filter_value($asset, $fieldKey);
            if ($current === '') {
                if ($allowBlank || ($from === '' && $to === '')) {
                    continue;
                }
                return false;
            }
            if (!asset_date_in_range($current, $from, $to)) {
                return false;
            }
            continue;
        }
        if ($type === 'number') {
            $from = trim((string)($criteria['from'] ?? ''));
            $to = trim((string)($criteria['to'] ?? ''));
            $allowBlank = !empty($criteria['blank']);
            $current = trim((string)($asset['values'][$fieldKey] ?? ''));
            if ($current === '') {
                if ($allowBlank || ($from === '' && $to === '')) {
                    continue;
                }
                return false;
            }
            $currentValue = (float)$current;
            if ($from !== '' && $currentValue < (float)$from) {
                return false;
            }
            if ($to !== '' && $currentValue > (float)$to) {
                return false;
            }
            continue;
        }
        if ($type === 'file') {
            if (!$selected) {
                continue;
            }
            $exts = asset_file_extensions_for_asset($asset, $fieldKey);
            if (!$exts && in_array('__no_file__', $selected, true)) {
                continue;
            }
            if ($exts && in_array('__has_file__', $selected, true)) {
                continue;
            }
            return false;
        }
        $current = $type === 'bimh'
            ? trim((string)($asset['values'][$fieldKey . '__est_name'] ?? ''))
            : asset_filter_value($asset, $fieldKey);
        if ($current === '' && in_array('__blank__', $selected, true)) {
            continue;
        }
        if (!$selected) {
            continue;
        }
        $matched = false;
        foreach ($selected as $value) {
            if ($value !== '__blank__' && strcasecmp($current, $value) === 0) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            return false;
        }
    }
    return true;
}

function asset_download_matches_common_filters(array $asset, int $segmentId, array $filters): bool
{
    foreach ($filters as $identifier => $criteria) {
        if (!is_array($criteria)) {
            continue;
        }
        if ($identifier === '__office__') {
            if (!asset_download_matches_multi_hierarchy_filters($asset, $criteria)) {
                return false;
            }
            continue;
        }
        $field = asset_download_field_for_label((string)$identifier, $segmentId);
        if (!$field) {
            continue;
        }
        $fieldKey = (string)$field['field_key'];
        $fieldType = (string)($field['data_type'] ?? 'text');
        $selected = array_values(array_map('strval', (array)($criteria['values'] ?? [])));

        if ($fieldType === 'date') {
            $from = trim((string)($criteria['from'] ?? ''));
            $to = trim((string)($criteria['to'] ?? ''));
            $allowBlank = !empty($criteria['blank']);
            $current = asset_filter_value($asset, $fieldKey);
            if ($current === '') {
                if ($allowBlank || ($from === '' && $to === '')) {
                    continue;
                }
                return false;
            }
            if (!asset_date_in_range($current, $from, $to)) {
                return false;
            }
            continue;
        }

        if ($fieldType === 'number') {
            $from = trim((string)($criteria['from'] ?? ''));
            $to = trim((string)($criteria['to'] ?? ''));
            $allowBlank = !empty($criteria['blank']);
            $current = trim((string)($asset['values'][$fieldKey] ?? ''));
            if ($current === '') {
                if ($allowBlank || ($from === '' && $to === '')) {
                    continue;
                }
                return false;
            }
            $currentValue = (float)$current;
            if ($from !== '' && $currentValue < (float)$from) {
                return false;
            }
            if ($to !== '' && $currentValue > (float)$to) {
                return false;
            }
            continue;
        }

        if ($fieldType === 'file') {
            $extensions = asset_file_extensions_for_asset($asset, $fieldKey);
            if (!$selected) {
                continue;
            }
            if (!$extensions && in_array('__no_file__', $selected, true)) {
                continue;
            }
            if ($extensions && in_array('__has_file__', $selected, true)) {
                continue;
            }
            return false;
        }

        $current = $fieldType === 'bimh'
            ? trim((string)($asset['values'][$fieldKey . '__est_name'] ?? ''))
            : asset_filter_value($asset, $fieldKey);
        if ($current === '' && in_array('__blank__', $selected, true)) {
            continue;
        }
        if (!$selected) {
            continue;
        }
        $matched = false;
        foreach ($selected as $value) {
            if ($value !== '__blank__' && strcasecmp($current, $value) === 0) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            return false;
        }
    }
    return true;
}

function asset_download_sort_value(array $asset, string $key): string
{
    return match ($key) {
        '__office_name' => asset_download_office_hierarchy($asset)['office_name'],
        '__category' => (string)($asset['category_name'] ?? ''),
        '__subcategory' => (string)($asset['subcategory_name'] ?? ''),
        default => trim((string)($asset['values'][$key] ?? '')),
    };
}

function asset_download_common_value_for_asset(array $asset, string $identifier, int $segmentId): string
{
    if ($identifier === '__office__') {
        $value = trim((string)(asset_download_office_hierarchy($asset)['office_name'] ?? ''));
        return $value === '' ? 'Blank' : $value;
    }
    $field = asset_download_field_for_label($identifier, $segmentId);
    if (!$field) {
        return 'Blank';
    }
    $value = trim((string)($asset['values'][(string)$field['field_key']] ?? ''));
    return $value === '' ? 'Blank' : $value;
}

function asset_download_sort_assets_by_common(array &$assets, array $sorts, int $segmentId): void
{
    if (!$sorts) {
        return;
    }
    usort($assets, static function (array $a, array $b) use ($sorts, $segmentId): int {
        foreach ($sorts as $sort) {
            $field = (string)($sort['field'] ?? '');
            if ($field === '') {
                continue;
            }
            $dir = strtolower((string)($sort['dir'] ?? 'asc')) === 'desc' ? -1 : 1;
            $left = asset_download_common_value_for_asset($a, $field, $segmentId);
            $right = asset_download_common_value_for_asset($b, $field, $segmentId);
            $cmp = strnatcasecmp($left, $right);
            if ($cmp !== 0) {
                return $cmp * $dir;
            }
        }
        return 0;
    });
}

function asset_download_sort_assets(array &$assets, array $sorts): void
{
    if (!$sorts) {
        return;
    }
    usort($assets, static function (array $a, array $b) use ($sorts): int {
        foreach ($sorts as $sort) {
            $field = (string)($sort['field'] ?? '');
            if ($field === '') {
                continue;
            }
            $dir = strtolower((string)($sort['dir'] ?? 'asc')) === 'desc' ? -1 : 1;
            $left = asset_download_sort_value($a, $field);
            $right = asset_download_sort_value($b, $field);
            $cmp = strnatcasecmp($left, $right);
            if ($cmp !== 0) {
                return $cmp * $dir;
            }
        }
        return 0;
    });
}

function asset_download_filename_tokens(array $asset, int $segmentId, string $fieldName = '', string $level1Label = '', string $level1Value = ''): array
{
    $office = asset_download_office_hierarchy($asset);
    $tokens = [
        'office_name' => $office['office_name'],
        'sub-division' => (string)($office['subdivision_name'] ?? ''),
        'division' => (string)($office['division_name'] ?? ''),
        'circle' => (string)($office['circle_name'] ?? ''),
        'zone' => (string)($office['zone_name'] ?? ''),
        'segment' => asset_template_segment_display_name($segmentId),
        'office_type' => (string)($asset['office_type_label'] ?? ''),
        'field_name' => $fieldName,
        'asset_number' => (string)($asset['asset_number'] ?? ''),
    ];
    foreach (asset_download_declared_field_token_map() as $label => $tokenKey) {
        $tokens[$tokenKey] = asset_download_common_value_for_asset($asset, $label, $segmentId);
    }
    return $tokens;
}

function asset_download_build_name(array $tokens): string
{
    $template = asset_download_filename_template();
    if (str_contains($template, '{')) {
        $used = false;
        $rendered = preg_replace_callback('/\{([a-z0-9_-]+)\}/i', static function (array $matches) use ($tokens, &$used): string {
            $token = strtolower(trim((string)($matches[1] ?? '')));
            $value = trim((string)($tokens[$token] ?? ''));
            if ($value === '') {
                return '';
            }
            $used = true;
            return asset_download_safe_name($value);
        }, $template);
        $rendered = preg_replace('/\s+/', ' ', (string)$rendered);
        $rendered = preg_replace('/[_\-\s]+/', '_', (string)$rendered);
        $rendered = trim((string)$rendered, '_.- ');
        if ($used && $rendered !== '') {
            return $rendered;
        }
    }
    $parts = [];
    foreach (asset_download_naming_tokens() as $token) {
        $value = trim((string)($tokens[$token] ?? ''));
        if ($value !== '') {
            $parts[] = asset_download_safe_name($value);
        }
    }
    return $parts ? implode('_', $parts) : 'download';
}

function asset_download_build_folder_parts(string $template, array $tokens): array
{
    $template = trim($template);
    if ($template === '') {
        return [];
    }
    $segments = preg_split('/\s*>\s*/', $template) ?: [];
    $parts = [];
    foreach ($segments as $segment) {
        $segment = trim((string)$segment);
        if ($segment === '') {
            continue;
        }
        $rendered = preg_replace_callback('/\{([a-z0-9_-]+)\}/i', static function (array $matches) use ($tokens): string {
            $token = strtolower(trim((string)($matches[1] ?? '')));
            return trim((string)($tokens[$token] ?? ''));
        }, $segment);
        $rendered = trim((string)$rendered);
        if ($rendered === '') {
            continue;
        }
        $safe = asset_download_safe_name($rendered);
        if ($safe !== '') {
            $parts[] = $safe;
        }
    }
    return $parts;
}

function asset_audit_level_choices(array $user, string $viewScope = 'my_office'): array
{
    $choices = ['count' => 'Count'];
    foreach (asset_download_level1_catalog($user, $viewScope) as $label => $_values) {
        $key = $label === 'Office' ? '__office__' : $label;
        $choices[$key] = $label;
    }
    return $choices;
}

function asset_audit_level_value_map(array $user, string $viewScope = 'my_office'): array
{
    $valueMap = [];
    foreach (asset_download_level1_catalog($user, $viewScope) as $label => $values) {
        $key = $label === 'Office' ? '__office__' : $label;
        $valueMap[$key] = array_values(array_filter(array_map('strval', $values), static fn(string $value): bool => trim($value) !== ''));
    }
    return $valueMap;
}

function asset_audit_field_has_entry(array $asset, array $field): bool
{
    $fieldKey = (string)($field['field_key'] ?? '');
    $fieldType = (string)($field['data_type'] ?? 'text');
    if ($fieldType === 'file') {
        return !empty($asset['files'][$fieldKey]);
    }
    return trim((string)($asset['values'][$fieldKey] ?? '')) !== '';
}

function asset_audit_count_value(array $asset, string $levelKey, int $segmentId): string
{
    if ($levelKey === '__office__') {
        return trim((string)(asset_download_office_hierarchy($asset)['office_name'] ?? ''));
    }
    return asset_download_common_value_for_asset($asset, $levelKey, $segmentId);
}

function asset_audit_count_cell(array $assets, array $field, string $levelKey, array $levelValueMap, int $segmentId): string
{
    $fieldKey = (string)($field['field_key'] ?? '');
    $fieldType = (string)($field['data_type'] ?? 'text');
    if ($levelKey === 'count') {
        if ($fieldType === 'file') {
            $totalFiles = 0;
            foreach ($assets as $asset) {
                $totalFiles += count((array)($asset['files'][$fieldKey] ?? []));
            }
            return (string)$totalFiles;
        }
        $count = 0;
        foreach ($assets as $asset) {
            if (asset_audit_field_has_entry($asset, $field)) {
                $count++;
            }
        }
        return (string)$count;
    }

    $totalLookup = [];
    foreach (($levelValueMap[$levelKey] ?? []) as $value) {
        $value = trim((string)$value);
        if ($value !== '') {
            $totalLookup[$value] = true;
        }
    }
    $matched = [];
    foreach ($assets as $asset) {
        if (!asset_audit_field_has_entry($asset, $field)) {
            continue;
        }
        $value = trim((string)asset_audit_count_value($asset, $levelKey, $segmentId));
        if ($value === '' || strcasecmp($value, 'Blank') === 0) {
            continue;
        }
        $matched[$value] = true;
    }
    $filledCount = count($matched);
    return (string)$filledCount;
}

function asset_audit_segments(array $user, string $viewScope = 'my_office'): array
{
    $segments = [];
    foreach (get_asset_segments(false) as $segment) {
        $segmentId = (int)$segment['id'];
        $fields = array_values(array_filter(
            get_asset_fields(false, $segmentId),
            static fn(array $field): bool => (int)($field['active_status'] ?? 0) === 1
        ));
        $assets = asset_download_accessible_assets_for_segment($segmentId, $user, $viewScope);
        $segments[] = [
            'segment' => $segment,
            'fields' => $fields,
            'assets' => $assets,
        ];
    }
    return $segments;
}

function asset_download_normalize_filter_values(array $values, array $allowed): array
{
    if (!$allowed) {
        return [];
    }
    $allowedLookup = array_fill_keys(array_map('strval', $allowed), true);
    $normalized = [];
    foreach ($values as $value) {
        $value = (string)$value;
        if ($value !== '' && isset($allowedLookup[$value])) {
            $normalized[$value] = $value;
        }
    }
    return array_values($normalized);
}

function asset_download_normalize_filter_criteria(array $criteria, array $filterMeta): array
{
    $type = (string)($filterMeta['data_type'] ?? 'text');
    if ($type === 'office') {
        $normalized = [];
        foreach (['zone_ids', 'circle_ids', 'division_ids', 'subdivision_ids'] as $key) {
            $ids = array_values(array_filter(array_map('intval', (array)($criteria[$key] ?? [])), static fn(int $id): bool => $id > 0));
            if ($ids) {
                $normalized[$key] = array_values(array_unique($ids));
            }
        }
        return $normalized;
    }
    if ($type === 'date' || $type === 'number') {
        $normalized = [];
        $from = trim((string)($criteria['from'] ?? ''));
        $to = trim((string)($criteria['to'] ?? ''));
        if ($from !== '') {
            $normalized['from'] = $from;
        }
        if ($to !== '') {
            $normalized['to'] = $to;
        }
        if (!empty($criteria['blank'])) {
            $normalized['blank'] = '1';
        }
        return $normalized;
    }
    if ($type === 'file') {
        $values = asset_download_normalize_filter_values(
            (array)($criteria['values'] ?? []),
            ['__has_file__', '__no_file__']
        );
        return $values ? ['values' => $values] : [];
    }

    $allowed = array_map('strval', array_keys((array)($filterMeta['options'] ?? [])));
    if ($allowed === []) {
        $values = [];
        foreach ((array)($criteria['values'] ?? []) as $value) {
            $value = trim((string)$value);
            if ($value !== '') {
                $values[$value] = $value;
            }
        }
        if (!empty($criteria['blank'])) {
            $values['__blank__'] = '__blank__';
        }
        return $values ? ['values' => array_values($values)] : [];
    }
    if (!empty($filterMeta['has_blank'])) {
        $allowed[] = '__blank__';
    }
    $values = asset_download_normalize_filter_values((array)($criteria['values'] ?? []), $allowed);
    return $values ? ['values' => $values] : [];
}

function asset_download_request_common_filter_meta(): array
{
    $meta = [
        '__office__' => [
            'data_type' => 'office',
        ],
    ];

    foreach (asset_download_common_label_candidates() as $label) {
        foreach (get_asset_segments(false) as $segment) {
            $segmentId = (int)$segment['id'];
            $field = asset_download_field_for_label($label, $segmentId);
            if (!$field) {
                continue;
            }
            $meta[$label] = [
                'data_type' => (string)($field['data_type'] ?? 'text'),
                'has_blank' => true,
            ];
            break;
        }
    }

    return $meta;
}

function asset_download_normalize_common_filters(array $inputFilters, array $catalog): array
{
    $normalized = [];
    foreach ($catalog as $identifier => $filterMeta) {
        $criteria = (array)($inputFilters[$identifier] ?? []);
        if (!$criteria) {
            continue;
        }
        $normalizedCriteria = asset_download_normalize_filter_criteria($criteria, $filterMeta);
        if ($normalizedCriteria) {
            $normalized[$identifier] = $normalizedCriteria;
        }
    }
    return $normalized;
}

function asset_download_normalize_segment_filters(array $inputFilters, array $allowedFieldMap, array $selectedFieldKeys): array
{
    $selectedLookup = array_fill_keys(array_map('strval', $selectedFieldKeys), true);
    $normalized = [];
    foreach ($allowedFieldMap as $fieldKey => $fieldMeta) {
        if (!isset($selectedLookup[(string)$fieldKey])) {
            continue;
        }
        $criteria = (array)($inputFilters[$fieldKey] ?? []);
        if (!$criteria) {
            continue;
        }
        $filterMeta = [
            'data_type' => (string)($fieldMeta['data_type'] ?? 'text'),
            'options' => (array)($fieldMeta['options'] ?? []),
            'has_blank' => !empty($fieldMeta['has_blank']),
        ];
        $normalizedCriteria = asset_download_normalize_filter_criteria($criteria, $filterMeta);
        if ($normalizedCriteria) {
            $normalized[(string)$fieldKey] = $normalizedCriteria;
        }
    }
    return $normalized;
}

function asset_download_file_summary(array $asset, string $fieldKey): string
{
    $counts = [];
    foreach (($asset['files'][$fieldKey] ?? []) as $fileRow) {
        $ext = strtolower(trim((string)($fileRow['file_ext'] ?? '')));
        $ext = $ext !== '' ? $ext : 'file';
        if (!isset($counts[$ext])) {
            $counts[$ext] = 0;
        }
        $counts[$ext]++;
    }
    if (!$counts) {
        return 'No file';
    }
    ksort($counts, SORT_NATURAL | SORT_FLAG_CASE);
    $parts = [];
    foreach ($counts as $ext => $count) {
        $label = $count === 1 ? $ext : $ext . 's';
        $parts[] = $count . ' ' . $label;
    }
    return implode(', ', $parts);
}

function asset_download_request_from_input(array $input, array $user, string $viewScope = 'my_office'): array
{
    $errors = [];
    $output = strtolower(trim((string)($input['download_output'] ?? 'excel')));
    if (!in_array($output, ['excel', 'pdf', 'zip'], true)) {
        $output = 'excel';
    }
    $level1Catalog = null;
    $level1Labels = array_values(array_unique(array_merge(['Office'], asset_download_selected_level1_labels())));
    $level1Label = trim((string)($input['download_level1_label'] ?? ''));
    if ($output !== 'zip' && ($level1Label === '' || !in_array($level1Label, $level1Labels, true))) {
        $errors[] = 'Valid Level 1 field is required.';
    }
    $level1Values = array_values(array_unique(array_filter(array_map(
        static fn($value): string => trim((string)$value),
        (array)($input['download_level1_values'] ?? [])
    ), static fn(string $value): bool => $value !== '')));

    $commonOptionMap = asset_download_common_option_map();
    $level1CommonKey = $level1Label === 'Office' ? '__office__' : $level1Label;
    $commonColumns = [];
    $commonSorts = [];
    if ($output !== 'zip') {
        $commonColumnSelections = array_values(array_filter(array_map('strval', (array)($input['download_common_columns'] ?? []))));
        $commonColumnSelections = array_values(array_filter($commonColumnSelections, static fn(string $value): bool => isset($commonOptionMap[$value])));
        foreach ($commonColumnSelections as $identifier) {
            if ($identifier === $level1CommonKey) {
                continue;
            }
            $commonColumns[$identifier] = [
                'field' => $identifier,
                'label' => (string)$commonOptionMap[$identifier],
                'order' => max(1, (int)($input['download_common_column_order'][$identifier] ?? 999)),
            ];
        }
        if (!$commonColumns) {
            $fallbackOrder = 1;
            foreach ($commonOptionMap as $identifier => $label) {
                if ($identifier === $level1CommonKey) {
                    continue;
                }
                $commonColumns[$identifier] = [
                    'field' => $identifier,
                    'label' => (string)$label,
                    'order' => $fallbackOrder++,
                ];
            }
        }
        uasort($commonColumns, static function (array $a, array $b): int {
            $cmp = ((int)$a['order']) <=> ((int)$b['order']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strnatcasecmp((string)$a['label'], (string)$b['label']);
        });
        $commonColumns = array_values($commonColumns);

        foreach ((array)($input['download_common_sort'] ?? []) as $identifier => $row) {
            $identifier = (string)$identifier;
            if (!isset($commonOptionMap[$identifier]) || $identifier === $level1CommonKey) {
                continue;
            }
            if (empty($row['enabled'])) {
                continue;
            }
            $commonSorts[] = [
                'field' => $identifier,
                'label' => (string)$commonOptionMap[$identifier],
                'order' => max(1, (int)($row['order'] ?? 999)),
                'dir' => strtolower((string)($row['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc',
            ];
        }
        usort($commonSorts, static function (array $a, array $b): int {
            $cmp = ((int)$a['order']) <=> ((int)$b['order']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strnatcasecmp((string)$a['label'], (string)$b['label']);
        });
    }

    $selectedSegmentIds = array_values(array_filter(array_map('intval', (array)($input['download_segments'] ?? []))));
    if (!$selectedSegmentIds) {
        $errors[] = 'Select at least one segment.';
    }

    $segments = [];
    foreach ($selectedSegmentIds as $segmentId) {
        $segment = get_asset_segment($segmentId);
        if (!$segment || (int)($segment['active_status'] ?? 0) !== 1) {
            continue;
        }
        $allFields = get_asset_fields(false, $segmentId);
        $selectedFieldKeys = array_values(array_filter(array_map('strval', (array)($input['download_selected_fields'][$segmentId] ?? []))));
        $fieldKeyMap = [];
        foreach ($allFields as $field) {
            $fieldKeyMap[(string)$field['field_key']] = $field;
        }
        $selectedFieldKeys = array_values(array_filter($selectedFieldKeys, static fn(string $key): bool => $key !== '' && isset($fieldKeyMap[$key])));
        $segmentFilterMetaMap = [];
        foreach (asset_download_effective_filter_fields($segmentId) as $filterField) {
            $segmentFilterKey = (string)$filterField['field_key'];
            if (isset($fieldKeyMap[$segmentFilterKey])) {
                $segmentFilterMetaMap[$segmentFilterKey] = $fieldKeyMap[$segmentFilterKey];
            }
        }

        $zipSelected = [];
        foreach ($selectedFieldKeys as $fieldKey) {
            if ((string)($fieldKeyMap[$fieldKey]['data_type'] ?? '') === 'file') {
                $zipSelected[] = $fieldKey;
            }
        }
        if ($output === 'zip' && !$zipSelected) {
            $errors[] = 'Select at least one file field for ZIP in segment ' . (string)$segment['segment_name'] . '.';
        }

        $segments[$segmentId] = [
            'segment' => $segment,
            'fields' => $allFields,
            'selected_field_keys' => $selectedFieldKeys,
            'selected_zip_field_keys' => $zipSelected,
            'filters' => asset_download_normalize_segment_filters(
                (array)($input['download_filters'][$segmentId] ?? []),
                $segmentFilterMetaMap,
                $selectedFieldKeys
            ),
        ];
    }
    if (!$segments) {
        $errors[] = 'No valid segments selected.';
    }

    return [
        'errors' => array_values(array_unique($errors)),
        'request' => [
            'output' => $output,
            'zip_use_hierarchy' => !empty($input['download_zip_use_hierarchy']),
            'zip_folder_template' => trim((string)($input['download_zip_folder_template'] ?? '')),
            'level1_label' => $level1Label,
            'level1_values' => $level1Values,
            'common_filters' => asset_download_normalize_common_filters(
                (array)($input['download_common_filters'] ?? []),
                asset_download_request_common_filter_meta()
            ),
            'common_columns' => $commonColumns,
            'common_sorts' => $commonSorts,
            'segments' => $segments,
            'view_scope' => $viewScope,
        ],
    ];
}

function asset_download_dataset(array $request, array $user): array
{
    $level1Label = (string)$request['level1_label'];
    $selectedLevel1Values = array_flip(array_map('strval', (array)$request['level1_values']));
    $groups = [];
    foreach ($request['segments'] as $segmentId => $segmentConfig) {
        $assets = asset_download_accessible_assets_for_segment((int)$segmentId, $user, (string)$request['view_scope']);
        $filterFieldMap = [];
        foreach (asset_download_effective_filter_fields((int)$segmentId) as $field) {
            $filterFieldMap[(string)$field['field_key']] = $field;
        }
        $segmentRowsByGroup = [];
        foreach ($assets as $asset) {
            if (!asset_download_matches_common_filters($asset, (int)$segmentId, (array)($request['common_filters'] ?? []))) {
                continue;
            }
            $level1Value = $level1Label !== ''
                ? asset_download_level1_value_for_asset($asset, $level1Label, (int)$segmentId)
                : 'files';
            if ($level1Label !== '' && $selectedLevel1Values && !isset($selectedLevel1Values[$level1Value])) {
                continue;
            }
            if (!asset_download_matches_segment_filters($asset, $filterFieldMap, (array)$segmentConfig['filters'])) {
                continue;
            }
            $segmentRowsByGroup[$level1Value][] = $asset;
        }
        foreach ($segmentRowsByGroup as $groupValue => &$segmentAssets) {
            asset_download_sort_assets_by_common($segmentAssets, (array)($request['common_sorts'] ?? []), (int)$segmentId);
        }
        unset($segmentAssets);
        foreach ($segmentRowsByGroup as $groupValue => $segmentAssets) {
            $groups[$groupValue]['level1_value'] = $groupValue;
            $groups[$groupValue]['segments'][$segmentId] = [
                'segment' => $segmentConfig['segment'],
                'assets' => $segmentAssets,
                'selected_field_keys' => $segmentConfig['selected_field_keys'],
                'selected_zip_field_keys' => $segmentConfig['selected_zip_field_keys'],
            ];
        }
    }
    if (!$groups && $level1Label !== '') {
        foreach ((array)$request['level1_values'] as $value) {
            $groups[$value] = ['level1_value' => $value, 'segments' => []];
        }
    } elseif (!$groups) {
        $groups['files'] = ['level1_value' => 'files', 'segments' => []];
    }
    uksort($groups, 'strnatcasecmp');
    return $groups;
}

function asset_download_table_headers(array $selectedFieldKeys, int $segmentId, array $commonColumns = [], string $level1Label = '', bool $includeLevel1Column = true): array
{
    $headers = ['serial' => 'SL No'];
    if ($includeLevel1Column && $level1Label !== '') {
        $headers['__level1'] = $level1Label;
    }
    foreach ($commonColumns as $commonColumn) {
        $field = (string)($commonColumn['field'] ?? '');
        $label = (string)($commonColumn['label'] ?? '');
        if ($field === '' || $label === '') {
            continue;
        }
        $headers['__common__' . $field] = $label;
    }
    if (asset_category_selection_enabled($segmentId)) {
        $headers['category'] = 'Category';
    }
    if (asset_subcategory_enabled($segmentId)) {
        $headers['subcategory'] = 'Sub-category';
    }
    foreach (get_asset_fields(false, $segmentId) as $field) {
        $fieldKey = (string)$field['field_key'];
        if (!in_array($fieldKey, $selectedFieldKeys, true)) {
            continue;
        }
        if (trim((string)$field['label']) === $level1Label) {
            continue;
        }
        $headers[$fieldKey] = (string)$field['label'];
    }
    return $headers;
}

function asset_download_table_rows(array $assets, array $selectedFieldKeys, int $segmentId, array $commonColumns = [], string $level1Label = '', bool $includeLevel1Column = true): array
{
    $rows = [];
    $fieldMap = [];
    foreach (get_asset_fields(false, $segmentId) as $field) {
        $fieldMap[(string)$field['field_key']] = $field;
    }
    foreach ($assets as $index => $asset) {
        $row = ['serial' => $index + 1];
        if ($includeLevel1Column && $level1Label !== '') {
            $row['__level1'] = asset_download_level1_value_for_asset($asset, $level1Label, $segmentId);
        }
        foreach ($commonColumns as $commonColumn) {
            $field = (string)($commonColumn['field'] ?? '');
            if ($field === '') {
                continue;
            }
            $row['__common__' . $field] = asset_download_common_value_for_asset($asset, $field, $segmentId);
        }
        if (asset_category_selection_enabled($segmentId)) {
            $row['category'] = (string)($asset['category_name'] ?? '');
        }
        if (asset_subcategory_enabled($segmentId)) {
            $row['subcategory'] = (string)($asset['subcategory_name'] ?? '');
        }
        foreach ($selectedFieldKeys as $fieldKey) {
            $field = $fieldMap[$fieldKey] ?? null;
            if (!$field) {
                continue;
            }
            if (trim((string)$field['label']) === $level1Label) {
                continue;
            }
            if ((string)($field['data_type'] ?? '') === 'file') {
                $row[$fieldKey] = asset_download_file_summary($asset, $fieldKey);
            } else {
                $row[$fieldKey] = (string)($asset['values'][$fieldKey] ?? '');
            }
        }
        $rows[] = $row;
    }
    return $rows;
}

function asset_download_prepare_runtime(string $mode = 'download'): string
{
    if (function_exists('ignore_user_abort')) {
        @ignore_user_abort(true);
    }
    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }
    @ini_set('max_execution_time', '0');
    @ini_set('memory_limit', '1024M');
    $cacheDir = __DIR__ . '/../../storage/runtime/' . $mode;
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }
    return $cacheDir;
}

function asset_download_filtered_assets_for_segment(int $segmentId, array $request, array $user): array
{
    $level1Label = (string)($request['level1_label'] ?? '');
    $selectedLevel1Values = array_flip(array_map('strval', (array)($request['level1_values'] ?? [])));
    $segmentConfig = (array)($request['segments'][$segmentId] ?? []);
    if (!$segmentConfig) {
        return [];
    }
    $assets = asset_download_accessible_assets_for_segment($segmentId, $user, (string)($request['view_scope'] ?? 'my_office'));
    $filterFieldMap = [];
    foreach (asset_download_effective_filter_fields($segmentId) as $field) {
        $filterFieldMap[(string)$field['field_key']] = $field;
    }
    $matched = [];
    foreach ($assets as $asset) {
        if (!asset_download_matches_common_filters($asset, $segmentId, (array)($request['common_filters'] ?? []))) {
            continue;
        }
        $level1Value = $level1Label !== ''
            ? asset_download_level1_value_for_asset($asset, $level1Label, $segmentId)
            : 'files';
        if ($level1Label !== '' && $selectedLevel1Values && !isset($selectedLevel1Values[$level1Value])) {
            continue;
        }
        if (!asset_download_matches_segment_filters($asset, $filterFieldMap, (array)($segmentConfig['filters'] ?? []))) {
            continue;
        }
        $matched[] = $asset;
    }
    asset_download_sort_assets_by_common($matched, (array)($request['common_sorts'] ?? []), $segmentId);
    return $matched;
}

function asset_download_append_excel_rows(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $startRow, array $assets, array $selectedFieldKeys, int $segmentId, array $commonColumns = [], string $level1Label = '', bool $includeLevel1Column = true): int
{
    $headers = asset_download_table_headers($selectedFieldKeys, $segmentId, $commonColumns, $level1Label, $includeLevel1Column);
    $fieldMap = [];
    foreach (get_asset_fields(false, $segmentId) as $field) {
        $fieldMap[(string)$field['field_key']] = $field;
    }
    $rowNum = $startRow;
    foreach ($assets as $index => $asset) {
        $col = 1;
        foreach (array_keys($headers) as $key) {
            $value = '';
            if ($key === 'serial') {
                $value = (string)($index + 1);
            } elseif ($key === '__level1') {
                $value = asset_download_level1_value_for_asset($asset, $level1Label, $segmentId);
            } elseif (str_starts_with($key, '__common__')) {
                $commonField = substr($key, 10);
                $value = asset_download_common_value_for_asset($asset, $commonField, $segmentId);
            } elseif ($key === 'category') {
                $value = (string)($asset['category_name'] ?? '');
            } elseif ($key === 'subcategory') {
                $value = (string)($asset['subcategory_name'] ?? '');
            } else {
                $field = $fieldMap[$key] ?? null;
                if ($field && (string)($field['data_type'] ?? '') === 'file') {
                    $value = asset_download_file_summary($asset, $key);
                } else {
                    $value = (string)($asset['values'][$key] ?? '');
                }
            }
            $sheet->setCellValueExplicit([$col, $rowNum], $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $col++;
        }
        $rowNum++;
    }
    return $rowNum;
}

function asset_download_export_excel(array $request, array $user): void
{
    ensure_library('PhpOffice\\PhpSpreadsheet\\Spreadsheet', 'PhpSpreadsheet is not installed.');
    $cacheDir = asset_download_prepare_runtime('excel_export');
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    \PhpOffice\PhpSpreadsheet\Settings::setLocale('en');
    $sheetIndex = 0;
    foreach ($request['segments'] as $segmentId => $segmentConfig) {
        $sheet = $sheetIndex === 0 ? $book->getActiveSheet() : $book->createSheet($sheetIndex);
        $safeTitle = substr(preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', asset_download_safe_name((string)$segmentConfig['segment']['segment_name'])), 0, 31);
        $sheet->setTitle($safeTitle !== '' ? $safeTitle : ('Segment' . ($sheetIndex + 1)));
        $headers = asset_download_table_headers(
            $segmentConfig['selected_field_keys'],
            (int)$segmentId,
            (array)($request['common_columns'] ?? []),
            (string)$request['level1_label'],
            true
        );
        $rowNum = 1;
        $col = 1;
        foreach ($headers as $header) {
            $sheet->setCellValue([$col, $rowNum], $header);
            $col++;
        }
        $rowNum++;
        $matchedAssets = asset_download_filtered_assets_for_segment((int)$segmentId, $request, $user);
        $rowNum = asset_download_append_excel_rows(
            $sheet,
            $rowNum,
            $matchedAssets,
            $segmentConfig['selected_field_keys'],
            (int)$segmentId,
            (array)($request['common_columns'] ?? []),
            (string)$request['level1_label'],
            true
        );
        $sheetIndex++;
    }
    $tmpFile = tempnam($cacheDir, 'xlsx_');
    if ($tmpFile === false) {
        throw new RuntimeException('Unable to prepare temporary Excel file.');
    }
    $xlsxPath = $tmpFile . '.xlsx';
    @unlink($xlsxPath);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . asset_download_build_name([
        'segment' => 'download',
        'field_name' => 'report',
        'office_name' => '',
        'asset_number' => date('Ymd_His'),
    ]) . '.xlsx"');
    header('Cache-Control: max-age=0');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book);
    $writer->setPreCalculateFormulas(false);
    $writer->setUseDiskCaching(true, $cacheDir);
    $writer->save($xlsxPath);
    $book->disconnectWorksheets();
    unset($book);
    header('Content-Length: ' . (string)filesize($xlsxPath));
    readfile($xlsxPath);
    @unlink($xlsxPath);
    @unlink($tmpFile);
    exit;
}

function asset_download_export_pdf(array $request, array $groups): void
{
    $html = '<html><head><meta charset="utf-8"><style>'
        . '@page{size:A4 landscape;margin:20px 18px 28px 18px;}'
        . 'body{font-family:DejaVu Sans,Arial,sans-serif;font-size:9px;color:#111;}'
        . 'h2,h3{margin:0 0 8px;}'
        . '.group{page-break-after:always;}'
        . '.group:last-child{page-break-after:auto;}'
        . '.group-head{margin-bottom:10px;padding-bottom:6px;border-bottom:2px solid #1f4f82;}'
        . '.segment-block{margin-bottom:16px;}'
        . 'table{width:100%;border-collapse:collapse;table-layout:fixed;margin-bottom:12px;}'
        . 'table.compact{font-size:8px;}'
        . 'table.tight{font-size:7px;}'
        . 'thead{display:table-header-group;}'
        . 'tr{page-break-inside:avoid;}'
        . 'th,td{border:1px solid #444;padding:4px;vertical-align:top;text-align:left;word-wrap:break-word;word-break:break-word;white-space:pre-wrap;overflow-wrap:anywhere;}'
        . 'th{background:#eef4fb;font-weight:700;}'
        . '.muted{color:#666;}'
        . '.page-footer{position:fixed;bottom:-12px;left:0;right:0;text-align:right;font-size:9px;color:#444;}'
        . '</style></head><body>'
        . '<div class="page-footer">Page <span class="page-number"></span></div>';
    foreach ($groups as $groupValue => $group) {
        $html .= '<section class="group"><h2>' . e((string)$request['level1_label']) . ': ' . e((string)$groupValue) . '</h2>';
        foreach ($request['segments'] as $segmentId => $segmentConfig) {
            $segmentData = $group['segments'][$segmentId] ?? null;
            if (!$segmentData) {
                continue;
            }
            $headers = asset_download_table_headers(
                $segmentConfig['selected_field_keys'],
                (int)$segmentId,
                (array)($request['common_columns'] ?? []),
                (string)$request['level1_label'],
                false
            );
            $rows = asset_download_table_rows(
                $segmentData['assets'],
                $segmentConfig['selected_field_keys'],
                (int)$segmentId,
                (array)($request['common_columns'] ?? []),
                (string)$request['level1_label'],
                false
            );
            $headerCount = count($headers);
            $tableClass = $headerCount >= 14 ? 'tight' : ($headerCount >= 10 ? 'compact' : '');
            $html .= '<div class="segment-block"><h3>' . e((string)$segmentConfig['segment']['segment_name']) . '</h3>';
            $html .= '<table' . ($tableClass !== '' ? ' class="' . $tableClass . '"' : '') . '><thead><tr>';
            foreach ($headers as $header) {
                $html .= '<th>' . e((string)$header) . '</th>';
            }
            $html .= '</tr></thead><tbody>';
            foreach ($rows as $row) {
                $html .= '<tr>';
                foreach (array_keys($headers) as $key) {
                    $html .= '<td>' . e((string)($row[$key] ?? '')) . '</td>';
                }
                $html .= '</tr>';
            }
            if (!$rows) {
                $html .= '<tr><td colspan="' . count($headers) . '" class="muted">No rows found.</td></tr>';
            }
            $html .= '</tbody></table></div>';
        }
        $html .= '</section>';
    }
    $html .= '<script type="text/php">if (isset($pdf)) { $font = $fontMetrics->getFont("Helvetica", "normal"); $pdf->page_text(760, 575, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 9, array(0,0,0)); }</script></body></html>';
    export_pdf($html, asset_download_build_name([
        'segment' => 'download',
        'field_name' => 'report',
        'office_name' => '',
        'asset_number' => date('Ymd_His'),
    ]) . '.pdf');
}

function asset_download_export_zip(array $request, array $groups): void
{
    $tmpFile = tempnam(sys_get_temp_dir(), 'assetdl_');
    if ($tmpFile === false) {
        throw new RuntimeException('Unable to create temporary ZIP file.');
    }
    $zipPath = $tmpFile . '.zip';
    @unlink($zipPath);
    $zip = new \ZipArchive();
    if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
        @unlink($tmpFile);
        throw new RuntimeException('Unable to create ZIP archive.');
    }
    $usedNames = [];
    $addedFileCount = 0;
    $useHierarchy = !empty($request['zip_use_hierarchy']);
    $folderTemplate = trim((string)($request['zip_folder_template'] ?? ''));
    foreach ($groups as $groupValue => $group) {
        $groupFolder = asset_download_safe_name((string)$groupValue);
        foreach ($request['segments'] as $segmentId => $segmentConfig) {
            $segmentData = $group['segments'][$segmentId] ?? null;
            if (!$segmentData) {
                continue;
            }
            foreach ($segmentData['assets'] as $assetIndex => $asset) {
                $folderParts = [];
                if (!$useHierarchy) {
                    $folderParts[] = 'files';
                }
                foreach ($segmentConfig['selected_zip_field_keys'] as $fieldKey) {
                    $fieldMeta = null;
                    foreach ($segmentConfig['fields'] as $field) {
                        if ((string)($field['field_key'] ?? '') === $fieldKey) {
                            $fieldMeta = $field;
                            break;
                        }
                    }
                    $fieldLabel = (string)($fieldMeta['label'] ?? $fieldKey);
                    $fieldFiles = array_values($asset['files'][$fieldKey] ?? []);
                    $fieldFileCount = count($fieldFiles);
                    foreach ($fieldFiles as $fileIndex => $fileRow) {
                        $path = asset_file_storage_dir() . '/' . (string)$fileRow['stored_name'];
                        if (!is_file($path)) {
                            continue;
                        }
                        $tokens = asset_download_filename_tokens($asset, (int)$segmentId, $fieldLabel, (string)$request['level1_label'], (string)$groupValue);
                        if ($useHierarchy) {
                            $folderParts = asset_download_build_folder_parts($folderTemplate, $tokens);
                            if (!$folderParts) {
                                $folderParts = ['files'];
                            }
                        }
                        $name = asset_download_build_name($tokens);
                        if ($fieldFileCount > 1) {
                            $name .= '_' . ($fileIndex + 1);
                        }
                        $ext = strtolower(trim((string)($fileRow['file_ext'] ?? '')));
                        if ($ext !== '') {
                            $name .= '.' . $ext;
                        }
                        $internal = implode('/', array_merge($folderParts, [$name]));
                        $counter = 2;
                        while (isset($usedNames[$internal])) {
                            $base = preg_replace('/\\.[^.]+$/', '', $name);
                            $suffix = $ext !== '' ? '.' . $ext : '';
                            $internal = implode('/', array_merge($folderParts, [$base . '_' . $counter . $suffix]));
                            $counter++;
                        }
                        $usedNames[$internal] = true;
                        if ($zip->addFile($path, $internal)) {
                            $addedFileCount++;
                        }
                    }
                }
            }
        }
    }
    if ($addedFileCount === 0) {
        $zip->addFromString('README.txt', "No files matched the selected download settings.\n");
    }
    $zip->close();
    @unlink($tmpFile);
    if (!is_file($zipPath)) {
        throw new RuntimeException('ZIP archive could not be created.');
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . asset_download_build_name([
        'segment' => 'download',
        'field_name' => 'files',
        'office_name' => '',
        'asset_number' => date('Ymd_His'),
    ]) . '.zip"');
    header('Content-Length: ' . (string)filesize($zipPath));
    readfile($zipPath);
    @unlink($zipPath);
    exit;
}

function asset_handle_hierarchical_download(array $input, array $user, string $viewScope = 'my_office'): void
{
    $parsed = asset_download_request_from_input($input, $user, $viewScope);
    if ($parsed['errors']) {
        throw new RuntimeException(implode(' ', $parsed['errors']));
    }
    $request = $parsed['request'];
    asset_download_prepare_runtime((string)($request['output'] ?? 'download'));
    if ($request['output'] === 'excel') {
        asset_download_export_excel($request, $user);
    }
    $groups = asset_download_dataset($request, $user);
    if ($request['output'] === 'pdf') {
        asset_download_export_pdf($request, $groups);
    }
    if ($request['output'] === 'zip') {
        asset_download_export_zip($request, $groups);
    }
    asset_download_export_excel($request, $user);
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

function validate_office_asset_declaration_requirements(int $officeType, int $officeId, ?int $segmentId = null, ?array $user = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $user = $user ?: current_user();
    $finalRequiredFields = array_values(array_filter(
        get_asset_fields(true, $segmentId),
        static fn(array $field): bool => (int)($field['active_status'] ?? 0) === 1 && asset_is_final_submission_required($field)
    ));
    if (!$finalRequiredFields) {
        return ['row_count' => 0, 'message' => ''];
    }

    $assets = get_assets([
        'segment_id' => $segmentId,
        'office_view_scope' => 'my_office',
        'office_type' => $officeType,
        'office_id' => $officeId,
    ], $user, false);
    if (!$assets) {
        return ['row_count' => 0, 'message' => ''];
    }

    $missingLabels = [];
    $rowCount = 0;
    foreach ($assets as $asset) {
        $rowHasMissing = false;
        foreach ($finalRequiredFields as $field) {
            $fieldKey = (string)$field['field_key'];
            $isMissing = false;
            if ((string)($field['data_type'] ?? '') === 'file') {
                $isMissing = empty($asset['files'][$fieldKey]);
            } else {
                $isMissing = trim((string)($asset['values'][$fieldKey] ?? '')) === '';
            }
            if (!$isMissing) {
                continue;
            }
            $missingLabels[] = asset_label_for_submission_message($field);
            $rowHasMissing = true;
        }
        if ($rowHasMissing) {
            $rowCount++;
        }
    }

    if ($rowCount <= 0) {
        return ['row_count' => 0, 'message' => ''];
    }

    return [
        'row_count' => $rowCount,
        'message' => 'Action Required before submission: Missing ' . asset_quote_label_list($missingLabels) . ' in ' . $rowCount . ' row' . ($rowCount === 1 ? '' : 's') . '.',
    ];
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
    $columns = [];
    if (asset_category_selection_enabled($segmentId)) {
        $columns[] = ['key' => 'category', 'label' => 'Category / Category'];
    }
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
    $sheet = null;
    foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
        if (strcasecmp(trim((string)$worksheet->getTitle()), 'data') === 0) {
            $sheet = $worksheet;
            break;
        }
    }
    if (!$sheet) {
        return ['Template must contain a sheet named data.'];
    }
    $rows = $sheet->toArray(null, false, false, true);
    if (!$rows) {
        return ['Data sheet is empty.'];
    }
    $expectedCount = count(asset_template_columns($segmentId));
    $headerCount = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn(1));
    if ($headerCount !== $expectedCount) {
        return ['Data sheet column count must match the current field count plus SL and Instruction columns.'];
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

    $rows = $sheet->toArray(null, false, false, true);
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
    set_asset_template_source(asset_template_source_uploaded(), $segmentId);
    return $syncSummary;
}

function output_asset_template_download(string $mode = 'selected', ?int $segmentId = null): void
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $stored = asset_template_uploaded_info($segmentId);
    $mode = strtolower(trim($mode));
    $shouldDownloadUploaded = false;
    if ($mode === 'uploaded') {
        $shouldDownloadUploaded = $stored !== null;
    } elseif ($mode === 'selected') {
        $shouldDownloadUploaded = $stored !== null && asset_template_prefers_uploaded($segmentId);
    }
    if ($shouldDownloadUploaded && $stored) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="asset_import_template.xlsx"');
        header('Content-Length: ' . (string)$stored['size']);
        header('Cache-Control: max-age=0');
        readfile($stored['path']);
        exit;
    }
    $spreadsheet = build_asset_template_autogen_workbook($segmentId);
    output_asset_template_autogen_download($spreadsheet, asset_template_download_filename($segmentId));
}

function asset_template_row_limit(): int
{
    return 1000;
}

function asset_template_download_filename(?int $segmentId = null): string
{
    $base = asset_template_segment_display_name($segmentId);
    $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $base);
    $safe = trim((string)$safe, '_');
    if ($safe === '') {
        $safe = 'segment';
    }
    return strtolower($safe) . '_autogen.xlsx';
}

function asset_template_segment_display_name(?int $segmentId = null): string
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $segment = asset_active_segment($segmentId, true);
    $name = trim((string)($segment['segment_name'] ?? ''));
    return $name !== '' ? $name : 'General';
}

function asset_template_safe_sheet_name(string $name, array $taken = []): string
{
    $name = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', $name);
    $name = trim((string)$name);
    if ($name === '') {
        $name = 'Sheet';
    }
    $name = mb_substr($name, 0, 31);
    $base = $name;
    $counter = 1;
    while (in_array(mb_strtolower($name, 'UTF-8'), array_map(static fn(string $item): string => mb_strtolower($item, 'UTF-8'), $taken), true)) {
        $suffix = '_' . $counter;
        $name = mb_substr($base, 0, max(1, 31 - mb_strlen($suffix, 'UTF-8'))) . $suffix;
        $counter++;
    }
    return $name;
}

function asset_template_data_sheet_name(?int $segmentId = null): string
{
    return asset_template_safe_sheet_name(asset_template_segment_display_name($segmentId) . '_autogen');
}

function asset_template_dropdown_sheet_name(?int $segmentId = null, array $taken = []): string
{
    return asset_template_safe_sheet_name(asset_template_segment_display_name($segmentId) . '_autogen_dropdowns', $taken);
}

function asset_template_named_token(string $value): string
{
    $value = strtoupper(trim($value));
    $value = preg_replace('/[^A-Z0-9_]+/', '_', $value);
    $value = preg_replace('/_+/', '_', (string)$value);
    $value = trim((string)$value, '_');
    if ($value === '') {
        $value = 'ITEM';
    }
    if (preg_match('/^[0-9]/', $value)) {
        $value = 'N_' . $value;
    }
    return $value;
}

function asset_template_formula_escape(string $value): string
{
    return str_replace('"', '""', $value);
}

function asset_template_formula_named_token_expr(string $cellReference): string
{
    $expr = 'TRIM(' . $cellReference . ')';
    foreach ([' ', '-', '/', '&', '(', ')', ',', '.', "'", ':'] as $char) {
        $expr = 'SUBSTITUTE(' . $expr . ',"' . asset_template_formula_escape($char) . '","_")';
    }
    return 'UPPER(' . $expr . ')';
}

function asset_template_input_column_definitions(?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $columns = [];
    if (asset_category_selection_enabled($segmentId)) {
        $columns[] = [
            'key' => 'category',
            'label' => 'Category / Category',
            'data_type' => 'dropdown',
            'required_input' => true,
            'required_final' => true,
            'non_editable' => false,
            'instruction_label' => 'Category',
            'options' => array_map(static fn(array $row): string => (string)$row['name'], get_asset_categories(false, $segmentId)),
            'validation_kind' => 'category',
        ];
    }
    if (asset_subcategory_enabled($segmentId)) {
        $columns[] = [
            'key' => 'subcategory',
            'label' => 'Sub-category / Sub-category',
            'data_type' => 'dropdown',
            'required_input' => true,
            'required_final' => true,
            'non_editable' => false,
            'instruction_label' => 'Sub-category',
            'parent_key' => asset_category_selection_enabled($segmentId) ? 'category' : null,
            'validation_kind' => 'subcategory',
        ];
    }

    $fields = get_asset_fields(false, $segmentId);
    $fieldById = [];
    foreach ($fields as $field) {
        $fieldById[(int)$field['id']] = $field;
    }
    foreach ($fields as $field) {
        if ((int)$field['is_import_enabled'] !== 1 || (int)$field['active_status'] !== 1) {
            continue;
        }
        $parentId = (int)($field['secondary_of_field_id'] ?? 0);
        $dataType = (string)$field['data_type'];
        $definition = [
            'key' => (string)$field['field_key'],
            'label' => (string)$field['label'],
            'data_type' => $dataType,
            'required_input' => asset_is_input_required($field),
            'required_final' => asset_is_final_submission_required($field),
            'non_editable' => false,
            'instruction_label' => asset_label_for_submission_message($field),
            'number_format_rule' => (string)($field['number_format_rule'] ?? ''),
            'text_max_length' => (int)($field['text_max_length'] ?? 0),
            'validation_kind' => $dataType,
            'field_id' => (int)$field['id'],
        ];
        if ($dataType === 'yes_no') {
            $definition['options'] = ['Yes', 'No'];
        } elseif ($dataType === 'dropdown' && $parentId <= 0) {
            $definition['options'] = array_map(static fn(array $option): string => (string)$option['option_value'], get_asset_field_options((int)$field['id']));
        } elseif ($dataType === 'conditional') {
            $definition['options'] = array_map(static fn(array $option): string => (string)$option['option_value'], get_asset_field_options((int)$field['id']));
            $definition['conditional_map'] = asset_decode_conditional_map($field);
            $definition['validation_kind'] = 'conditional_primary';
        } elseif ($dataType === 'dropdown' && $parentId > 0 && isset($fieldById[$parentId])) {
            $definition['parent_key'] = (string)$fieldById[$parentId]['field_key'];
            $definition['validation_kind'] = 'conditional_secondary';
        }
        $columns[] = $definition;
    }
    return $columns;
}

function asset_template_build_header_rich_text(array $column): \PhpOffice\PhpSpreadsheet\RichText\RichText
{
    $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
    $main = $richText->createTextRun((string)($column['label'] ?? ''));
    $main->getFont()->setBold(true)->getColor()->setRGB('17324D');

    $appendLine = static function (\PhpOffice\PhpSpreadsheet\RichText\RichText $richTextValue, string $text, string $color = '17324D'): void {
        $richTextValue->createText("\n");
        $run = $richTextValue->createTextRun($text);
        $run->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color($color));
        $run->getFont()->setSize(10);
    };

    if (!empty($column['non_editable'])) {
        $appendLine($richText, 'view only, cannot edit', 'C00000');
        return $richText;
    }
    if (in_array((string)($column['data_type'] ?? ''), ['dropdown', 'yes_no', 'conditional'], true) || in_array((string)($column['validation_kind'] ?? ''), ['category', 'subcategory', 'conditional_secondary'], true)) {
        $appendLine($richText, 'select from dropdown', '365F91');
    }
    if ((string)($column['data_type'] ?? '') === 'date') {
        $appendLine($richText, 'yyyy-mm-dd', '365F91');
    }
    if ((string)($column['data_type'] ?? '') === 'number') {
        $numberHint = asset_template_number_header_hint((string)($column['number_format_rule'] ?? ''));
        if ($numberHint !== '') {
            $appendLine($richText, $numberHint, '365F91');
        }
    }
    if ((string)($column['data_type'] ?? '') === 'text' && (int)($column['text_max_length'] ?? 0) > 0) {
        $appendLine($richText, 'max ' . (int)$column['text_max_length'] . ' chars', '365F91');
    }
    if (!empty($column['required_input'])) {
        $appendLine($richText, 'required (input now)', '228B22');
    }
    if (!empty($column['required_final'])) {
        $appendLine($richText, 'required (can input later)', '808080');
    }
    return $richText;
}

function asset_template_number_header_hint(string $rule): string
{
    $rule = trim($rule);
    if ($rule === '') {
        return '';
    }
    $parsed = asset_parse_number_format_rule($rule);
    if (!$parsed) {
        return '';
    }

    $beforeDigits = max(1, (int)$parsed['before_digits']);
    $afterDigits = max(0, (int)$parsed['after_digits']);
    $maxBefore = str_repeat('9', $beforeDigits);
    $hint = 'max value ' . $maxBefore;
    if ($afterDigits > 0) {
        $hint .= '.' . str_repeat('9', $afterDigits);
    }
    if (!empty($parsed['allow_negative'])) {
        $hint .= ' (-ve allowd)';
    }
    return $hint;
}

function asset_template_column_width(array $column): float
{
    if (!empty($column['non_editable']) && (string)$column['key'] === 'serial') {
        return 10;
    }
    if (!empty($column['non_editable']) && (string)$column['key'] === 'instruction') {
        return 36;
    }
    return match ((string)($column['data_type'] ?? 'text')) {
        'date' => 18,
        'number' => 18,
        'dropdown', 'yes_no', 'conditional' => 24,
        default => 26,
    };
}

function asset_template_date_validation_formula(string $cellReference): string
{
    return '=AND(LEN(' . $cellReference . ')=10,MID(' . $cellReference . ',5,1)="-",MID(' . $cellReference . ',8,1)="-",ISNUMBER(SUBSTITUTE(' . $cellReference . ',"-","")+0),IFERROR(INT(MID(' . $cellReference . ',6,2)),0)<=12,IFERROR(INT(MID(' . $cellReference . ',6,2)),0)>=1,IFERROR(INT(MID(' . $cellReference . ',9,2)),0)<=31,IFERROR(INT(MID(' . $cellReference . ',9,2)),0)>=1)';
}

function asset_template_number_validation_formula(string $cellReference, string $rule = ''): string
{
    $rule = trim($rule);
    if ($rule === '') {
        return '=OR(' . $cellReference . '="",ISNUMBER(' . $cellReference . '))';
    }
    $parsed = asset_parse_number_format_rule($rule);
    if (!$parsed) {
        return '=TRUE';
    }
    $unsigned = 'IF(LEFT(' . $cellReference . ',1)="-",MID(' . $cellReference . ',2,255),' . $cellReference . ')';
    $before = 'IFERROR(LEFT(' . $unsigned . ',FIND(".",' . $unsigned . ')-1),' . $unsigned . ')';
    $after = 'IFERROR(MID(' . $unsigned . ',FIND(".",' . $unsigned . ')+1,255),"")';
    $signCheck = $parsed['allow_negative'] ? 'TRUE' : 'LEFT(' . $cellReference . ',1)<>"-"';
    $beforeCheck = 'LEN(' . $before . ')' . ($parsed['before_exact'] ? '=' : '<=') . (int)$parsed['before_digits'];
    $afterCheck = 'LEN(' . $after . ')' . ($parsed['after_exact'] ? '=' : '<=') . (int)$parsed['after_digits'];
    return '=OR(' . $cellReference . '="",AND(ISNUMBER(--' . $cellReference . '),LEN(' . $cellReference . ')-LEN(SUBSTITUTE(' . $cellReference . ',".",""))<=1,' . $signCheck . ',' . $beforeCheck . ',' . $afterCheck . '))';
}

function build_asset_template_instruction_formula(string $rowInputRange, array $requiredColumnRefs, string $instructionColumnRef): string
{
    if (!$requiredColumnRefs) {
        return '=IF(COUNTA(' . $rowInputRange . ')=0,"","OK")';
    }
    $parts = [];
    foreach ($requiredColumnRefs as $entry) {
        $parts[] = 'IF(' . $entry['ref'] . '="","' . asset_template_formula_escape((string)$entry['label']) . '","")';
    }
    $missingJoin = 'TEXTJOIN(", ",TRUE,' . implode(',', $parts) . ')';
    return '=IF(COUNTA(' . $rowInputRange . ')=0,"",IF(' . $missingJoin . '="","OK","Missing: "&' . $missingJoin . '))';
}

function output_asset_template_autogen_download(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $filename): void
{
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->setPreCalculateFormulas(false);
    $writer->save('php://output');
    exit;
}

function build_asset_template_autogen_workbook(?int $segmentId = null): \PhpOffice\PhpSpreadsheet\Spreadsheet
{
    ensure_library('PhpOffice\\PhpSpreadsheet\\Spreadsheet', 'PhpSpreadsheet is not installed.');
    $segmentId = asset_normalize_segment_id($segmentId);
    $rowLimit = asset_template_row_limit();
    $startRow = 2;
    $endRow = $startRow + $rowLimit - 1;

    $inputColumns = asset_template_input_column_definitions($segmentId);
    $displayColumns = array_merge(
        [[
            'key' => 'serial',
            'label' => 'Serial No',
            'data_type' => 'number',
            'non_editable' => true,
        ]],
        $inputColumns,
        [[
            'key' => 'instruction',
            'label' => 'Instruction',
            'data_type' => 'text',
            'non_editable' => true,
        ]]
    );

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $dataSheet = $spreadsheet->getActiveSheet();
    $dataSheet->setTitle(asset_template_data_sheet_name($segmentId));
    $dropdownSheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet(
        $spreadsheet,
        asset_template_dropdown_sheet_name($segmentId, [$dataSheet->getTitle()])
    );
    $spreadsheet->addSheet($dropdownSheet);
    $spreadsheet->setActiveSheetIndex(0);
    $dropdownSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

    $dataSheet->setShowGridlines(false);
    $dataSheet->freezePane('A2');

    $helperColumnIndex = 1;
    $definedRanges = [];
    $emptyRangeName = 'TPL_EMPTY';
    $dropdownSheet->setCellValue('A1', $emptyRangeName);
    $dropdownSheet->setCellValue('A2', '');
    $spreadsheet->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange($emptyRangeName, $dropdownSheet, '$A$2:$A$2'));
    $helperColumnIndex = 2;

    $writeListRange = static function (\PhpOffice\PhpSpreadsheet\Spreadsheet $book, \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $rangeName, array $values, int &$columnIndex) use (&$definedRanges): string {
        $values = array_values(array_unique(array_values(array_filter(array_map(static fn($value): string => trim((string)$value), $values), static fn(string $value): bool => $value !== ''))));
        if (!$values) {
            $values = [''];
        }
        $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
        $sheet->setCellValue($col . '1', $rangeName);
        foreach ($values as $offset => $value) {
            $sheet->setCellValueExplicit($col . ($offset + 2), $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
        $rangeRef = '$' . $col . '$2:$' . $col . '$' . (count($values) + 1);
        if (!isset($definedRanges[$rangeName])) {
            $book->addNamedRange(new \PhpOffice\PhpSpreadsheet\NamedRange($rangeName, $sheet, $rangeRef));
            $definedRanges[$rangeName] = true;
        }
        $columnIndex++;
        return $rangeName;
    };

    $categoryRangeName = null;
    $fieldMapByKey = [];
    foreach ($inputColumns as $column) {
        $fieldMapByKey[$column['key']] = $column;
    }
    foreach ($inputColumns as &$column) {
        if (($column['validation_kind'] ?? '') === 'category') {
            $categoryRangeName = $writeListRange($spreadsheet, $dropdownSheet, 'TPL_CATEGORY_LIST', $column['options'] ?? [], $helperColumnIndex);
            foreach (get_asset_categories(false, $segmentId) as $category) {
                $children = array_map(static fn(array $row): string => (string)$row['name'], get_asset_subcategories((int)$category['id'], false, $segmentId));
                $writeListRange(
                    $spreadsheet,
                    $dropdownSheet,
                    'TPL_CAT_' . asset_template_named_token((string)$category['name']),
                    $children,
                    $helperColumnIndex
                );
            }
            $column['range_name'] = $categoryRangeName;
        } elseif (($column['validation_kind'] ?? '') === 'dropdown') {
            $column['range_name'] = $writeListRange(
                $spreadsheet,
                $dropdownSheet,
                'TPL_LIST_' . asset_template_named_token((string)$column['key']),
                $column['options'] ?? [],
                $helperColumnIndex
            );
        } elseif (($column['validation_kind'] ?? '') === 'yes_no') {
            $column['range_name'] = $writeListRange(
                $spreadsheet,
                $dropdownSheet,
                'TPL_YESNO',
                $column['options'] ?? ['Yes', 'No'],
                $helperColumnIndex
            );
        } elseif (($column['validation_kind'] ?? '') === 'conditional_primary') {
            $fieldToken = asset_template_named_token((string)$column['key']);
            $column['range_name'] = $writeListRange(
                $spreadsheet,
                $dropdownSheet,
                'TPL_COND_' . $fieldToken . '_LIST',
                $column['options'] ?? [],
                $helperColumnIndex
            );
            foreach (($column['conditional_map'] ?? []) as $primaryValue => $children) {
                $writeListRange(
                    $spreadsheet,
                    $dropdownSheet,
                    'TPL_COND_' . $fieldToken . '_' . asset_template_named_token((string)$primaryValue),
                    is_array($children) ? $children : [],
                    $helperColumnIndex
                );
            }
        }
    }
    unset($column);

    $columnLetters = [];
    foreach ($displayColumns as $index => $column) {
        $letter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
        $columnLetters[$column['key']] = $letter;
        $dataSheet->setCellValue($letter . '1', asset_template_build_header_rich_text($column));
        $dataSheet->getColumnDimension($letter)->setWidth(asset_template_column_width($column));
    }

    $lastColumnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($displayColumns));
    $dataSheet->getRowDimension(1)->setRowHeight(72);
    for ($row = $startRow; $row <= $endRow; $row++) {
        $dataSheet->setCellValueExplicit($columnLetters['serial'] . $row, (string)($row - 1), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }

    $requiredRefs = [];
    foreach ($inputColumns as $column) {
        if (!$column['required_input'] && !$column['required_final']) {
            continue;
        }
        $requiredRefs[] = [
            'ref' => $columnLetters[$column['key']] . $startRow,
            'label' => $column['instruction_label'],
        ];
    }
    $inputFirstLetter = $columnLetters[$inputColumns[0]['key'] ?? 'serial'] ?? 'B';
    $inputLastLetter = $columnLetters[$inputColumns[count($inputColumns) - 1]['key'] ?? 'serial'] ?? $columnLetters['serial'];
    for ($row = $startRow; $row <= $endRow; $row++) {
        $rowRequiredRefs = array_map(static function (array $item) use ($row): array {
            $columnPart = preg_replace('/\d+$/', '', $item['ref']);
            return ['ref' => $columnPart . $row, 'label' => $item['label']];
        }, $requiredRefs);
        $instructionFormula = build_asset_template_instruction_formula($inputFirstLetter . $row . ':' . $inputLastLetter . $row, $rowRequiredRefs, $columnLetters['instruction'] . $row);
        $dataSheet->setCellValue($columnLetters['instruction'] . $row, $instructionFormula);
    }

    $headerStyleRange = 'A1:' . $lastColumnLetter . '1';
    $dataSheet->getStyle($headerStyleRange)->applyFromArray([
        'alignment' => [
            'wrapText' => true,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'EAF1F8'],
        ],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'C4CBD3'],
            ],
        ],
    ]);

    $dataRange = 'A1:' . $lastColumnLetter . $endRow;
    $dataSheet->getStyle($dataRange)->applyFromArray([
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'D0D4D9'],
            ],
        ],
        'alignment' => [
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP,
            'wrapText' => true,
        ],
    ]);
    $dataSheet->getStyle('A2:' . $lastColumnLetter . $endRow)->getFont()->setSize(10);
    $dataSheet->getStyle($columnLetters['instruction'] . '2:' . $columnLetters['instruction'] . $endRow)->getAlignment()->setWrapText(true);

    foreach ($inputColumns as $column) {
        $colLetter = $columnLetters[$column['key']];
        $dataSheet->getStyle($colLetter . $startRow . ':' . $colLetter . $endRow)
            ->getProtection()
            ->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);

        if ((string)$column['data_type'] === 'date') {
            $dataSheet->getStyle($colLetter . $startRow . ':' . $colLetter . $endRow)
                ->getNumberFormat()
                ->setFormatCode('@');
        } elseif ((string)$column['data_type'] === 'number' && trim((string)($column['number_format_rule'] ?? '')) !== '') {
            $dataSheet->getStyle($colLetter . $startRow . ':' . $colLetter . $endRow)
                ->getNumberFormat()
                ->setFormatCode('@');
        } elseif ((string)$column['data_type'] === 'number') {
            $dataSheet->getStyle($colLetter . $startRow . ':' . $colLetter . $endRow)
                ->getNumberFormat()
                ->setFormatCode('0.############');
        }

        for ($row = $startRow; $row <= $endRow; $row++) {
            $cellRef = $colLetter . $row;
            $validation = $dataSheet->getCell($cellRef)->getDataValidation();
            $validation->setAllowBlank(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
            $validation->setErrorTitle('Invalid Entry');
            $validation->setShowInputMessage(false);

            switch ((string)($column['validation_kind'] ?? '')) {
                case 'category':
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('=TPL_CATEGORY_LIST');
                    $validation->setError('Choose from dropdown.');
                    break;
                case 'subcategory':
                    $parentRef = $columnLetters['category'] . $row;
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setShowDropDown(true);
                    $validation->setAllowBlank(false);
                    $validation->setFormula1('=IF(' . $parentRef . '="",TPL_EMPTY,INDIRECT("TPL_CAT_"&' . asset_template_formula_named_token_expr($parentRef) . '))');
                    $validation->setError('Choose valid sub-category.');
                    break;
                case 'dropdown':
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('=' . (string)$column['range_name']);
                    $validation->setError('Choose from dropdown.');
                    break;
                case 'yes_no':
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('=TPL_YESNO');
                    $validation->setError('Choose Yes or No.');
                    break;
                case 'conditional_primary':
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('=' . (string)$column['range_name']);
                    $validation->setError('Choose from dropdown.');
                    break;
                case 'conditional_secondary':
                    $parentRef = $columnLetters[(string)$column['parent_key']] . $row;
                    $fieldToken = asset_template_named_token((string)$column['parent_key']);
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
                    $validation->setShowDropDown(true);
                    $validation->setAllowBlank(false);
                    $validation->setFormula1('=IF(' . $parentRef . '="",TPL_EMPTY,INDIRECT("TPL_COND_' . $fieldToken . '_"&' . asset_template_formula_named_token_expr($parentRef) . '))');
                    $validation->setError('Choose valid item from dropdown.');
                    break;
                case 'date':
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_CUSTOM);
                    $validation->setFormula1(asset_template_date_validation_formula($cellRef));
                    $validation->setError('Use YYYY-MM-DD.');
                    break;
                case 'number':
                    $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_CUSTOM);
                    $validation->setFormula1(asset_template_number_validation_formula($cellRef, (string)($column['number_format_rule'] ?? '')));
                    $validation->setError('Invalid number format.');
                    break;
                case 'text':
                    if ((int)($column['text_max_length'] ?? 0) > 0) {
                        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_CUSTOM);
                        $validation->setFormula1('=OR(' . $cellRef . '="",LEN(' . $cellRef . ')<=' . (int)$column['text_max_length'] . ')');
                        $validation->setError('Max ' . (int)$column['text_max_length'] . ' characters.');
                    }
                    break;
            }
        }
    }

    $dataSheet->getProtection()->setPassword('1234');
    $dataSheet->getProtection()->setSheet(true);
    $dataSheet->getProtection()->setSort(false);
    $dataSheet->getProtection()->setInsertRows(false);
    $dataSheet->getProtection()->setFormatCells(false);
    $dropdownSheet->getProtection()->setPassword('1234');
    $dropdownSheet->getProtection()->setSheet(true);

    return $spreadsheet;
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
    if (!asset_category_selection_enabled($segmentId)) {
        unset($headers['category']);
    }
    return $headers;
}

function build_asset_template_rows(?int $segmentId = null): array
{
    $row = [];
    if (asset_category_selection_enabled($segmentId)) {
        $row['category'] = '';
    }
    if (asset_subcategory_enabled($segmentId)) {
        $row['subcategory'] = '';
    }
    return [$row];
}

function asset_import_detect_target_sheet_name($reader, string $tmpName): ?string
{
    if (!method_exists($reader, 'listWorksheetNames')) {
        return null;
    }
    try {
        $sheetNames = $reader->listWorksheetNames($tmpName);
    } catch (Throwable $e) {
        return null;
    }
    foreach ($sheetNames as $sheetName) {
        if (strcasecmp(trim((string)$sheetName), 'data') === 0) {
            return (string)$sheetName;
        }
    }
    return isset($sheetNames[0]) ? (string)$sheetNames[0] : null;
}

function asset_import_extract_sheet_rows_with_reader(string $tmpName, int $lastInputColumnIndex): array
{
    ensure_library('PhpOffice\\PhpSpreadsheet\\IOFactory', 'PhpSpreadsheet is not installed.');
    $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpName);
    $reader->setReadDataOnly(true);
    if (method_exists($reader, 'setReadEmptyCells')) {
        $reader->setReadEmptyCells(false);
    }
    $targetSheetName = asset_import_detect_target_sheet_name($reader, $tmpName);
    if ($targetSheetName !== null && method_exists($reader, 'setLoadSheetsOnly')) {
        $reader->setLoadSheetsOnly([$targetSheetName]);
    }
    $spreadsheet = $reader->load($tmpName);
    $sheet = null;
    if ($targetSheetName !== null) {
        $sheet = $spreadsheet->getSheetByName($targetSheetName);
    }
    if (!$sheet) {
        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            if (strcasecmp(trim((string)$worksheet->getTitle()), 'data') === 0) {
                $sheet = $worksheet;
                break;
            }
        }
    }
    if (!$sheet) {
        $sheet = $spreadsheet->getActiveSheet();
    }
    $lastInputColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastInputColumnIndex);
    $lastRow = max(1, $sheet->getHighestDataRow());
    $rawRows = $sheet->rangeToArray('A1:' . $lastInputColumn . $lastRow, null, false, false, false);
    $rows = [];
    foreach ($rawRows as $offset => $row) {
        $rows[$offset + 1] = is_array($row) ? array_values($row) : [];
    }
    return $rows;
}

function asset_import_xlsx_column_index(string $cellRef): int
{
    $letters = preg_replace('/[^A-Z]/i', '', strtoupper($cellRef));
    $index = 0;
    $length = strlen($letters);
    for ($i = 0; $i < $length; $i++) {
        $index = ($index * 26) + (ord($letters[$i]) - 64);
    }
    return $index;
}

function asset_import_xlsx_shared_strings(\ZipArchive $zip): array
{
    $xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($xml === false) {
        return [];
    }
    $doc = @simplexml_load_string($xml);
    if (!$doc) {
        return [];
    }
    $strings = [];
    foreach ($doc->xpath('//*[local-name()="si"]') ?: [] as $item) {
        $text = '';
        foreach ($item->xpath('.//*[local-name()="t"]') ?: [] as $node) {
            $text .= (string)$node;
        }
        $strings[] = $text;
    }
    return $strings;
}

function asset_import_xlsx_sheet_path(\ZipArchive $zip): ?string
{
    $workbookXml = $zip->getFromName('xl/workbook.xml');
    $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
    if ($workbookXml === false || $relsXml === false) {
        return null;
    }
    $workbook = @simplexml_load_string($workbookXml);
    $rels = @simplexml_load_string($relsXml);
    if (!$workbook || !$rels) {
        return null;
    }
    $targetRelId = null;
    $firstRelId = null;
    foreach ($workbook->xpath('//*[local-name()="sheets"]/*[local-name()="sheet"]') ?: [] as $sheet) {
        $relId = (string)$sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
        if ($firstRelId === null && $relId !== '') {
            $firstRelId = $relId;
        }
        if (strcasecmp(trim((string)$sheet['name']), 'data') === 0) {
            $targetRelId = $relId;
            break;
        }
    }
    $targetRelId = $targetRelId ?: $firstRelId;
    if ($targetRelId === null || $targetRelId === '') {
        return null;
    }
    foreach ($rels->xpath('//*[local-name()="Relationship"]') ?: [] as $rel) {
        if ((string)$rel['Id'] !== $targetRelId) {
            continue;
        }
        $target = (string)$rel['Target'];
        if ($target === '') {
            return null;
        }
        return strpos($target, 'xl/') === 0 ? $target : 'xl/' . ltrim($target, '/');
    }
    return null;
}

function asset_import_extract_sheet_rows_from_xlsx(string $tmpName, int $lastInputColumnIndex): array
{
    $zip = new \ZipArchive();
    if ($zip->open($tmpName) !== true) {
        throw new RuntimeException('Unable to open the Excel file.');
    }
    try {
        $sheetPath = asset_import_xlsx_sheet_path($zip);
        if ($sheetPath === null) {
            throw new RuntimeException('Unable to locate the data sheet in the Excel file.');
        }
        $sheetXml = $zip->getFromName($sheetPath);
        if ($sheetXml === false) {
            throw new RuntimeException('Unable to read the data sheet from the Excel file.');
        }
        $sharedStrings = asset_import_xlsx_shared_strings($zip);
        $sheet = @simplexml_load_string($sheetXml);
        if (!$sheet) {
            throw new RuntimeException('Unable to parse the data sheet in the Excel file.');
        }
        $rows = [];
        foreach ($sheet->xpath('//*[local-name()="sheetData"]/*[local-name()="row"]') ?: [] as $rowNode) {
            $rowNumber = (int)($rowNode['r'] ?? 0);
            if ($rowNumber <= 0) {
                continue;
            }
            $rowValues = array_fill(0, $lastInputColumnIndex, null);
            foreach ($rowNode->xpath('./*[local-name()="c"]') ?: [] as $cell) {
                $ref = (string)($cell['r'] ?? '');
                $columnIndex = asset_import_xlsx_column_index($ref);
                if ($columnIndex <= 0 || $columnIndex > $lastInputColumnIndex) {
                    continue;
                }
                $type = (string)($cell['t'] ?? '');
                $value = '';
                if ($type === 'inlineStr') {
                    foreach ($cell->xpath('./*[local-name()="is"]//*[local-name()="t"]') ?: [] as $textNode) {
                        $value .= (string)$textNode;
                    }
                } else {
                    $raw = (string)($cell->v ?? '');
                    if ($type === 's') {
                        $sharedIndex = (int)$raw;
                        $value = (string)($sharedStrings[$sharedIndex] ?? '');
                    } else {
                        $value = $raw;
                    }
                }
                $rowValues[$columnIndex - 1] = $value;
            }
            $rows[$rowNumber] = $rowValues;
        }
        if (!$rows) {
            $rows[1] = array_fill(0, $lastInputColumnIndex, null);
        }
        ksort($rows);
        return $rows;
    } finally {
        $zip->close();
    }
}

function parse_asset_import_file(string $tmpName, string $originalName, array $user, ?int $segmentId = null): array
{
    $segmentId = asset_normalize_segment_id($segmentId);
    $expectedKeys = asset_import_expected_keys($segmentId);
    if (!$expectedKeys) {
        return ['errors' => ['No active import columns are configured.'], 'rows' => []];
    }
    $lastInputColumnIndex = count($expectedKeys) + 1;
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === 'xlsx') {
        try {
            $rows = asset_import_extract_sheet_rows_from_xlsx($tmpName, $lastInputColumnIndex);
        } catch (Throwable $xlsxError) {
            try {
                $rows = asset_import_extract_sheet_rows_with_reader($tmpName, $lastInputColumnIndex);
            } catch (Throwable $readerError) {
                throw $readerError;
            }
        }
    } else {
        $rows = asset_import_extract_sheet_rows_with_reader($tmpName, $lastInputColumnIndex);
    }
    if (!$rows) {
        return ['errors' => ['Uploaded file is empty.'], 'rows' => []];
    }

    $importRows = [];
    $topErrors = [];
    foreach ($rows as $rowNumber => $cells) {
        if ((int)$rowNumber === 1) {
            continue;
        }
        $values = array_values((array)$cells);
        if ($values) {
            array_shift($values);
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
        $importRows[] = stage_asset_import_row($payload, (int)$rowNumber, $segmentId);
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
    $payload = [];
    if (asset_category_selection_enabled($segmentId)) {
        $payload['category'] = trim((string)($row['category'] ?? ''));
    }
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
    $categoryName = asset_category_selection_enabled($segmentId) ? trim((string)($input['category'] ?? '')) : '';
    $subcategoryEnabled = asset_subcategory_enabled($segmentId);
    $subcategoryName = $subcategoryEnabled ? trim((string)($input['subcategory'] ?? '')) : '';

    $categoryId = asset_category_selection_enabled($segmentId) ? 0 : asset_single_category_id($segmentId);
    if ($categoryName !== '') {
        foreach (get_asset_categories(false, $segmentId) as $category) {
            if (strcasecmp($category['name'], $categoryName) === 0) {
                $categoryId = (int)$category['id'];
                break;
            }
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
