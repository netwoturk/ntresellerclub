-- NetwoTürk ResellerClub PrestaShop Module
-- Initial database schema draft

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_customer` (
  `id_ntresellerclub_customer` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_customer` INT UNSIGNED NOT NULL,
  `resellerclub_customer_id` BIGINT UNSIGNED DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_customer`),
  KEY `idx_id_customer` (`id_customer`),
  KEY `idx_rc_customer_id` (`resellerclub_customer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_service` (
  `id_ntresellerclub_service` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_customer` INT UNSIGNED NOT NULL,
  `id_order` INT UNSIGNED DEFAULT NULL,
  `id_product` INT UNSIGNED DEFAULT NULL,
  `service_type` VARCHAR(50) NOT NULL,
  `domain_name` VARCHAR(255) DEFAULT NULL,
  `resellerclub_customer_id` BIGINT UNSIGNED DEFAULT NULL,
  `resellerclub_contact_id` BIGINT UNSIGNED DEFAULT NULL,
  `resellerclub_order_id` BIGINT UNSIGNED DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `renew_price` DECIMAL(20,6) DEFAULT NULL,
  `transfer_price` DECIMAL(20,6) DEFAULT NULL,
  `restore_price` DECIMAL(20,6) DEFAULT NULL,
  `last_sync` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_service`),
  KEY `idx_customer` (`id_customer`),
  KEY `idx_order` (`id_order`),
  KEY `idx_domain` (`domain_name`),
  KEY `idx_expiry` (`expiry_date`),
  KEY `idx_rc_order` (`resellerclub_order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_price` (
  `id_ntresellerclub_price` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_type` VARCHAR(50) NOT NULL,
  `code` VARCHAR(100) NOT NULL,
  `cost_price` DECIMAL(20,6) DEFAULT NULL,
  `sale_price` DECIMAL(20,6) DEFAULT NULL,
  `currency` VARCHAR(10) DEFAULT NULL,
  `years` INT UNSIGNED DEFAULT NULL,
  `last_sync` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_price`),
  UNIQUE KEY `uniq_product_code_years` (`product_type`, `code`, `years`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_log` (
  `id_ntresellerclub_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `level` VARCHAR(20) NOT NULL,
  `context` VARCHAR(100) DEFAULT NULL,
  `message` TEXT,
  `request_data` MEDIUMTEXT,
  `response_data` MEDIUMTEXT,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_log`),
  KEY `idx_level` (`level`),
  KEY `idx_context` (`context`)
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
  KEY `idx_domain` (`domain`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
