CREATE TABLE IF NOT EXISTS zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(255) NOT NULL,
    office_address VARCHAR(255) DEFAULT NULL,
    office_type TINYINT NOT NULL DEFAULT 2,
    active_status TINYINT NOT NULL DEFAULT 1,
    allow_office_user_management TINYINT NOT NULL DEFAULT 1,
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
    allow_office_user_management TINYINT NOT NULL DEFAULT 1,
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
    allow_office_user_management TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_divisions_zone (zone_id),
    KEY idx_divisions_circle (circle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subdivisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(255) NOT NULL,
    office_address VARCHAR(255) DEFAULT NULL,
    office_type TINYINT NOT NULL DEFAULT 5,
    zone_id INT NOT NULL,
    circle_id INT NOT NULL,
    division_id INT NOT NULL,
    active_status TINYINT NOT NULL DEFAULT 1,
    allow_office_user_management TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_subdivisions_zone (zone_id),
    KEY idx_subdivisions_circle (circle_id),
    KEY idx_subdivisions_division (division_id)
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
    subdivision_id INT DEFAULT NULL,
    is_primary_office_user TINYINT NOT NULL DEFAULT 0,
    office_access_level TINYINT NOT NULL DEFAULT 2,
    active_status TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_users_zone (zone_id),
    KEY idx_users_circle (circle_id),
    KEY idx_users_division (division_id),
    KEY idx_users_subdivision (subdivision_id)
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
    welcome_message LONGTEXT DEFAULT NULL,
    ui_theme_key VARCHAR(50) DEFAULT NULL,
    asset_subcategory_enabled TINYINT NOT NULL DEFAULT 1,
    asset_number_visible_to_users TINYINT NOT NULL DEFAULT 1,
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

CREATE TABLE IF NOT EXISTS segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_name VARCHAR(255) NOT NULL,
    active_status TINYINT NOT NULL DEFAULT 1,
    asset_subcategory_enabled TINYINT NOT NULL DEFAULT 1,
    asset_number_visible_to_users TINYINT NOT NULL DEFAULT 1,
    show_data_provider_superadmin TINYINT NOT NULL DEFAULT 1,
    show_filter_card_superadmin TINYINT NOT NULL DEFAULT 1,
    show_filter_card_users TINYINT NOT NULL DEFAULT 1,
    show_office_scope_switch TINYINT NOT NULL DEFAULT 1,
    show_filter_card TINYINT NOT NULL DEFAULT 1,
    allow_bulk_import TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_segments_name (segment_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    active_status TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_asset_categories_segment_name (segment_id, name),
    KEY idx_asset_categories_segment (segment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT DEFAULT NULL,
    category_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    active_status TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    KEY idx_asset_subcategories_segment (segment_id),
    KEY idx_asset_subcategories_category (category_id),
    UNIQUE KEY uniq_asset_subcategories_name (category_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT DEFAULT NULL,
    field_key VARCHAR(100) NOT NULL,
    label VARCHAR(255) NOT NULL,
    data_type VARCHAR(20) NOT NULL,
    field_information LONGTEXT DEFAULT NULL,
    video_tutorial_url VARCHAR(1000) DEFAULT NULL,
    number_format_rule VARCHAR(30) DEFAULT NULL,
    text_max_length INT DEFAULT NULL,
    secondary_of_field_id INT DEFAULT NULL,
    conditional_map_json LONGTEXT DEFAULT NULL,
    mandatory_scope TINYINT NOT NULL DEFAULT 0,
    is_required TINYINT NOT NULL DEFAULT 0,
    is_displayed TINYINT NOT NULL DEFAULT 1,
    is_import_enabled TINYINT NOT NULL DEFAULT 1,
    is_unique TINYINT NOT NULL DEFAULT 0,
    is_filter_enabled TINYINT NOT NULL DEFAULT 0,
    filter_scope TINYINT NOT NULL DEFAULT 0,
    active_status TINYINT NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_asset_fields_segment_key (segment_id, field_key),
    KEY idx_asset_fields_segment (segment_id)
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
    segment_id INT DEFAULT NULL,
    asset_number VARCHAR(50) NOT NULL,
    category_id INT DEFAULT NULL,
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
    KEY idx_assets_segment (segment_id),
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

CREATE TABLE IF NOT EXISTS asset_field_file_rules (
    field_id INT NOT NULL PRIMARY KEY,
    is_multiple TINYINT NOT NULL DEFAULT 0,
    max_files INT NOT NULL DEFAULT 1,
    max_file_size_bytes BIGINT NOT NULL DEFAULT 0,
    max_total_size_bytes BIGINT NOT NULL DEFAULT 0,
    allowed_extensions TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_file_values (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS office_asset_declarations (
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
    UNIQUE KEY uniq_asset_declaration_segment (segment_id, office_type, office_id),
    KEY idx_asset_declaration_segment (segment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_import_batches (
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
    updated_at DATETIME DEFAULT NULL,
    KEY idx_asset_import_batches_segment (segment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT DEFAULT NULL,
    asset_id INT NOT NULL,
    user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    summary VARCHAR(255) NOT NULL,
    details LONGTEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_asset_activity_logs_segment (segment_id),
    KEY idx_asset_activity_logs_asset (asset_id),
    KEY idx_asset_activity_logs_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_table_column_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    segment_id INT DEFAULT NULL,
    category_id INT NOT NULL,
    column_key VARCHAR(100) NOT NULL,
    is_visible TINYINT NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_asset_table_column_pref_segment (user_id, segment_id, category_id, column_key),
    KEY idx_asset_table_column_pref_user (user_id),
    KEY idx_asset_table_column_pref_segment (segment_id),
    KEY idx_asset_table_column_pref_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS segment_office_scope_visibility (
    id INT AUTO_INCREMENT PRIMARY KEY,
    segment_id INT NOT NULL,
    office_type TINYINT NOT NULL,
    show_my_office TINYINT NOT NULL DEFAULT 1,
    show_office_under_me TINYINT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL,
    UNIQUE KEY uniq_segment_office_scope_visibility (segment_id, office_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS office_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS office_order_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_order_id INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_ext VARCHAR(20) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY idx_office_order_files_order (office_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bimh_data (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
