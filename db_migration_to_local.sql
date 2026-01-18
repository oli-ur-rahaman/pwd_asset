CREATE TABLE `development` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ministry_id` int NOT NULL,
  `fy_id` int NOT NULL,
  `division_id` int NOT NULL,
  `month_val` tinyint NOT NULL DEFAULT '1',
  `pkg` int NOT NULL DEFAULT '0',
  `est` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pkg_live` int NOT NULL DEFAULT '0',
  `pkg_eval` int NOT NULL DEFAULT '0',
  `pkg_cont` int NOT NULL DEFAULT '0',
  `cont` decimal(10,2) NOT NULL DEFAULT '0.00',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_development_fy_div` (`fy_id`,`division_id`,`id`),
  KEY `idx_development_ministry` (`ministry_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `ministries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `vis_opr` tinyint NOT NULL DEFAULT '1',
  `vis_dev` tinyint NOT NULL DEFAULT '1',
  `inuse_status` tinyint NOT NULL DEFAULT '1',
  `def_opr` tinyint NOT NULL DEFAULT '0',
  `def_dev` tinyint NOT NULL DEFAULT '0',
  `def_opr_sl` int NOT NULL DEFAULT '0',
  `def_dev_sl` int NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `operational` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ministry_id` int NOT NULL,
  `fy_id` int NOT NULL,
  `division_id` int NOT NULL,
  `month_val` tinyint NOT NULL DEFAULT '1',
  `pkg` int NOT NULL DEFAULT '0',
  `est` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pkg_live` int NOT NULL DEFAULT '0',
  `pkg_eval` int NOT NULL DEFAULT '0',
  `pkg_cont` int NOT NULL DEFAULT '0',
  `cont` decimal(10,2) NOT NULL DEFAULT '0.00',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_operational_fy_div` (`fy_id`,`division_id`,`id`),
  KEY `idx_operational_ministry` (`ministry_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE `dev_pw` ADD KEY `idx_development_fy_div` (fy_id,division_id,id);
ALTER TABLE `info` ADD COLUMN `i_opr` text NULL;
ALTER TABLE `info` ADD COLUMN `i_dev` text NULL;
ALTER TABLE `opr_repair` ADD KEY `idx_revenue_fy_div` (fy_id,division_id,id);
