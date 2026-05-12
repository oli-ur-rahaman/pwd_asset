CREATE TABLE IF NOT EXISTS zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(255) NOT NULL,
    office_address VARCHAR(255) DEFAULT NULL,
    office_type TINYINT NOT NULL DEFAULT 2,
    active_status TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS circles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(255) NOT NULL,
    office_address VARCHAR(255) DEFAULT NULL,
    office_type TINYINT NOT NULL DEFAULT 2,
    zone_id INT DEFAULT NULL,
    active_status TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_circles_zone (zone_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS divisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(255) NOT NULL,
    office_address VARCHAR(255) DEFAULT NULL,
    office_type TINYINT NOT NULL DEFAULT 2,
    zone_id INT DEFAULT NULL,
    circle_id INT DEFAULT NULL,
    field_office TINYINT NOT NULL DEFAULT 1,
    active_status TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_divisions_zone (zone_id),
    KEY idx_divisions_circle (circle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_id VARCHAR(255) NOT NULL UNIQUE,
    officer_name VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    office_type TINYINT NOT NULL DEFAULT 4,
    office_role TINYINT NOT NULL DEFAULT 1,
    zone_id INT DEFAULT NULL,
    circle_id INT DEFAULT NULL,
    division_id INT DEFAULT NULL,
    active_status TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_users_zone (zone_id),
    KEY idx_users_circle (circle_id),
    KEY idx_users_division (division_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fiscal_years VARCHAR(7) NOT NULL,
    now_flag TINYINT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opr_repair (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fy_id INT NOT NULL,
    division_id INT NOT NULL,
    month_val TINYINT NOT NULL DEFAULT 1,
    pkg INT NOT NULL DEFAULT 0,
    est DECIMAL(10,2) NOT NULL DEFAULT 0,
    pkg_live INT NOT NULL DEFAULT 0,
    pkg_eval INT NOT NULL DEFAULT 0,
    pkg_cont INT NOT NULL DEFAULT 0,
    cont DECIMAL(10,2) NOT NULL DEFAULT 0,
    note TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_opr_repair_fy_div (fy_id, division_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opr_other (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fy_id INT NOT NULL,
    division_id INT NOT NULL,
    month_val TINYINT NOT NULL DEFAULT 1,
    pkg INT NOT NULL DEFAULT 0,
    est DECIMAL(10,2) NOT NULL DEFAULT 0,
    pkg_live INT NOT NULL DEFAULT 0,
    pkg_eval INT NOT NULL DEFAULT 0,
    pkg_cont INT NOT NULL DEFAULT 0,
    cont DECIMAL(10,2) NOT NULL DEFAULT 0,
    note TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_opr_other_fy_div (fy_id, division_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dev_pw (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fy_id INT NOT NULL,
    division_id INT NOT NULL,
    month_val TINYINT NOT NULL DEFAULT 1,
    pkg INT NOT NULL DEFAULT 0,
    est DECIMAL(10,2) NOT NULL DEFAULT 0,
    pkg_live INT NOT NULL DEFAULT 0,
    pkg_eval INT NOT NULL DEFAULT 0,
    pkg_cont INT NOT NULL DEFAULT 0,
    cont DECIMAL(10,2) NOT NULL DEFAULT 0,
    note TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_dev_pw_fy_div (fy_id, division_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS opr_other_min (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fy_id INT NOT NULL,
    division_id INT NOT NULL,
    month_val TINYINT NOT NULL DEFAULT 1,
    pkg INT NOT NULL DEFAULT 0,
    est DECIMAL(10,2) NOT NULL DEFAULT 0,
    pkg_live INT NOT NULL DEFAULT 0,
    pkg_eval INT NOT NULL DEFAULT 0,
    pkg_cont INT NOT NULL DEFAULT 0,
    cont DECIMAL(10,2) NOT NULL DEFAULT 0,
    note TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_opr_other_min_fy_div (fy_id, division_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS dev_other_min (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fy_id INT NOT NULL,
    division_id INT NOT NULL,
    month_val TINYINT NOT NULL DEFAULT 1,
    pkg INT NOT NULL DEFAULT 0,
    est DECIMAL(10,2) NOT NULL DEFAULT 0,
    pkg_live INT NOT NULL DEFAULT 0,
    pkg_eval INT NOT NULL DEFAULT 0,
    pkg_cont INT NOT NULL DEFAULT 0,
    cont DECIMAL(10,2) NOT NULL DEFAULT 0,
    note TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_dev_other_min_fy_div (fy_id, division_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    table_name VARCHAR(50) NOT NULL,
    record_id INT NOT NULL,
    summary VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_logs_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS info (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(255) DEFAULT NULL,
    video_tutorial_url VARCHAR(255) DEFAULT NULL,
    login_message TEXT DEFAULT NULL,
    i_opr_repair TEXT DEFAULT NULL,
    i_opr_other TEXT DEFAULT NULL,
    i_dev_pw TEXT DEFAULT NULL,
    i_opr_min TEXT DEFAULT NULL,
    i_dev_min TEXT DEFAULT NULL,
    i_opr TEXT DEFAULT NULL,
    i_dev TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS operational (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ministry_id INT NOT NULL,
    fy_id INT NOT NULL,
    division_id INT NOT NULL,
    month_val TINYINT NOT NULL DEFAULT 1,
    pkg INT NOT NULL DEFAULT 0,
    est DECIMAL(10,2) NOT NULL DEFAULT 0,
    pkg_live INT NOT NULL DEFAULT 0,
    pkg_eval INT NOT NULL DEFAULT 0,
    pkg_cont INT NOT NULL DEFAULT 0,
    cont DECIMAL(10,2) NOT NULL DEFAULT 0,
    note TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_operational_fy_div (fy_id, division_id, id),
    KEY idx_operational_ministry (ministry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS development (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ministry_id INT NOT NULL,
    fy_id INT NOT NULL,
    division_id INT NOT NULL,
    month_val TINYINT NOT NULL DEFAULT 1,
    pkg INT NOT NULL DEFAULT 0,
    est DECIMAL(10,2) NOT NULL DEFAULT 0,
    pkg_live INT NOT NULL DEFAULT 0,
    pkg_eval INT NOT NULL DEFAULT 0,
    pkg_cont INT NOT NULL DEFAULT 0,
    cont DECIMAL(10,2) NOT NULL DEFAULT 0,
    note TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_development_fy_div (fy_id, division_id, id),
    KEY idx_development_ministry (ministry_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ministries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    vis_opr TINYINT NOT NULL DEFAULT 1,
    vis_dev TINYINT NOT NULL DEFAULT 1,
    inuse_status TINYINT NOT NULL DEFAULT 1,
    def_opr TINYINT NOT NULL DEFAULT 0,
    def_dev TINYINT NOT NULL DEFAULT 0,
    def_opr_sl INT NOT NULL DEFAULT 0,
    def_dev_sl INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    active_status TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_asset_categories_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_subcategories (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_fields (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_field_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    field_id INT NOT NULL,
    option_value VARCHAR(255) NOT NULL,
    option_label VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    active_status TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_asset_field_options_field (field_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assets (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_values (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS office_asset_declarations (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_import_batches (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
