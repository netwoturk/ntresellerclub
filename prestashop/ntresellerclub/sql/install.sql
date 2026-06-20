CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_customer` (
  `id_ntresellerclub_customer` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_customer` INT UNSIGNED NOT NULL,
  `resellerclub_customer_id` BIGINT UNSIGNED DEFAULT NULL,
  `email` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_customer`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_service` (
  `id_ntresellerclub_service` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_customer` INT UNSIGNED NOT NULL,
  `id_order` INT UNSIGNED DEFAULT NULL,
  `service_type` VARCHAR(50) NOT NULL,
  `domain_name` VARCHAR(255) DEFAULT NULL,
  `provider_order_id` BIGINT UNSIGNED DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'pending',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `PREFIX_ntresellerclub_cart_domain` (
  `id_ntresellerclub_cart_domain` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `id_cart` INT UNSIGNED NOT NULL,
  `domain_name` VARCHAR(255) NOT NULL,
  `years` INT UNSIGNED DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id_ntresellerclub_cart_domain`),
  KEY `idx_id_cart` (`id_cart`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
