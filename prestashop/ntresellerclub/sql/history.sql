CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_price_history` (
  `id_ntresellerclub_price_history` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_code` VARCHAR(64) NOT NULL,
  `product_type` VARCHAR(50) NOT NULL,
  `code` VARCHAR(100) NOT NULL,
  `old_cost_price` DECIMAL(20,6) DEFAULT NULL,
  `new_cost_price` DECIMAL(20,6) DEFAULT NULL,
  `old_sale_price` DECIMAL(20,6) DEFAULT NULL,
  `new_sale_price` DECIMAL(20,6) DEFAULT NULL,
  `currency` VARCHAR(10) DEFAULT NULL,
  `change_source` VARCHAR(64) DEFAULT 'system',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_price_history`),
  KEY `idx_price_history_provider` (`provider_code`),
  KEY `idx_price_history_code` (`code`),
  KEY `idx_price_history_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_exchange_rate_history` (
  `id_ntresellerclub_exchange_rate_history` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `from_currency` VARCHAR(10) NOT NULL,
  `to_currency` VARCHAR(10) NOT NULL,
  `old_rate` DECIMAL(20,6) DEFAULT NULL,
  `new_rate` DECIMAL(20,6) NOT NULL,
  `change_source` VARCHAR(64) DEFAULT 'admin',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_exchange_rate_history`),
  KEY `idx_exchange_pair` (`from_currency`, `to_currency`),
  KEY `idx_exchange_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
