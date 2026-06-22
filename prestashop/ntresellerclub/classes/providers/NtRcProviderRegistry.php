<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcProviderRegistry
{
    public static function all($onlyEnabled = false)
    {
        $where = $onlyEnabled ? ' WHERE is_enabled=1' : '';
        return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider`' . $where . ' ORDER BY provider_name ASC');
    }

    public static function get($providerCode)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider` WHERE provider_code="' . pSQL($providerCode) . '"'
        );
    }

    public static function isUsable($providerCode)
    {
        $row = self::get($providerCode);
        return $row && (int)$row['is_enabled'] === 1 && (int)$row['is_licensed'] === 1;
    }

    public static function save($providerCode, $providerName, $providerType, $enabled, $licensed, $version = '1.0.0', $configJson = null)
    {
        $exists = self::get($providerCode);
        $data = array(
            'provider_code' => pSQL($providerCode),
            'provider_name' => pSQL($providerName),
            'provider_type' => pSQL($providerType),
            'is_enabled' => (int)$enabled,
            'is_licensed' => (int)$licensed,
            'version' => pSQL($version),
            'config_json' => $configJson ? pSQL($configJson) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        if ($exists) {
            return Db::getInstance()->update('ntresellerclub_provider', $data, 'provider_code="' . pSQL($providerCode) . '"');
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        return Db::getInstance()->insert('ntresellerclub_provider', $data);
    }
}
