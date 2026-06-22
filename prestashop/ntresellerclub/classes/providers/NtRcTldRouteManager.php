<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcTldRouteManager
{
    public static function resolve($tld)
    {
        $tld = strtolower(ltrim(trim($tld), '.'));
        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_tld_route` WHERE tld="' . pSQL($tld) . '" AND is_enabled=1 ORDER BY priority ASC'
        );
        return $row ? $row['provider_code'] : null;
    }

    public static function resolveDomain($domainName)
    {
        $domainName = strtolower(trim($domainName));
        $parts = explode('.', $domainName);
        if (count($parts) < 2) {
            return null;
        }

        $lastTwo = implode('.', array_slice($parts, -2));
        $lastOne = end($parts);

        $provider = self::resolve($lastTwo);
        if ($provider) {
            return $provider;
        }

        return self::resolve($lastOne);
    }

    public static function all()
    {
        return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_tld_route` ORDER BY tld ASC, priority ASC');
    }

    public static function save($tld, $providerCode, $enabled = 1, $priority = 10)
    {
        $tld = strtolower(ltrim(trim($tld), '.'));
        $exists = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_tld_route` WHERE tld="' . pSQL($tld) . '" AND provider_code="' . pSQL($providerCode) . '"'
        );

        $data = array(
            'tld' => pSQL($tld),
            'provider_code' => pSQL($providerCode),
            'is_enabled' => (int)$enabled,
            'priority' => (int)$priority,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        if ($exists) {
            return Db::getInstance()->update('ntresellerclub_tld_route', $data, 'id_ntresellerclub_tld_route=' . (int)$exists['id_ntresellerclub_tld_route']);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        return Db::getInstance()->insert('ntresellerclub_tld_route', $data);
    }
}
