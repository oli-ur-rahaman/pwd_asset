<?php

function ensure_asset_schema(): void
{
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;

    db()->exec(
        "CREATE TABLE IF NOT EXISTS asset_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
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
            field_key VARCHAR(100) NOT NULL,
            label VARCHAR(255) NOT NULL,
            data_type VARCHAR(20) NOT NULL,
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
    asset_ensure_column('asset_fields', 'is_unique', 'TINYINT NOT NULL DEFAULT 0');
    asset_relax_subcategory_requirement();

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
    db()->exec('UPDATE users SET office_access_level = 0 WHERE office_role IN (2,3)');
    foreach ([2 => 'zone_id', 3 => 'circle_id', 4 => 'division_id', 5 => 'subdivision_id'] as $officeType => $column) {
        $stmt = db()->prepare("SELECT id, {$column} AS office_id FROM users WHERE office_role = 1 AND office_type = ? AND {$column} IS NOT NULL AND {$column} > 0 ORDER BY id ASC");
        $stmt->execute([$officeType]);
        $seen = [];
        foreach ($stmt->fetchAll() as $row) {
            $officeId = (int)$row['office_id'];
            if ($officeId <= 0) {
                continue;
            }
            $isPrimary = !isset($seen[$officeId]);
            $seen[$officeId] = true;
            db()->prepare('UPDATE users SET is_primary_office_user = ?, office_access_level = ?, updated_at = NOW() WHERE id = ?')->execute([
                $isPrimary ? 1 : 0,
                $isPrimary ? 1 : 2,
                (int)$row['id'],
            ]);
        }
    }
}

function asset_seed_default_fields(): void
{
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
        $stmt = db()->prepare('SELECT id FROM asset_fields WHERE field_key = ? LIMIT 1');
        $stmt->execute([$field['field_key']]);
        $fieldId = (int)($stmt->fetchColumn() ?: 0);
        if ($fieldId === 0) {
            $insert = db()->prepare('INSERT INTO asset_fields (field_key, label, data_type, is_required, is_displayed, is_import_enabled, active_status, sort_order, created_at) VALUES (?, ?, ?, ?, 1, 1, 1, ?, NOW())');
            $insert->execute([
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
    return ['text', 'number', 'date', 'dropdown', 'yes_no', 'file'];
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

function asset_subcategory_enabled(): bool
{
    $info = get_info_row();
    $value = $info['asset_subcategory_enabled'] ?? null;
    if ($value === null || $value === '') {
        return true;
    }
    return (int)$value === 1;
}

function set_asset_subcategory_enabled(int $status): void
{
    $existing = get_info_row();
    save_info_row(
        $existing['video_tutorial_url'] ?? null,
        $existing['login_message'] ?? null,
        [
            'site_name' => $existing['site_name'] ?? null,
            'welcome_message' => $existing['welcome_message'] ?? null,
            'asset_subcategory_enabled' => $status === 1 ? 1 : 0,
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

function get_asset_categories(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM asset_categories WHERE deleted_at IS NULL';
    if (!$includeInactive) {
        $sql .= ' AND active_status = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';
    return db()->query($sql)->fetchAll();
}

function get_asset_category(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM asset_categories WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_asset_category(string $name): void
{
    $stmt = db()->prepare('INSERT INTO asset_categories (name, active_status, sort_order, created_at) VALUES (?, 1, ?, NOW())');
    $stmt->execute([$name, next_sort_order('asset_categories')]);
}

function update_asset_category(int $id, string $name): void
{
    $stmt = db()->prepare('UPDATE asset_categories SET name = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$name, $id]);
}

function set_asset_category_status(int $id, int $status): void
{
    $stmt = db()->prepare('UPDATE asset_categories SET active_status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$status === 1 ? 1 : 0, $id]);
}

function delete_asset_category(int $id): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM assets WHERE category_id = ?');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        return false;
    }
    $stmt = db()->prepare('DELETE FROM asset_categories WHERE id = ?');
    $stmt->execute([$id]);
    return true;
}

function get_asset_subcategories(?int $categoryId = null, bool $includeInactive = false): array
{
    $sql = 'SELECT s.*, c.name AS category_name FROM asset_subcategories s JOIN asset_categories c ON c.id = s.category_id WHERE s.deleted_at IS NULL';
    $params = [];
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

function get_asset_subcategory(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM asset_subcategories WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function create_asset_subcategory(int $categoryId, string $name): void
{
    $stmt = db()->prepare('INSERT INTO asset_subcategories (category_id, name, active_status, sort_order, created_at) VALUES (?, ?, 1, ?, NOW())');
    $stmt->execute([$categoryId, $name, next_sort_order('asset_subcategories', 'category_id', $categoryId)]);
}

function update_asset_subcategory(int $id, int $categoryId, string $name): void
{
    $stmt = db()->prepare('UPDATE asset_subcategories SET category_id = ?, name = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$categoryId, $name, $id]);
}

function set_asset_subcategory_status(int $id, int $status): void
{
    $stmt = db()->prepare('UPDATE asset_subcategories SET active_status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$status === 1 ? 1 : 0, $id]);
}

function delete_asset_subcategory(int $id): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM assets WHERE subcategory_id = ?');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        return false;
    }
    $stmt = db()->prepare('DELETE FROM asset_subcategories WHERE id = ?');
    $stmt->execute([$id]);
    return true;
}

function get_asset_fields(bool $includeInactive = false): array
{
    $sql = 'SELECT * FROM asset_fields WHERE deleted_at IS NULL';
    if (!$includeInactive) {
        $sql .= ' AND active_status = 1';
    }
    $sql .= ' ORDER BY sort_order ASC, id ASC';
    return db()->query($sql)->fetchAll();
}

function get_asset_field(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM asset_fields WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
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
    $stmt = db()->prepare('INSERT INTO asset_fields (field_key, label, data_type, is_required, is_displayed, is_import_enabled, is_unique, active_status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())');
    $stmt->execute([
        $payload['field_key'],
        $payload['label'],
        $payload['data_type'],
        $payload['is_required'],
        $payload['is_displayed'],
        $payload['is_import_enabled'],
        $payload['is_unique'],
        $payload['sort_order'],
    ]);
    $fieldId = (int)db()->lastInsertId();
    replace_asset_field_options($fieldId, $payload['options'] ?? []);
    if ($payload['data_type'] === 'file') {
        save_asset_field_file_rule($fieldId, $payload['file_rule'] ?? asset_default_file_rule());
    }
}

function update_asset_field(int $id, array $payload): void
{
    $stmt = db()->prepare('UPDATE asset_fields SET label = ?, data_type = ?, is_required = ?, is_displayed = ?, is_import_enabled = ?, is_unique = ?, sort_order = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([
        $payload['label'],
        $payload['data_type'],
        $payload['is_required'],
        $payload['is_displayed'],
        $payload['is_import_enabled'],
        $payload['is_unique'],
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

function set_asset_field_status(int $id, int $status): void
{
    $stmt = db()->prepare('UPDATE asset_fields SET active_status = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$status === 1 ? 1 : 0, $id]);
}

function delete_asset_field(int $id): bool
{
    $field = get_asset_field($id);
    if (!$field) {
        return false;
    }
    if (in_array($field['field_key'], asset_locked_field_keys(), true)) {
        return false;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM asset_values WHERE field_id = ?');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        return false;
    }
    $stmt = db()->prepare('SELECT COUNT(*) FROM asset_file_values WHERE field_id = ?');
    $stmt->execute([$id]);
    if ((int)$stmt->fetchColumn() > 0) {
        return false;
    }
    db()->prepare('DELETE FROM asset_field_options WHERE field_id = ?')->execute([$id]);
    delete_asset_field_file_rule($id);
    db()->prepare('DELETE FROM asset_fields WHERE id = ?')->execute([$id]);
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
    $map = [];
    foreach (get_asset_fields($includeInactive) as $field) {
        $map[$field['field_key']] = $field;
    }
    return $map;
}

function asset_category_map(bool $includeInactive = false): array
{
    $map = [];
    foreach (get_asset_categories($includeInactive) as $category) {
        $map[$category['id']] = $category;
    }
    return $map;
}

function asset_subcategory_map(bool $includeInactive = false): array
{
    $map = [];
    foreach (get_asset_subcategories(null, $includeInactive) as $subcategory) {
        $map[$subcategory['id']] = $subcategory;
    }
    return $map;
}

function validate_asset_field_definition(array $input, ?int $fieldId = null): array
{
    $label = trim((string)($input['label'] ?? ''));
    $dataType = trim((string)($input['data_type'] ?? ''));
    $keyInput = trim((string)($input['field_key'] ?? ''));
    $existingField = $fieldId ? get_asset_field($fieldId) : null;
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
    $sortOrder = (int)($input['sort_order'] ?? 0);
    if ($sortOrder <= 0) {
        $sortOrder = $fieldId ? (int)($existingField['sort_order'] ?? 0) : next_sort_order('asset_fields');
    }

    $existing = db()->prepare('SELECT id FROM asset_fields WHERE field_key = ? LIMIT 1');
    $existing->execute([$fieldKey]);
    $existingId = (int)($existing->fetchColumn() ?: 0);
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
            'field_key' => $fieldKey,
            'label' => $label,
            'data_type' => $dataType,
            'is_required' => !empty($input['is_required']) ? 1 : 0,
            'is_displayed' => !empty($input['is_displayed']) ? 1 : 0,
            'is_import_enabled' => $dataType === 'file' ? 0 : (!empty($input['is_import_enabled']) ? 1 : 0),
            'is_unique' => $dataType === 'file' ? 0 : (!empty($input['is_unique']) ? 1 : 0),
            'sort_order' => $sortOrder,
            'options' => $options,
            'file_rule' => $fileRule,
        ],
    ];
}

function validate_asset_payload(array $input, ?int $assetId = null, array $fileBag = []): array
{
    $errors = [];
    $categoryId = (int)($input['category_id'] ?? 0);
    $subcategoryEnabled = asset_subcategory_enabled();
    $subcategoryId = $subcategoryEnabled ? (int)($input['subcategory_id'] ?? 0) : 0;
    $category = $categoryId > 0 ? get_asset_category($categoryId) : null;
    $subcategory = $subcategoryEnabled && $subcategoryId > 0 ? get_asset_subcategory($subcategoryId) : null;
    if (!$category || (!is_superadmin() && (int)$category['active_status'] !== 1)) {
        $errors['category_id'] = 'Valid category is required.';
    }
    if ($subcategoryEnabled && (!$subcategory || (int)($subcategory['category_id'] ?? 0) !== $categoryId || (!is_superadmin() && (int)$subcategory['active_status'] !== 1))) {
        $errors['subcategory_id'] = 'Valid sub-category is required.';
    }

    $fieldMap = asset_field_map();
    $values = [];
    $fileOperations = [];
    foreach ($fieldMap as $fieldKey => $field) {
        if ($field['data_type'] === 'file') {
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

    return [
        'errors' => $errors,
        'payload' => [
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
        if (!is_numeric($value)) {
            $errors[$key] = "{$label} must be numeric.";
            return $normalized;
        }
        $normalized['value_number'] = (float)$value;
        $normalized['display'] = (string)$value;
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

    if ($type === 'dropdown') {
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

function create_asset(array $validated, array $user): int
{
    $fileCleanup = ['new_paths' => [], 'delete_paths' => []];
    db()->beginTransaction();
    try {
        $assetId = persist_asset_record($validated, $user, $fileCleanup);
        db()->commit();
        finalize_asset_file_changes($fileCleanup, true);
        add_log((int)$user['id'], 'assets', $assetId, 'Asset created.');
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
    $stmt = db()->prepare('INSERT INTO assets (asset_number, category_id, subcategory_id, office_type, office_id, active_status, created_by, created_at) VALUES (?, ?, ?, ?, ?, 1, ?, NOW())');
    $stmt->execute([
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
    } catch (Throwable $e) {
        db()->rollBack();
        finalize_asset_file_changes($fileCleanup, false);
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
    $sql = 'SELECT a.*, c.name AS category_name, s.name AS subcategory_name FROM assets a JOIN asset_categories c ON c.id = a.category_id LEFT JOIN asset_subcategories s ON s.id = a.subcategory_id WHERE a.id = ?';
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
        'dropdown' => (string)($row['value_option'] ?? ''),
        default => (string)($row['value_text'] ?? ''),
    };
}

function user_can_manage_asset(array $user, array $asset): bool
{
    if (is_superadmin()) {
        return true;
    }
    $ctx = current_office_context($user);
    if (!$ctx) {
        return false;
    }
    return (int)$asset['office_type'] === (int)$ctx['office_type'] && (int)$asset['office_id'] === (int)$ctx['office_id'];
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

function get_assets(array $filters = [], ?array $user = null, bool $includeDeleted = false): array
{
    $user = $user ?: current_user();
    $sql = 'SELECT a.*, c.name AS category_name, s.name AS subcategory_name FROM assets a JOIN asset_categories c ON c.id = a.category_id LEFT JOIN asset_subcategories s ON s.id = a.subcategory_id WHERE 1=1';
    $params = [];
    if (!$includeDeleted) {
        $sql .= ' AND a.deleted_at IS NULL AND a.active_status = 1';
    }

    if (!is_superadmin()) {
        $ctx = current_office_context($user);
        if ($ctx) {
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
    $subcategoryId = asset_subcategory_enabled() ? (int)($filters['subcategory_id'] ?? 0) : 0;
    if ($subcategoryId > 0) {
        $sql .= ' AND a.subcategory_id = ?';
        $params[] = $subcategoryId;
    }

    $condition = trim((string)($filters['condition_value'] ?? ''));
    if ($condition !== '') {
        $fieldMap = asset_field_map(true);
        if (isset($fieldMap['condition_value'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM asset_values v WHERE v.asset_id = a.id AND v.field_id = ? AND v.value_option = ?)';
            $params[] = (int)$fieldMap['condition_value']['id'];
            $params[] = $condition;
        }
    }

    $declaredStatus = trim((string)($filters['declared_status'] ?? ''));
    if ($declaredStatus === 'declared') {
        $sql .= ' AND EXISTS (SELECT 1 FROM office_asset_declarations d WHERE d.office_type = a.office_type AND d.office_id = a.office_id AND d.declared_status = 1)';
    } elseif ($declaredStatus === 'undeclared') {
        $sql .= ' AND NOT EXISTS (SELECT 1 FROM office_asset_declarations d WHERE d.office_type = a.office_type AND d.office_id = a.office_id AND d.declared_status = 1)';
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
    return $rows;
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
    $fieldMap = asset_field_map(true);
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
    $categories = get_asset_categories();
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

function asset_export_active_fields(): array
{
    return array_values(array_filter(
        get_asset_fields(),
        static fn(array $field): bool => (int)$field['active_status'] === 1 && (int)$field['is_import_enabled'] === 1
    ));
}

function asset_export_headers(bool $includeOfficeName = false): array
{
    $headers = ['serial' => 'Serial No'];
    if ($includeOfficeName) {
        $headers['office_name'] = 'Office Name';
    }
    $headers['category'] = 'Category';
    if (asset_subcategory_enabled()) {
        $headers['subcategory'] = 'Sub-category';
    }

    foreach (asset_export_active_fields() as $field) {
        $rawLabel = trim((string)($field['label'] ?? ''));
        $parts = preg_split('/\s*\/\s*/u', $rawLabel);
        $headers[$field['field_key']] = trim((string)($parts[0] ?? $rawLabel));
    }

    return $headers;
}

function build_asset_export_rows(array $filters = [], ?array $user = null, bool $includeOfficeName = false): array
{
    $rows = [];
    $fields = asset_export_active_fields();
    $assets = get_assets($filters, $user);

    foreach ($assets as $index => $asset) {
        $row = [
            'serial' => $index + 1,
            'category' => (string)($asset['category_name'] ?? ''),
        ];
        if (asset_subcategory_enabled()) {
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
    $headers = asset_export_headers($includeOfficeName);
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
    if (!$asset || !user_can_manage_asset($user ?: current_user(), $asset)) {
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

function get_asset_declaration(int $officeType, int $officeId): ?array
{
    $stmt = db()->prepare('SELECT * FROM office_asset_declarations WHERE office_type = ? AND office_id = ? LIMIT 1');
    $stmt->execute([$officeType, $officeId]);
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
    if ($diff < 86400) {
        $hours = max(1, (int)floor($diff / 3600));
        return $hours . ' hr' . ($hours === 1 ? '' : 's') . ' ago';
    }
    $days = max(1, (int)floor($diff / 86400));
    return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
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

function get_office_last_asset_update_at(int $officeType, int $officeId): ?string
{
    $stmt = db()->prepare('
        SELECT MAX(COALESCE(updated_at, created_at)) AS last_update_at
        FROM assets
        WHERE office_type = ? AND office_id = ?
    ');
    $stmt->execute([$officeType, $officeId]);
    $value = $stmt->fetchColumn();
    return $value !== false && $value !== null ? (string)$value : null;
}

function get_office_activity_summary(int $officeType, int $officeId): array
{
    $declaration = get_asset_declaration($officeType, $officeId);
    $lastSentAt = (string)($declaration['declared_at'] ?? '');
    $lastUpdateAt = (string)(get_office_last_asset_update_at($officeType, $officeId) ?? '');

    return [
        'last_sent_at' => $lastSentAt,
        'last_sent_label' => asset_relative_time_label($lastSentAt),
        'last_update_at' => $lastUpdateAt,
        'last_update_label' => asset_relative_time_label($lastUpdateAt, 'No updates yet'),
    ];
}

function declare_office_assets(int $officeType, int $officeId, int $userId): void
{
    $user = current_user();
    $officerName = trim((string)($user['officer_name'] ?? ''));
    if ($officerName === '') {
        $officerName = (string)($user['email_id'] ?? '');
    }
    $existing = get_asset_declaration($officeType, $officeId);
    if ($existing) {
        db()->prepare('UPDATE office_asset_declarations SET declared_status = 1, declared_at = NOW(), declared_by = ?, declared_officer_name = ?, updated_at = NOW() WHERE id = ?')->execute([$userId, $officerName, (int)$existing['id']]);
        return;
    }
    db()->prepare('INSERT INTO office_asset_declarations (office_type, office_id, declared_status, declared_at, declared_by, declared_officer_name, created_at) VALUES (?, ?, 1, NOW(), ?, ?, NOW())')->execute([$officeType, $officeId, $userId, $officerName]);
}

function reset_office_asset_declarations(array $pairs, int $userId): int
{
    $count = 0;
    foreach ($pairs as $pair) {
        $officeType = (int)($pair['office_type'] ?? 0);
        $officeId = (int)($pair['office_id'] ?? 0);
        if ($officeType <= 0 || $officeId <= 0) {
            continue;
        }
        $existing = get_asset_declaration($officeType, $officeId);
        if (!$existing) {
            db()->prepare('INSERT INTO office_asset_declarations (office_type, office_id, declared_status, reset_at, reset_by, created_at) VALUES (?, ?, 0, NOW(), ?, NOW())')->execute([$officeType, $officeId, $userId]);
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
            ON d.office_type = ? AND d.office_id = o.id
        ORDER BY o.office_name ASC";
    $stmt = db()->prepare($sql);
    $stmt->execute([$officeType]);
    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        $officeId = (int)$row['office_id'];
        $lastSentAt = (string)($row['declared_at'] ?? '');
        $lastUpdateAt = (string)(get_office_last_asset_update_at($officeType, $officeId) ?? '');
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

function asset_template_storage_path(): string
{
    return asset_template_storage_dir() . '/asset_import_template.xlsx';
}

function asset_template_uploaded_info(): ?array
{
    $path = asset_template_storage_path();
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

function asset_template_core_columns(): array
{
    $columns = [
        ['key' => 'category', 'label' => 'Category / Category'],
    ];
    if (asset_subcategory_enabled()) {
        $columns[] = ['key' => 'subcategory', 'label' => 'Sub-category / Sub-category'];
    }
    foreach (get_asset_fields() as $field) {
        if ((int)$field['is_import_enabled'] !== 1 || (int)$field['active_status'] !== 1) {
            continue;
        }
        $columns[] = ['key' => $field['field_key'], 'label' => $field['label']];
    }
    return $columns;
}

function asset_template_columns(): array
{
    return array_merge(
        [['key' => 'serial', 'label' => 'Serial No']],
        asset_template_core_columns(),
        [['key' => 'instruction', 'label' => 'Instruction']]
    );
}

function asset_import_expected_keys(): array
{
    return array_column(asset_template_core_columns(), 'key');
}

function validate_uploaded_asset_template(string $tmpName): array
{
    ensure_library('PhpOffice\\PhpSpreadsheet\\IOFactory', 'PhpSpreadsheet is not installed.');
    $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
    if (!$rows) {
        return ['Template file is empty.'];
    }
    $headerRow = array_values($rows[1] ?? []);
    $expectedCount = count(asset_template_columns());
    if (count($headerRow) < $expectedCount) {
        return ['Template column count is less than the current required column sequence.'];
    }
    return [];
}

function find_asset_category_by_name(string $name): ?array
{
    foreach (get_asset_categories(true) as $category) {
        if (strcasecmp(trim((string)$category['name']), trim($name)) === 0) {
            return $category;
        }
    }
    return null;
}

function find_asset_subcategory_by_name(int $categoryId, string $name): ?array
{
    foreach (get_asset_subcategories($categoryId, true) as $subcategory) {
        if (strcasecmp(trim((string)$subcategory['name']), trim($name)) === 0) {
            return $subcategory;
        }
    }
    return null;
}

function sync_asset_template_info_sheet(string $tmpName): array
{
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

            $category = find_asset_category_by_name($categoryName);
            if (!$category) {
                create_asset_category($categoryName);
                $category = find_asset_category_by_name($categoryName);
                $categoriesCreated++;
            } elseif ((int)($category['active_status'] ?? 1) !== 1) {
                set_asset_category_status((int)$category['id'], 1);
                $category = get_asset_category((int)$category['id']) ?? $category;
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

                $subcategory = find_asset_subcategory_by_name((int)$category['id'], $subcategoryName);
                if (!$subcategory) {
                    create_asset_subcategory((int)$category['id'], $subcategoryName);
                    $subcategoriesCreated++;
                } elseif ((int)($subcategory['active_status'] ?? 1) !== 1) {
                    set_asset_subcategory_status((int)$subcategory['id'], 1);
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

function save_uploaded_asset_template(array $file): array
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Please choose a valid Excel template file.');
    }
    $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($extension, ['xlsx', 'xls'], true)) {
        throw new RuntimeException('Template file must be an Excel file.');
    }
    $errors = validate_uploaded_asset_template($file['tmp_name']);
    if ($errors) {
        throw new RuntimeException(implode(' ', $errors));
    }
    $syncSummary = sync_asset_template_info_sheet($file['tmp_name']);
    $dir = asset_template_storage_dir();
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create template storage directory.');
    }
    $target = asset_template_storage_path();
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Failed to save template file.');
    }
    return $syncSummary;
}

function output_asset_template_download(): void
{
    $stored = asset_template_uploaded_info();
    if ($stored && asset_subcategory_enabled()) {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="asset_import_template.xlsx"');
        header('Content-Length: ' . (string)$stored['size']);
        header('Cache-Control: max-age=0');
        readfile($stored['path']);
        exit;
    }
    $headers = [];
    foreach (asset_template_columns() as $column) {
        $headers[$column['key']] = $column['label'];
    }
    $rows = [[
        'serial' => '1',
        'category' => '',
        'instruction' => 'Fill data columns only. Keep heading row unchanged.',
    ]];
    if (asset_subcategory_enabled()) {
        $rows[0]['subcategory'] = '';
    }
    export_excel($rows, $headers, 'asset_import_template.xlsx', 'Asset Template');
}

function asset_template_headers(): array
{
    $headers = [
        'category' => 'Category / শ্রেণি',
        'subcategory' => 'Sub-category / উপ-শ্রেণি',
    ];
    foreach (get_asset_fields() as $field) {
        if ((int)$field['is_import_enabled'] !== 1) {
            continue;
        }
        $headers[$field['field_key']] = $field['label'];
    }
    if (!asset_subcategory_enabled()) {
        unset($headers['subcategory']);
    }
    return $headers;
}

function build_asset_template_rows(): array
{
    if (!asset_subcategory_enabled()) {
        return [[
            'category' => '',
        ]];
    }
    return [[
        'category' => '',
        'subcategory' => '',
    ]];
}

function parse_asset_import_file(string $tmpName, string $originalName, array $user): array
{
    ensure_library('PhpOffice\\PhpSpreadsheet\\IOFactory', 'PhpSpreadsheet is not installed.');
    $spreadsheet = PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
    if (!$rows) {
        return ['errors' => ['Uploaded file is empty.'], 'rows' => []];
    }
    array_shift($rows);
    $expectedKeys = asset_import_expected_keys();
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
        $importRows[] = stage_asset_import_row($payload, $rowIndex + 2);
    }

    if (!$importRows) {
        $topErrors[] = 'No importable rows were found in the file.';
    }

    $_SESSION['asset_import_review'] = [
        'filename' => $originalName,
        'rows' => $importRows,
        'created_at' => date('Y-m-d H:i:s'),
    ];

    return ['errors' => $topErrors, 'rows' => $importRows];
}

function stage_asset_import_row(array $input, int $rowNumber): array
{
    $categoryName = trim((string)($input['category'] ?? ''));
    $subcategoryEnabled = asset_subcategory_enabled();
    $subcategoryName = $subcategoryEnabled ? trim((string)($input['subcategory'] ?? '')) : '';

    $categoryId = 0;
    foreach (get_asset_categories() as $category) {
        if (strcasecmp($category['name'], $categoryName) === 0) {
            $categoryId = (int)$category['id'];
            break;
        }
    }

    $subcategoryId = 0;
    if ($subcategoryEnabled && $categoryId > 0) {
        foreach (get_asset_subcategories($categoryId) as $subcategory) {
            if (strcasecmp($subcategory['name'], $subcategoryName) === 0) {
                $subcategoryId = (int)$subcategory['id'];
                break;
            }
        }
    }

    $fieldInputs = [];
    foreach (get_asset_fields() as $field) {
        if ((int)$field['is_import_enabled'] !== 1) {
            continue;
        }
        $fieldInputs[$field['field_key']] = $input[$field['field_key']] ?? '';
    }

    $validated = validate_asset_payload([
        'category_id' => $categoryId,
        'subcategory_id' => $subcategoryId,
        'fields' => $fieldInputs,
    ]);

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
    $reviewRows = [];
    $subcategoryEnabled = asset_subcategory_enabled();
    foreach ($submittedRows as $index => $row) {
        $payload = [
            'category' => trim((string)($row['category'] ?? '')),
        ];
        if ($subcategoryEnabled) {
            $payload['subcategory'] = trim((string)($row['subcategory'] ?? ''));
        }
        foreach (get_asset_fields() as $field) {
            if ((int)$field['is_import_enabled'] !== 1) {
                continue;
            }
            $payload[$field['field_key']] = trim((string)($row['fields'][$field['field_key']] ?? ''));
        }
        $reviewRows[] = stage_asset_import_row($payload, (int)($row['row_number'] ?? ($index + 1)));
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

    $saved = 0;
    $errors = [];
    $invalidRows = [];
    $batchStmt = db()->prepare('INSERT INTO asset_import_batches (office_type, office_id, uploaded_by, original_filename, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())');
    $ctx = current_office_context($user);
    if (!$ctx) {
        return ['saved' => 0, 'errors' => ['Office context not found.']];
    }
    $batchStmt->execute([$ctx['office_type'], $ctx['office_id'], (int)$user['id'], (string)($review['filename'] ?? 'import.xlsx'), 'completed']);
    $batchId = (int)db()->lastInsertId();

    $validRows = [];
    foreach ($review['rows'] as $row) {
        $validated = validate_asset_payload([
            'category_id' => (int)$row['category_id'],
            'subcategory_id' => (int)$row['subcategory_id'],
            'fields' => $row['fields'] ?? [],
        ]);
        if (!empty($validated['errors'])) {
            $row['errors'] = $validated['errors'];
            $invalidRows[] = $row;
            $errors[] = 'Row ' . $row['row_number'] . ' still has validation errors.';
            continue;
        }
        $validRows[] = ['row' => $row, 'payload' => $validated['payload']];
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
                $invalidRows[] = $item['row'];
            }
            $saved = 0;
            $errors[] = 'Database save failed. No rows were imported.';
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
