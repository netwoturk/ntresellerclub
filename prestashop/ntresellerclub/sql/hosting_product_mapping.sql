CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_hosting_product_mapping` (
  `id_ntresellerclub_hosting_product_mapping` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_product` INT UNSIGNED NOT NULL,
  `provider_code` VARCHAR(64) NOT NULL DEFAULT 'resellerclub',
  `provider_product_id` VARCHAR(128) NOT NULL,
  `package_name` VARCHAR(128) NOT NULL,
  `billing_cycle` VARCHAR(32) NOT NULL DEFAULT 'yearly',
  `cost_price` DECIMAL(20,6) DEFAULT 0,
  `sale_price` DECIMAL(20,6) DEFAULT 0,
  `currency` VARCHAR(10) DEFAULT 'USD',
  `active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_ntresellerclub_hosting_product_mapping`),
  UNIQUE KEY `uniq_hosting_product_provider` (`id_product`, `provider_code`),
  KEY `idx_hosting_mapping_provider_product` (`provider_code`, `provider_product_id`),
  KEY `idx_hosting_mapping_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
