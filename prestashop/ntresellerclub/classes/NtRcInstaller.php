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

        return self::ensureOperationQueueSchema();
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
