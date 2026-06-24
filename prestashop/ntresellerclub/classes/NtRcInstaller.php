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
            && self::ensureContactProfileSchema();
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
            'ntresellerclub_cart_domain',
            'ntresellerclub_price',
            'ntresellerclub_price_history',
            'ntresellerclub_exchange_rate_history',
            'ntresellerclub_operation_queue',
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
