<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcInstaller
{
    public static function installSql()
    {
        foreach (array('install.sql', 'history.sql') as $sqlFile) {
            if (!self::executeSqlFile($sqlFile)) {
                return false;
            }
        }

        return self::ensureOperationQueueSchema()
            && self::ensureProviderCustomerSchema()
            && self::ensureContactProfileSchema()
            && self::ensureServiceSchema()
            && self::ensureHostingProductMappingSchema()
            && self::ensureSslProductMappingSchema()
            && self::ensureCartDomainSchema()
            && self::ensurePricingSchema()
            && self::ensureBillingEventSchema()
            && self::ensureMonitoringSchema()
            && self::ensureNotificationSchema();
    }

    protected static function executeSqlFile($sqlFile)
    {
        $file = _PS_MODULE_DIR_ . 'ntresellerclub/sql/' . $sqlFile;
        if (!file_exists($file)) {
            return false;
        }

        $sql = file_get_contents($file);
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $queries = preg_split('/;\s*[\r\n]+/', $sql);

        foreach ((array)$queries as $query) {
            $query = trim($query);
            if ($query === '') {
                continue;
            }
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public static function ensureOperationQueueSchema()
    {
        if (!self::addColumnIfMissing('ntresellerclub_operation_queue', 'priority', 'INT UNSIGNED NOT NULL DEFAULT 3 AFTER `action`')) {
            return false;
        }

        return self::addColumnIfMissing('ntresellerclub_operation_queue', 'lock_token', 'VARCHAR(128) DEFAULT NULL AFTER `last_error`');
    }

    public static function ensureProviderCustomerSchema()
    {
        $columns = array(
            'id_customer' => 'INT UNSIGNED NOT NULL AFTER `id_ntresellerclub_provider_customer`',
            'provider_code' => 'VARCHAR(64) NOT NULL AFTER `id_customer`',
            'provider_customer_id' => 'VARCHAR(128) DEFAULT NULL AFTER `provider_code`',
            'email' => 'VARCHAR(255) NOT NULL AFTER `provider_customer_id`',
            'status' => 'VARCHAR(50) DEFAULT "pending" AFTER `email`',
            'created_at' => 'DATETIME NOT NULL AFTER `status`',
            'updated_at' => 'DATETIME DEFAULT NULL AFTER `created_at`',
        );

        foreach ($columns as $column => $definition) {
            if (!self::addColumnIfMissing('ntresellerclub_provider_customer', $column, $definition)) {
                return false;
            }
        }

        return true;
    }

    public static function ensureServiceSchema()
    {
        $columns = array(
            'provider_service_id' => 'VARCHAR(128) DEFAULT NULL AFTER `domain_name`',
            'provider_order_id' => 'VARCHAR(128) DEFAULT NULL AFTER `provider_service_id`',
            'provider_customer_id' => 'VARCHAR(128) DEFAULT NULL AFTER `provider_order_id`',
            'provider_contact_id' => 'VARCHAR(128) DEFAULT NULL AFTER `provider_customer_id`',
            'ssl_certificate_number' => 'VARCHAR(128) DEFAULT NULL AFTER `provider_contact_id`',
            'start_date' => 'DATE DEFAULT NULL AFTER `ssl_certificate_number`',
            'expiry_date' => 'DATE DEFAULT NULL AFTER `start_date`',
            'last_sync' => 'DATETIME DEFAULT NULL AFTER `currency`',
            'updated_at' => 'DATETIME DEFAULT NULL AFTER `created_at`',
        );

        foreach ($columns as $column => $definition) {
            if (!self::addColumnIfMissing('ntresellerclub_service', $column, $definition)) {
                return false;
            }
        }

        return true;
    }

    public static function ensureHostingProductMappingSchema()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_hosting_product_mapping` ('
            . '`id_ntresellerclub_hosting_product_mapping` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . '`id_product` INT UNSIGNED NOT NULL,'
            . '`provider_code` VARCHAR(64) NOT NULL DEFAULT "resellerclub",'
            . '`provider_product_id` VARCHAR(128) NOT NULL,'
            . '`package_name` VARCHAR(128) NOT NULL,'
            . '`billing_cycle` VARCHAR(32) NOT NULL DEFAULT "yearly",'
            . '`cost_price` DECIMAL(20,6) DEFAULT 0,'
            . '`sale_price` DECIMAL(20,6) DEFAULT 0,'
            . '`currency` VARCHAR(10) DEFAULT "USD",'
            . '`active` TINYINT(1) DEFAULT 1,'
            . '`created_at` DATETIME NOT NULL,'
            . '`updated_at` DATETIME DEFAULT NULL,'
            . 'PRIMARY KEY (`id_ntresellerclub_hosting_product_mapping`),'
            . 'UNIQUE KEY `uniq_hosting_product_provider` (`id_product`, `provider_code`),'
            . 'KEY `idx_hosting_mapping_provider_product` (`provider_code`, `provider_product_id`),'
            . 'KEY `idx_hosting_mapping_active` (`active`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $columns = array(
            'id_product' => 'INT UNSIGNED NOT NULL AFTER `id_ntresellerclub_hosting_product_mapping`',
            'provider_code' => 'VARCHAR(64) NOT NULL DEFAULT "resellerclub" AFTER `id_product`',
            'provider_product_id' => 'VARCHAR(128) NOT NULL AFTER `provider_code`',
            'package_name' => 'VARCHAR(128) NOT NULL AFTER `provider_product_id`',
            'billing_cycle' => 'VARCHAR(32) NOT NULL DEFAULT "yearly" AFTER `package_name`',
            'cost_price' => 'DECIMAL(20,6) DEFAULT 0 AFTER `billing_cycle`',
            'sale_price' => 'DECIMAL(20,6) DEFAULT 0 AFTER `cost_price`',
            'currency' => 'VARCHAR(10) DEFAULT "USD" AFTER `sale_price`',
            'active' => 'TINYINT(1) DEFAULT 1 AFTER `currency`',
            'created_at' => 'DATETIME NOT NULL AFTER `active`',
            'updated_at' => 'DATETIME DEFAULT NULL AFTER `created_at`',
        );

        foreach ($columns as $column => $definition) {
            if (!self::addColumnIfMissing('ntresellerclub_hosting_product_mapping', $column, $definition)) {
                return false;
            }
        }

        return true;
    }

    public static function ensureSslProductMappingSchema()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_ssl_product_mapping` ('
            . '`id_ntresellerclub_ssl_product_mapping` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . '`id_product` INT UNSIGNED NOT NULL,'
            . '`provider_code` VARCHAR(64) NOT NULL DEFAULT "resellerclub",'
            . '`provider_product_id` VARCHAR(128) NOT NULL,'
            . '`ssl_product_type` VARCHAR(64) NOT NULL DEFAULT "standard",'
            . '`billing_cycle` VARCHAR(32) NOT NULL DEFAULT "yearly",'
            . '`cost_price` DECIMAL(20,6) DEFAULT 0,'
            . '`sale_price` DECIMAL(20,6) DEFAULT 0,'
            . '`currency` VARCHAR(10) DEFAULT "USD",'
            . '`active` TINYINT(1) DEFAULT 1,'
            . '`created_at` DATETIME NOT NULL,'
            . '`updated_at` DATETIME DEFAULT NULL,'
            . 'PRIMARY KEY (`id_ntresellerclub_ssl_product_mapping`),'
            . 'UNIQUE KEY `uniq_ssl_product_provider` (`id_product`, `provider_code`),'
            . 'KEY `idx_ssl_mapping_provider_product` (`provider_code`, `provider_product_id`),'
            . 'KEY `idx_ssl_mapping_active` (`active`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $columns = array(
            'id_product' => 'INT UNSIGNED NOT NULL AFTER `id_ntresellerclub_ssl_product_mapping`',
            'provider_code' => 'VARCHAR(64) NOT NULL DEFAULT "resellerclub" AFTER `id_product`',
            'provider_product_id' => 'VARCHAR(128) NOT NULL AFTER `provider_code`',
            'ssl_product_type' => 'VARCHAR(64) NOT NULL DEFAULT "standard" AFTER `provider_product_id`',
            'billing_cycle' => 'VARCHAR(32) NOT NULL DEFAULT "yearly" AFTER `ssl_product_type`',
            'cost_price' => 'DECIMAL(20,6) DEFAULT 0 AFTER `billing_cycle`',
            'sale_price' => 'DECIMAL(20,6) DEFAULT 0 AFTER `cost_price`',
            'currency' => 'VARCHAR(10) DEFAULT "USD" AFTER `sale_price`',
            'active' => 'TINYINT(1) DEFAULT 1 AFTER `currency`',
            'created_at' => 'DATETIME NOT NULL AFTER `active`',
            'updated_at' => 'DATETIME DEFAULT NULL AFTER `created_at`',
        );

        foreach ($columns as $column => $definition) {
            if (!self::addColumnIfMissing('ntresellerclub_ssl_product_mapping', $column, $definition)) {
                return false;
            }
        }

        return true;
    }

    public static function ensureCartDomainSchema()
    {
        $columns = array(
            'options_json' => 'MEDIUMTEXT DEFAULT NULL AFTER `years`',
            'updated_at' => 'DATETIME DEFAULT NULL AFTER `created_at`',
        );

        foreach ($columns as $column => $definition) {
            if (!self::addColumnIfMissing('ntresellerclub_cart_domain', $column, $definition)) {
                return false;
            }
        }

        return true;
    }

    public static function ensurePricingSchema()
    {
        $columns = array(
            'target_currency' => 'VARCHAR(10) DEFAULT NULL AFTER `currency`',
            'tax_included' => 'TINYINT(1) DEFAULT 1 AFTER `margin_fixed`',
            'tax_rate' => 'DECIMAL(10,4) DEFAULT 20 AFTER `tax_included`',
            'rounding_mode' => 'VARCHAR(32) DEFAULT "no_round" AFTER `tax_rate`',
            'created_at' => 'DATETIME DEFAULT NULL AFTER `rounding_mode`',
            'updated_at' => 'DATETIME DEFAULT NULL AFTER `created_at`',
        );

        foreach ($columns as $column => $definition) {
            if (!self::addColumnIfMissing('ntresellerclub_price', $column, $definition)) {
                return false;
            }
        }

        return true;
    }

    public static function ensureMonitoringSchema()
    {
        $queries = array(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_provider_health` ('
                . '`id_ntresellerclub_provider_health` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                . '`provider_code` VARCHAR(64) NOT NULL,'
                . '`status` VARCHAR(32) NOT NULL,'
                . '`is_enabled` TINYINT(1) DEFAULT 0,'
                . '`is_licensed` TINYINT(1) DEFAULT 0,'
                . '`queue_pending` INT UNSIGNED DEFAULT 0,'
                . '`queue_failed` INT UNSIGNED DEFAULT 0,'
                . '`last_error` TEXT DEFAULT NULL,'
                . '`response_time_ms` INT UNSIGNED DEFAULT 0,'
                . '`checked_at` DATETIME NOT NULL,'
                . '`created_at` DATETIME NOT NULL,'
                . 'PRIMARY KEY (`id_ntresellerclub_provider_health`),'
                . 'KEY `idx_provider_health_provider` (`provider_code`),'
                . 'KEY `idx_provider_health_status` (`status`),'
                . 'KEY `idx_provider_health_checked` (`checked_at`)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_runtime_health` ('
                . '`id_ntresellerclub_runtime_health` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                . '`context` VARCHAR(64) NOT NULL,'
                . '`status` VARCHAR(32) NOT NULL,'
                . '`memory_limit` VARCHAR(32) DEFAULT NULL,'
                . '`memory_usage_bytes` BIGINT UNSIGNED DEFAULT 0,'
                . '`memory_peak_bytes` BIGINT UNSIGNED DEFAULT 0,'
                . '`max_execution_time` INT UNSIGNED DEFAULT 0,'
                . '`batch_limit` INT UNSIGNED DEFAULT 0,'
                . '`php_sapi` VARCHAR(64) DEFAULT NULL,'
                . '`queue_pending` INT UNSIGNED DEFAULT 0,'
                . '`queue_processing` INT UNSIGNED DEFAULT 0,'
                . '`queue_failed` INT UNSIGNED DEFAULT 0,'
                . '`last_cron_at` DATETIME DEFAULT NULL,'
                . '`checked_at` DATETIME NOT NULL,'
                . '`created_at` DATETIME NOT NULL,'
                . 'PRIMARY KEY (`id_ntresellerclub_runtime_health`),'
                . 'KEY `idx_runtime_health_context` (`context`),'
                . 'KEY `idx_runtime_health_status` (`status`),'
                . 'KEY `idx_runtime_health_checked` (`checked_at`)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_provider_statistics` ('
                . '`id_ntresellerclub_provider_statistics` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                . '`provider_code` VARCHAR(64) NOT NULL,'
                . '`metric_date` DATE NOT NULL,'
                . '`total_queue` INT UNSIGNED DEFAULT 0,'
                . '`pending_queue` INT UNSIGNED DEFAULT 0,'
                . '`processing_queue` INT UNSIGNED DEFAULT 0,'
                . '`done_queue` INT UNSIGNED DEFAULT 0,'
                . '`failed_queue` INT UNSIGNED DEFAULT 0,'
                . '`retry_queue` INT UNSIGNED DEFAULT 0,'
                . '`avg_retry` DECIMAL(10,4) DEFAULT 0,'
                . '`last_success_at` DATETIME DEFAULT NULL,'
                . '`last_failure_at` DATETIME DEFAULT NULL,'
                . '`created_at` DATETIME NOT NULL,'
                . '`updated_at` DATETIME DEFAULT NULL,'
                . 'PRIMARY KEY (`id_ntresellerclub_provider_statistics`),'
                . 'UNIQUE KEY `uniq_provider_statistics_day` (`provider_code`, `metric_date`),'
                . 'KEY `idx_provider_statistics_provider` (`provider_code`),'
                . 'KEY `idx_provider_statistics_date` (`metric_date`)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        foreach ($queries as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public static function ensureBillingEventSchema()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_billing_event` ('
            . '`id_ntresellerclub_billing_event` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . '`id_order` INT UNSIGNED DEFAULT NULL,'
            . '`id_customer` INT UNSIGNED DEFAULT NULL,'
            . '`id_service` INT UNSIGNED DEFAULT NULL,'
            . '`provider_code` VARCHAR(64) DEFAULT NULL,'
            . '`service_type` VARCHAR(50) DEFAULT NULL,'
            . '`event_type` VARCHAR(100) NOT NULL,'
            . '`event_status` VARCHAR(50) NOT NULL,'
            . '`message` TEXT DEFAULT NULL,'
            . '`metadata_json` MEDIUMTEXT DEFAULT NULL,'
            . '`created_at` DATETIME NOT NULL,'
            . 'PRIMARY KEY (`id_ntresellerclub_billing_event`),'
            . 'KEY `idx_billing_event_order` (`id_order`),'
            . 'KEY `idx_billing_event_customer` (`id_customer`),'
            . 'KEY `idx_billing_event_service` (`id_service`),'
            . 'KEY `idx_billing_event_provider` (`provider_code`),'
            . 'KEY `idx_billing_event_type` (`event_type`, `event_status`),'
            . 'KEY `idx_billing_event_created` (`created_at`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        return Db::getInstance()->execute($sql);
    }

    public static function ensureNotificationSchema()
    {
        $queries = array(
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_notification_template` ('
                . '`id_ntresellerclub_notification_template` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                . '`template_key` VARCHAR(100) NOT NULL,'
                . '`lang_iso` VARCHAR(5) NOT NULL,'
                . '`recipient_type` VARCHAR(32) NOT NULL DEFAULT "customer",'
                . '`subject` VARCHAR(255) NOT NULL,'
                . '`body_html` MEDIUMTEXT DEFAULT NULL,'
                . '`body_text` MEDIUMTEXT DEFAULT NULL,'
                . '`is_active` TINYINT(1) DEFAULT 1,'
                . '`created_at` DATETIME NOT NULL,'
                . '`updated_at` DATETIME DEFAULT NULL,'
                . 'PRIMARY KEY (`id_ntresellerclub_notification_template`),'
                . 'UNIQUE KEY `uniq_notification_template` (`template_key`, `lang_iso`, `recipient_type`),'
                . 'KEY `idx_notification_template_key` (`template_key`),'
                . 'KEY `idx_notification_template_lang` (`lang_iso`)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_notification_queue` ('
                . '`id_ntresellerclub_notification_queue` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                . '`template_key` VARCHAR(100) NOT NULL,'
                . '`lang_iso` VARCHAR(5) NOT NULL DEFAULT "en",'
                . '`recipient_type` VARCHAR(32) NOT NULL DEFAULT "customer",'
                . '`id_customer` INT UNSIGNED DEFAULT NULL,'
                . '`id_service` INT UNSIGNED DEFAULT NULL,'
                . '`to_email` VARCHAR(255) NOT NULL,'
                . '`to_name` VARCHAR(255) DEFAULT NULL,'
                . '`subject` VARCHAR(255) NOT NULL,'
                . '`body_html` MEDIUMTEXT DEFAULT NULL,'
                . '`body_text` MEDIUMTEXT DEFAULT NULL,'
                . '`variables_json` MEDIUMTEXT DEFAULT NULL,'
                . '`dedupe_key` VARCHAR(191) DEFAULT NULL,'
                . '`priority` INT UNSIGNED NOT NULL DEFAULT 3,'
                . '`status` VARCHAR(32) NOT NULL DEFAULT "pending",'
                . '`retry_count` INT UNSIGNED NOT NULL DEFAULT 0,'
                . '`max_retries` INT UNSIGNED NOT NULL DEFAULT 3,'
                . '`last_error` TEXT DEFAULT NULL,'
                . '`lock_token` VARCHAR(128) DEFAULT NULL,'
                . '`locked_at` DATETIME DEFAULT NULL,'
                . '`available_at` DATETIME DEFAULT NULL,'
                . '`sent_at` DATETIME DEFAULT NULL,'
                . '`created_at` DATETIME NOT NULL,'
                . '`updated_at` DATETIME DEFAULT NULL,'
                . 'PRIMARY KEY (`id_ntresellerclub_notification_queue`),'
                . 'UNIQUE KEY `uniq_notification_dedupe` (`dedupe_key`),'
                . 'KEY `idx_notification_queue_status` (`status`, `priority`),'
                . 'KEY `idx_notification_queue_customer` (`id_customer`),'
                . 'KEY `idx_notification_queue_service` (`id_service`),'
                . 'KEY `idx_notification_queue_template` (`template_key`)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
            'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_notification_log` ('
                . '`id_ntresellerclub_notification_log` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
                . '`id_notification_queue` INT UNSIGNED DEFAULT NULL,'
                . '`template_key` VARCHAR(100) DEFAULT NULL,'
                . '`recipient_type` VARCHAR(32) DEFAULT NULL,'
                . '`to_email` VARCHAR(255) DEFAULT NULL,'
                . '`status` VARCHAR(32) NOT NULL,'
                . '`message` TEXT DEFAULT NULL,'
                . '`created_at` DATETIME NOT NULL,'
                . 'PRIMARY KEY (`id_ntresellerclub_notification_log`),'
                . 'KEY `idx_notification_log_queue` (`id_notification_queue`),'
                . 'KEY `idx_notification_log_status` (`status`),'
                . 'KEY `idx_notification_log_template` (`template_key`)'
                . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        foreach ($queries as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    public static function ensureContactProfileSchema()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_contact_profile` ('
            . '`id_ntresellerclub_contact_profile` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . '`id_customer` INT UNSIGNED NOT NULL,'
            . '`profile_type` VARCHAR(50) DEFAULT "personal",'
            . '`company_name` VARCHAR(255) DEFAULT NULL,'
            . '`first_name` VARCHAR(128) DEFAULT NULL,'
            . '`last_name` VARCHAR(128) DEFAULT NULL,'
            . '`tax_number` VARCHAR(64) DEFAULT NULL,'
            . '`tax_office` VARCHAR(128) DEFAULT NULL,'
            . '`tc_number` VARCHAR(64) DEFAULT NULL,'
            . '`address` TEXT DEFAULT NULL,'
            . '`city` VARCHAR(128) DEFAULT NULL,'
            . '`state` VARCHAR(128) DEFAULT NULL,'
            . '`country_iso` VARCHAR(5) DEFAULT NULL,'
            . '`postcode` VARCHAR(32) DEFAULT NULL,'
            . '`phone` VARCHAR(64) DEFAULT NULL,'
            . '`email` VARCHAR(255) DEFAULT NULL,'
            . '`is_default` TINYINT(1) DEFAULT 0,'
            . '`created_at` DATETIME NOT NULL,'
            . '`updated_at` DATETIME DEFAULT NULL,'
            . 'PRIMARY KEY (`id_ntresellerclub_contact_profile`),'
            . 'KEY `idx_contact_profile_customer` (`id_customer`),'
            . 'KEY `idx_contact_profile_default` (`id_customer`, `is_default`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $columns = array(
            'profile_type' => 'VARCHAR(50) DEFAULT "personal" AFTER `id_customer`',
            'company_name' => 'VARCHAR(255) DEFAULT NULL AFTER `profile_type`',
            'first_name' => 'VARCHAR(128) DEFAULT NULL AFTER `company_name`',
            'last_name' => 'VARCHAR(128) DEFAULT NULL AFTER `first_name`',
            'tax_number' => 'VARCHAR(64) DEFAULT NULL AFTER `last_name`',
            'tax_office' => 'VARCHAR(128) DEFAULT NULL AFTER `tax_number`',
            'tc_number' => 'VARCHAR(64) DEFAULT NULL AFTER `tax_office`',
            'address' => 'TEXT DEFAULT NULL AFTER `tc_number`',
            'city' => 'VARCHAR(128) DEFAULT NULL AFTER `address`',
            'state' => 'VARCHAR(128) DEFAULT NULL AFTER `city`',
            'country_iso' => 'VARCHAR(5) DEFAULT NULL AFTER `state`',
            'postcode' => 'VARCHAR(32) DEFAULT NULL AFTER `country_iso`',
            'phone' => 'VARCHAR(64) DEFAULT NULL AFTER `postcode`',
            'email' => 'VARCHAR(255) DEFAULT NULL AFTER `phone`',
            'is_default' => 'TINYINT(1) DEFAULT 0 AFTER `email`',
            'created_at' => 'DATETIME NOT NULL AFTER `is_default`',
            'updated_at' => 'DATETIME DEFAULT NULL AFTER `created_at`',
        );

        foreach ($columns as $column => $definition) {
            if (!self::addColumnIfMissing('ntresellerclub_contact_profile', $column, $definition)) {
                return false;
            }
        }

        return true;
    }

    protected static function addColumnIfMissing($table, $column, $definition)
    {
        $fullTable = _DB_PREFIX_ . $table;
        $exists = Db::getInstance()->getValue('SHOW COLUMNS FROM `' . pSQL($fullTable) . '` LIKE "' . pSQL($column) . '"');
        if ($exists) {
            return true;
        }

        return Db::getInstance()->execute('ALTER TABLE `' . pSQL($fullTable) . '` ADD `' . pSQL($column) . '` ' . $definition);
    }

    public static function uninstallSql($dropTables = false)
    {
        if (!$dropTables) {
            return true;
        }

        $tables = array(
            'ntresellerclub_provider',
            'ntresellerclub_tld_route',
            'ntresellerclub_customer',
            'ntresellerclub_provider_customer',
            'ntresellerclub_contact_profile',
            'ntresellerclub_contact',
            'ntresellerclub_service',
            'ntresellerclub_hosting_product_mapping',
            'ntresellerclub_ssl_product_mapping',
            'ntresellerclub_cart_domain',
            'ntresellerclub_price',
            'ntresellerclub_price_history',
            'ntresellerclub_exchange_rate_history',
            'ntresellerclub_billing_event',
            'ntresellerclub_operation_queue',
            'ntresellerclub_provider_health',
            'ntresellerclub_runtime_health',
            'ntresellerclub_provider_statistics',
            'ntresellerclub_notification_template',
            'ntresellerclub_notification_queue',
            'ntresellerclub_notification_log',
            'ntresellerclub_notice',
            'ntresellerclub_log',
            'ntresellerclub_license'
        );

        foreach ($tables as $table) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . pSQL($table) . '`');
        }

        return true;
    }
}
