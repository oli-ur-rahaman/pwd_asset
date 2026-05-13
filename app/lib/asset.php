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
            subcategory_id INT NOT NULL,
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
    asset_ensure_column('circles', 'active_status', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('divisions', 'active_status', 'TINYINT NOT NULL DEFAULT 1');
    asset_ensure_column('office_asset_declarations', 'declared_officer_name', 'VARCHAR(255) DEFAULT NULL');
    asset_ensure_column('info', 'welcome_message', 'LONGTEXT DEFAULT NULL');

    asset_seed_default_fields();
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
    return ['text', 'number', 'date', 'dropdown', 'yes_no'];
}

function asset_locked_field_keys(): array
{
    return [];
}

function asset_core_columns(): array
{
    return [
        ['key' => '__sl', 'label' => 'SL No'],
        ['key' => 'asset_number', 'label' => 'Asset Number / সম্পদ নং'],
        ['key' => 'subcategory_name', 'label' => 'Sub-category / উপ-শ্রেণি'],
    ];
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
    return null;
}

function asset_office_type_label(int $officeType): string
{
    return match ($officeType) {
        2 => 'Zone',
        3 => 'Circle',
        4 => 'Division',
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

function create_asset_field(array $payload): void
{
    $stmt = db()->prepare('INSERT INTO asset_fields (field_key, label, data_type, is_required, is_displayed, is_import_enabled, active_status, sort_order, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())');
    $stmt->execute([
        $payload['field_key'],
        $payload['label'],
        $payload['data_type'],
        $payload['is_required'],
        $payload['is_displayed'],
        $payload['is_import_enabled'],
        $payload['sort_order'],
    ]);
    $fieldId = (int)db()->lastInsertId();
    replace_asset_field_options($fieldId, $payload['options'] ?? []);
}

function update_asset_field(int $id, array $payload): void
{
    $stmt = db()->prepare('UPDATE asset_fields SET label = ?, data_type = ?, is_required = ?, is_displayed = ?, is_import_enabled = ?, sort_order = ?, updated_at = NOW() WHERE id = ?');
    $stmt->execute([
        $payload['label'],
        $payload['data_type'],
        $payload['is_required'],
        $payload['is_displayed'],
        $payload['is_import_enabled'],
        $payload['sort_order'],
        $id,
    ]);
    replace_asset_field_options($id, $payload['options'] ?? []);
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
    db()->prepare('DELETE FROM asset_field_options WHERE field_id = ?')->execute([$id]);
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

    return [
        'errors' => $errors,
        'payload' => [
            'field_key' => $fieldKey,
            'label' => $label,
            'data_type' => $dataType,
            'is_required' => !empty($input['is_required']) ? 1 : 0,
            'is_displayed' => !empty($input['is_displayed']) ? 1 : 0,
            'is_import_enabled' => !empty($input['is_import_enabled']) ? 1 : 0,
            'sort_order' => $sortOrder,
            'options' => $options,
        ],
    ];
}

function validate_asset_payload(array $input, ?int $assetId = null): array
{
    $errors = [];
    $categoryId = (int)($input['category_id'] ?? 0);
    $subcategoryId = (int)($input['subcategory_id'] ?? 0);
    $category = $categoryId > 0 ? get_asset_category($categoryId) : null;
    $subcategory = $subcategoryId > 0 ? get_asset_subcategory($subcategoryId) : null;
    if (!$category || (!is_superadmin() && (int)$category['active_status'] !== 1)) {
        $errors['category_id'] = 'Valid category is required.';
    }
    if (!$subcategory || (int)($subcategory['category_id'] ?? 0) !== $categoryId || (!is_superadmin() && (int)$subcategory['active_status'] !== 1)) {
        $errors['subcategory_id'] = 'Valid sub-category is required.';
    }

    $fieldMap = asset_field_map();
    $values = [];
    foreach ($fieldMap as $fieldKey => $field) {
        $raw = $input['fields'][$fieldKey] ?? null;
        $values[$fieldKey] = normalize_asset_field_value($field, $raw, $fieldMap, $errors);
    }

    return [
        'errors' => $errors,
        'payload' => [
            'category_id' => $categoryId,
            'subcategory_id' => $subcategoryId,
            'field_values' => $values,
        ],
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

function create_asset(array $validated, array $user): int
{
    db()->beginTransaction();
    try {
        $assetId = persist_asset_record($validated, $user);
        db()->commit();
        add_log((int)$user['id'], 'assets', $assetId, 'Asset created.');
        return $assetId;
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function persist_asset_record(array $validated, array $user): int
{
    $ctx = current_office_context($user);
    if (!$ctx) {
        throw new RuntimeException('Office context not found.');
    }
    $stmt = db()->prepare('INSERT INTO assets (asset_number, category_id, subcategory_id, office_type, office_id, active_status, created_by, created_at) VALUES (?, ?, ?, ?, ?, 1, ?, NOW())');
    $stmt->execute([
        'PENDING',
        $validated['category_id'],
        $validated['subcategory_id'],
        $ctx['office_type'],
        $ctx['office_id'],
        (int)$user['id'],
    ]);
    $assetId = (int)db()->lastInsertId();
    $assetNumber = sprintf('AST-%s-%06d', date('Y'), $assetId);
    db()->prepare('UPDATE assets SET asset_number = ? WHERE id = ?')->execute([$assetNumber, $assetId]);
    save_asset_values($assetId, $validated['field_values']);
    return $assetId;
}

function update_asset(int $assetId, array $validated, array $user): void
{
    $asset = get_asset($assetId, true);
    if (!$asset) {
        throw new RuntimeException('Asset not found.');
    }
    db()->beginTransaction();
    try {
        $stmt = db()->prepare('UPDATE assets SET category_id = ?, subcategory_id = ?, updated_by = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([
            $validated['category_id'],
            $validated['subcategory_id'],
            (int)$user['id'],
            $assetId,
        ]);
        save_asset_values($assetId, $validated['field_values']);
        db()->commit();
        add_log((int)$user['id'], 'assets', $assetId, 'Asset updated.');
    } catch (Throwable $e) {
        db()->rollBack();
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
    $sql = 'SELECT a.*, c.name AS category_name, s.name AS subcategory_name FROM assets a JOIN asset_categories c ON c.id = a.category_id JOIN asset_subcategories s ON s.id = a.subcategory_id WHERE a.id = ?';
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
    $sql = 'SELECT a.*, c.name AS category_name, s.name AS subcategory_name FROM assets a JOIN asset_categories c ON c.id = a.category_id JOIN asset_subcategories s ON s.id = a.subcategory_id WHERE 1=1';
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
    $subcategoryId = (int)($filters['subcategory_id'] ?? 0);
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
    foreach ($rows as &$row) {
        $row['values'] = $valuesByAsset[(int)$row['id']] ?? [];
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
    $headers['subcategory'] = 'Sub-category';

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
            'subcategory' => (string)($asset['subcategory_name'] ?? ''),
        ];
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
    ];
}

function get_declarations_for_office_type(int $officeType, array $filters = []): array
{
    $tableMap = [
        2 => 'zones',
        3 => 'circles',
        4 => 'divisions',
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
        default => 0,
    };
}

function office_type_to_kind(int $officeType): string
{
    return match ($officeType) {
        2 => 'zone',
        3 => 'circle',
        4 => 'division',
        default => '',
    };
}

function office_table_for_kind(string $kind): ?string
{
    return match ($kind) {
        'zone' => 'zones',
        'circle' => 'circles',
        'division' => 'divisions',
        default => null,
    };
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

function find_primary_office_user(int $officeType, int $officeId): ?array
{
    if ($officeType <= 0 || $officeId <= 0) {
        return null;
    }
    if ($officeType === 2) {
        $stmt = db()->prepare('SELECT * FROM users WHERE office_type = 2 AND zone_id = ? ORDER BY office_role ASC, id ASC LIMIT 1');
    } elseif ($officeType === 3) {
        $stmt = db()->prepare('SELECT * FROM users WHERE office_type = 3 AND circle_id = ? ORDER BY office_role ASC, id ASC LIMIT 1');
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE office_type = 4 AND division_id = ? ORDER BY office_role ASC, id ASC LIMIT 1');
    }
    $stmt->execute([$officeId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function office_user_payload(string $kind, int $officeId, int $zoneId, ?int $circleId, ?int $divisionId): array
{
    $officeType = office_kind_to_type($kind);
    return [
        'office_type' => $officeType,
        'office_role' => 1,
        'zone_id' => $zoneId > 0 ? $zoneId : null,
        'circle_id' => $circleId && $circleId > 0 ? $circleId : null,
        'division_id' => $divisionId && $divisionId > 0 ? $divisionId : null,
        'office_id' => $officeId,
    ];
}

function insert_office_user(string $email, array $payload, int $activeStatus = 1): int
{
    $stmt = db()->prepare('INSERT INTO users (email_id, officer_name, password, office_type, office_role, zone_id, circle_id, division_id, active_status, created_at) VALUES (?, NULL, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $stmt->execute([
        $email,
        password_hash(office_default_password($email), PASSWORD_DEFAULT),
        $payload['office_type'],
        $payload['office_role'],
        $payload['zone_id'],
        $payload['circle_id'],
        $payload['division_id'],
        $activeStatus,
    ]);
    return (int)db()->lastInsertId();
}

function save_office_user(string $kind, int $officeId, string $email, int $zoneId, ?int $circleId, ?int $divisionId, int $activeStatus): void
{
    $payload = office_user_payload($kind, $officeId, $zoneId, $circleId, $divisionId);
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

    db()->prepare('UPDATE users SET email_id = ?, office_type = ?, office_role = ?, zone_id = ?, circle_id = ?, division_id = ?, active_status = ?, updated_at = NOW() WHERE id = ?')->execute([
        $email,
        $payload['office_type'],
        $payload['office_role'],
        $payload['zone_id'],
        $payload['circle_id'],
        $payload['division_id'],
        $activeStatus,
        (int)$existing['id'],
    ]);
}

function create_office_with_user(string $kind, string $name, string $address, string $email, ?int $zoneId = null, ?int $circleId = null): void
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
            save_office_user('zone', $officeId, $email, $officeId, null, null, 1);
        } elseif ($kind === 'circle') {
            if (($zoneId ?? 0) <= 0) {
                throw new RuntimeException('Circle requires a zone.');
            }
            db()->prepare('INSERT INTO circles (office_name, office_address, office_type, zone_id, active_status, created_at) VALUES (?, ?, 3, ?, 1, NOW())')->execute([$name, $addressValue, $zoneId]);
            $officeId = (int)db()->lastInsertId();
            save_office_user('circle', $officeId, $email, $zoneId, $officeId, null, 1);
        } else {
            $circle = find_circle_with_zone((int)$circleId);
            if (!$circle) {
                throw new RuntimeException('Division requires a valid circle.');
            }
            $zoneId = (int)$circle['zone_id'];
            $circleId = (int)$circle['id'];
            db()->prepare('INSERT INTO divisions (office_name, office_address, office_type, zone_id, circle_id, field_office, active_status, created_at) VALUES (?, ?, 4, ?, ?, 1, 1, NOW())')->execute([$name, $addressValue, $zoneId, $circleId]);
            $officeId = (int)db()->lastInsertId();
            save_office_user('division', $officeId, $email, $zoneId, $circleId, $officeId, 1);
        }
        db()->commit();
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        throw $e;
    }
}

function update_office_with_user(string $kind, int $officeId, string $name, string $address, string $email, ?int $zoneId = null, ?int $circleId = null): void
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
                save_office_user('zone', $officeId, $email, $officeId, null, null, $activeStatus);
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
                save_office_user('circle', $officeId, $email, $zoneId, $officeId, null, $activeStatus);
            }
        } else {
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
                save_office_user('division', $officeId, $email, $zoneId, $circleId, $officeId, $activeStatus);
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
        } else {
            db()->prepare('UPDATE users SET active_status = ?, updated_at = NOW() WHERE office_type = 4 AND division_id = ?')->execute([$activeStatus, $officeId]);
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

    $users = db()->query('SELECT * FROM users WHERE office_type IN (2, 3, 4) ORDER BY office_role ASC, id ASC')->fetchAll();
    $userMap = [];
    foreach ($users as $user) {
        $officeType = (int)$user['office_type'];
        $officeId = $officeType === 2 ? (int)($user['zone_id'] ?? 0) : ($officeType === 3 ? (int)($user['circle_id'] ?? 0) : (int)($user['division_id'] ?? 0));
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

    return [
        'zones' => $zones,
        'circles' => $circles,
        'divisions' => $divisions,
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
        ['key' => 'subcategory', 'label' => 'Sub-category / Sub-category'],
    ];
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
    if ($stored) {
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
        'subcategory' => '',
        'instruction' => 'Fill data columns only. Keep heading row unchanged.',
    ]];
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
    return $headers;
}

function build_asset_template_rows(): array
{
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
    $subcategoryName = trim((string)($input['subcategory'] ?? ''));

    $categoryId = 0;
    foreach (get_asset_categories() as $category) {
        if (strcasecmp($category['name'], $categoryName) === 0) {
            $categoryId = (int)$category['id'];
            break;
        }
    }

    $subcategoryId = 0;
    if ($categoryId > 0) {
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
    foreach ($submittedRows as $index => $row) {
        $payload = [
            'category' => trim((string)($row['category'] ?? '')),
            'subcategory' => trim((string)($row['subcategory'] ?? '')),
        ];
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
