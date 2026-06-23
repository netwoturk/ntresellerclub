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

        return true;
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
