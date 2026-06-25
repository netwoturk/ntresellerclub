CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_provider` (
  `id_ntresellerclub_provider` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_code` VARCHAR(64) NOT NULL,
  `provider_name` VARCHAR(128) NOT NULL,
  `provider_type` VARCHAR(50) DEFAULT 'mixed',
  `is_enabled` TINYINT(1) DEFAULT 0,
  `is_licensed` TINYINT(1) DEFAULT 0,
  `version` VARCHAR(32) DEFAULT NULL,
  `config_json` MEDIUMTEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_provider`),
  UNIQUE KEY `uniq_provider_code` (`provider_code`),
  KEY `idx_provider_enabled` (`is_enabled`),
  KEY `idx_provider_licensed` (`is_licensed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `PREFIX_ntresellerclub_provider` (`provider_code`, `provider_name`, `provider_type`, `is_enabled`, `is_licensed`, `version`, `created_at`) VALUES
('resellerclub', 'ResellerClub', 'mixed', 1, 1, '1.0.0', NOW()),
('domainnameapi', 'DomainNameAPI', 'domain', 0, 0, '1.0.0', NOW());

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_tld_route` (
  `id_ntresellerclub_tld_route` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tld` VARCHAR(64) NOT NULL,
  `provider_code` VARCHAR(64) NOT NULL,
  `is_enabled` TINYINT(1) DEFAULT 1,
  `priority` INT UNSIGNED DEFAULT 10,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_tld_route`),
  UNIQUE KEY `uniq_tld_provider` (`tld`, `provider_code`),
  KEY `idx_tld_route_tld` (`tld`),
  KEY `idx_tld_route_provider` (`provider_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `PREFIX_ntresellerclub_tld_route` (`tld`, `provider_code`, `is_enabled`, `priority`, `created_at`) VALUES
('com', 'resellerclub', 1, 10, NOW()),
('net', 'resellerclub', 1, 10, NOW()),
('org', 'resellerclub', 1, 10, NOW()),
('info', 'resellerclub', 1, 10, NOW()),
('biz', 'resellerclub', 1, 10, NOW()),
('tr', 'domainnameapi', 1, 10, NOW()),
('com.tr', 'domainnameapi', 1, 10, NOW()),
('net.tr', 'domainnameapi', 1, 10, NOW()),
('org.tr', 'domainnameapi', 1, 10, NOW()),
('av.tr', 'domainnameapi', 1, 10, NOW()),
('gen.tr', 'domainnameapi', 1, 10, NOW()),
('web.tr', 'domainnameapi', 1, 10, NOW());

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_customer` (
  `id_ntresellerclub_customer` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_customer` INT UNSIGNED NOT NULL,
  `resellerclub_customer_id` BIGINT UNSIGNED DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(64) DEFAULT NULL,
  `company` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_customer`),
  KEY `idx_id_customer` (`id_customer`),
  KEY `idx_provider_customer` (`resellerclub_customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_provider_customer` (
  `id_ntresellerclub_provider_customer` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_customer` INT UNSIGNED NOT NULL,
  `provider_code` VARCHAR(64) NOT NULL,
  `provider_customer_id` VARCHAR(128) DEFAULT NULL,
  `provider_username` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `raw_data` MEDIUMTEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_provider_customer`),
  UNIQUE KEY `uniq_customer_provider` (`id_customer`, `provider_code`),
  KEY `idx_provider_customer_code` (`provider_code`),
  KEY `idx_provider_customer_id` (`provider_customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_contact` (
  `id_ntresellerclub_contact` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_customer` INT UNSIGNED NOT NULL,
  `provider_code` VARCHAR(64) DEFAULT NULL,
  `provider_contact_id` BIGINT UNSIGNED DEFAULT NULL,
  `contact_type` VARCHAR(50) DEFAULT 'domain',
  `firstname` VARCHAR(128) DEFAULT NULL,
  `lastname` VARCHAR(128) DEFAULT NULL,
  `company` VARCHAR(255) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `phone` VARCHAR(64) DEFAULT NULL,
  `country` VARCHAR(8) DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_contact`),
  KEY `idx_contact_customer` (`id_customer`),
  KEY `idx_contact_provider` (`provider_code`),
  KEY `idx_provider_contact` (`provider_contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_service` (
  `id_ntresellerclub_service` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_customer` INT UNSIGNED NOT NULL,
  `id_order` INT UNSIGNED DEFAULT NULL,
  `id_product` INT UNSIGNED DEFAULT NULL,
  `provider_code` VARCHAR(64) DEFAULT NULL,
  `service_type` VARCHAR(50) NOT NULL,
  `domain_name` VARCHAR(255) DEFAULT NULL,
  `provider_service_id` VARCHAR(128) DEFAULT NULL,
  `provider_order_id` VARCHAR(128) DEFAULT NULL,
  `provider_customer_id` VARCHAR(128) DEFAULT NULL,
  `provider_contact_id` VARCHAR(128) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `renew_price` DECIMAL(20,6) DEFAULT NULL,
  `transfer_price` DECIMAL(20,6) DEFAULT NULL,
  `restore_price` DECIMAL(20,6) DEFAULT NULL,
  `currency` VARCHAR(10) DEFAULT NULL,
  `last_sync` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_service`),
  KEY `idx_service_customer` (`id_customer`),
  KEY `idx_service_order` (`id_order`),
  KEY `idx_service_provider` (`provider_code`),
  KEY `idx_service_domain` (`domain_name`),
  KEY `idx_service_expiry` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_cart_domain` (
  `id_ntresellerclub_cart_domain` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_cart` INT UNSIGNED NOT NULL,
  `domain_name` VARCHAR(255) NOT NULL,
  `provider_code` VARCHAR(64) DEFAULT NULL,
  `years` INT UNSIGNED DEFAULT 1,
  `options_json` MEDIUMTEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_cart_domain`),
  KEY `idx_id_cart` (`id_cart`),
  KEY `idx_cart_provider` (`provider_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_price` (
  `id_ntresellerclub_price` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_code` VARCHAR(64) DEFAULT NULL,
  `product_type` VARCHAR(50) NOT NULL,
  `code` VARCHAR(100) NOT NULL,
  `years` INT UNSIGNED DEFAULT 1,
  `cost_price` DECIMAL(20,6) DEFAULT NULL,
  `sale_price` DECIMAL(20,6) DEFAULT NULL,
  `currency` VARCHAR(10) DEFAULT NULL,
  `margin_mode` VARCHAR(32) DEFAULT 'manual',
  `margin_percent` DECIMAL(20,6) DEFAULT 0,
  `margin_fixed` DECIMAL(20,6) DEFAULT 0,
  `tax_included` TINYINT(1) DEFAULT 1,
  `last_sync` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_price`),
  UNIQUE KEY `uniq_price_code` (`provider_code`, `product_type`, `code`, `years`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_operation` (
  `id_ntresellerclub_operation` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_service` INT UNSIGNED NOT NULL,
  `provider_code` VARCHAR(64) NOT NULL,
  `domain_name` VARCHAR(255) DEFAULT NULL,
  `action` VARCHAR(64) NOT NULL,
  `payload_json` MEDIUMTEXT DEFAULT NULL,
  `response_json` MEDIUMTEXT DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `created_at` DATETIME NOT NULL,
  `processed_at` DATETIME DEFAULT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_operation`),
  KEY `idx_operation_service` (`id_service`),
  KEY `idx_operation_provider` (`provider_code`),
  KEY `idx_operation_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_provider_health` (
  `id_ntresellerclub_provider_health` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_code` VARCHAR(64) NOT NULL,
  `status` VARCHAR(32) NOT NULL,
  `is_enabled` TINYINT(1) DEFAULT 0,
  `is_licensed` TINYINT(1) DEFAULT 0,
  `queue_pending` INT UNSIGNED DEFAULT 0,
  `queue_failed` INT UNSIGNED DEFAULT 0,
  `last_error` TEXT DEFAULT NULL,
  `response_time_ms` INT UNSIGNED DEFAULT 0,
  `checked_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_provider_health`),
  KEY `idx_provider_health_provider` (`provider_code`),
  KEY `idx_provider_health_status` (`status`),
  KEY `idx_provider_health_checked` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_runtime_health` (
  `id_ntresellerclub_runtime_health` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `context` VARCHAR(64) NOT NULL,
  `status` VARCHAR(32) NOT NULL,
  `memory_limit` VARCHAR(32) DEFAULT NULL,
  `memory_usage_bytes` BIGINT UNSIGNED DEFAULT 0,
  `memory_peak_bytes` BIGINT UNSIGNED DEFAULT 0,
  `max_execution_time` INT UNSIGNED DEFAULT 0,
  `batch_limit` INT UNSIGNED DEFAULT 0,
  `php_sapi` VARCHAR(64) DEFAULT NULL,
  `queue_pending` INT UNSIGNED DEFAULT 0,
  `queue_processing` INT UNSIGNED DEFAULT 0,
  `queue_failed` INT UNSIGNED DEFAULT 0,
  `last_cron_at` DATETIME DEFAULT NULL,
  `checked_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_runtime_health`),
  KEY `idx_runtime_health_context` (`context`),
  KEY `idx_runtime_health_status` (`status`),
  KEY `idx_runtime_health_checked` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_provider_statistics` (
  `id_ntresellerclub_provider_statistics` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_code` VARCHAR(64) NOT NULL,
  `metric_date` DATE NOT NULL,
  `total_queue` INT UNSIGNED DEFAULT 0,
  `pending_queue` INT UNSIGNED DEFAULT 0,
  `processing_queue` INT UNSIGNED DEFAULT 0,
  `done_queue` INT UNSIGNED DEFAULT 0,
  `failed_queue` INT UNSIGNED DEFAULT 0,
  `retry_queue` INT UNSIGNED DEFAULT 0,
  `avg_retry` DECIMAL(10,4) DEFAULT 0,
  `last_success_at` DATETIME DEFAULT NULL,
  `last_failure_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_provider_statistics`),
  UNIQUE KEY `uniq_provider_statistics_day` (`provider_code`, `metric_date`),
  KEY `idx_provider_statistics_provider` (`provider_code`),
  KEY `idx_provider_statistics_date` (`metric_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_notification_template` (
  `id_ntresellerclub_notification_template` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_key` VARCHAR(100) NOT NULL,
  `lang_iso` VARCHAR(5) NOT NULL,
  `recipient_type` VARCHAR(32) NOT NULL DEFAULT 'customer',
  `subject` VARCHAR(255) NOT NULL,
  `body_html` MEDIUMTEXT DEFAULT NULL,
  `body_text` MEDIUMTEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_notification_template`),
  UNIQUE KEY `uniq_notification_template` (`template_key`, `lang_iso`, `recipient_type`),
  KEY `idx_notification_template_key` (`template_key`),
  KEY `idx_notification_template_lang` (`lang_iso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_notification_queue` (
  `id_ntresellerclub_notification_queue` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_key` VARCHAR(100) NOT NULL,
  `lang_iso` VARCHAR(5) NOT NULL DEFAULT 'en',
  `recipient_type` VARCHAR(32) NOT NULL DEFAULT 'customer',
  `id_customer` INT UNSIGNED DEFAULT NULL,
  `id_service` INT UNSIGNED DEFAULT NULL,
  `to_email` VARCHAR(255) NOT NULL,
  `to_name` VARCHAR(255) DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_html` MEDIUMTEXT DEFAULT NULL,
  `body_text` MEDIUMTEXT DEFAULT NULL,
  `variables_json` MEDIUMTEXT DEFAULT NULL,
  `dedupe_key` VARCHAR(191) DEFAULT NULL,
  `priority` INT UNSIGNED NOT NULL DEFAULT 3,
  `status` VARCHAR(32) NOT NULL DEFAULT 'pending',
  `retry_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `max_retries` INT UNSIGNED NOT NULL DEFAULT 3,
  `last_error` TEXT DEFAULT NULL,
  `lock_token` VARCHAR(128) DEFAULT NULL,
  `locked_at` DATETIME DEFAULT NULL,
  `available_at` DATETIME DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_notification_queue`),
  UNIQUE KEY `uniq_notification_dedupe` (`dedupe_key`),
  KEY `idx_notification_queue_status` (`status`, `priority`),
  KEY `idx_notification_queue_customer` (`id_customer`),
  KEY `idx_notification_queue_service` (`id_service`),
  KEY `idx_notification_queue_template` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_notification_log` (
  `id_ntresellerclub_notification_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_notification_queue` INT UNSIGNED DEFAULT NULL,
  `template_key` VARCHAR(100) DEFAULT NULL,
  `recipient_type` VARCHAR(32) DEFAULT NULL,
  `to_email` VARCHAR(255) DEFAULT NULL,
  `status` VARCHAR(32) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_notification_log`),
  KEY `idx_notification_log_queue` (`id_notification_queue`),
  KEY `idx_notification_log_status` (`status`),
  KEY `idx_notification_log_template` (`template_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_notice` (
  `id_ntresellerclub_notice` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_service` INT UNSIGNED NOT NULL,
  `notice_type` VARCHAR(50) NOT NULL,
  `days_before` INT DEFAULT NULL,
  `sent_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_notice`),
  KEY `idx_notice_service` (`id_service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_log` (
  `id_ntresellerclub_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` VARCHAR(20) NOT NULL,
  `context` VARCHAR(100) DEFAULT NULL,
  `message` TEXT,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_log`),
  KEY `idx_log_level` (`level`),
  KEY `idx_log_context` (`context`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_license` (
  `id_ntresellerclub_license` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `domain` VARCHAR(255) NOT NULL,
  `license_key` VARCHAR(255) NOT NULL,
  `status` VARCHAR(50) DEFAULT 'inactive',
  `expires_at` DATE DEFAULT NULL,
  `last_check` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_license`),
  KEY `idx_license_domain` (`domain`),
  KEY `idx_license_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;