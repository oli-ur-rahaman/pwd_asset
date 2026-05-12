CREATE TABLE IF NOT EXISTS zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(255) NOT NULL,
    office_address VARCHAR(255) DEFAULT NULL,
    office_type TINYINT NOT NULL DEFAULT 2,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS circles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    office_name VARCHAR(255) NOT NULL,
    office_address VARCHAR(255) DEFAULT NULL,
    office_type TINYINT NOT NULL DEFAULT 2,
    zone_id INT DEFAULT NULL,
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
