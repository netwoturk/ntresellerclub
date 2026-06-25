<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';

class NtRcCartDomain
{
    public static function rememberDomain($idCart, $domainName, $years = 1, $providerCode = null, array $options = array())
    {
        NtRcInstaller::ensureCartDomainSchema();

        if (!$idCart || !$domainName) {
            return false;
        }

        $data = array(
            'id_cart' => (int)$idCart,
            'domain_name' => pSQL($domainName),
            'provider_code' => $providerCode ? pSQL($providerCode) : null,
            'years' => (int)$years,
            'options_json' => !empty($options) ? pSQL(json_encode($options)) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        return Db::getInstance()->insert('ntresellerclub_cart_domain', $data);
    }

    public static function getDomainsByCart($idCart)
    {
        NtRcInstaller::ensureCartDomainSchema();

        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_cart_domain` WHERE id_cart=' . (int)$idCart
        );
    }
}