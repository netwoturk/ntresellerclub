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
            'id_product' => !empty($options['id_product']) ? (int)$options['id_product'] : 0,
            'domain_name' => pSQL($domainName),
            'tld' => !empty($options['tld']) ? pSQL($options['tld']) : null,
            'provider_code' => $providerCode ? pSQL($providerCode) : null,
            'service_type' => !empty($options['service_type']) ? pSQL($options['service_type']) : null,
            'years' => (int)$years,
            'price_snapshot' => isset($options['price_snapshot']) ? (float)$options['price_snapshot'] : null,
            'currency' => !empty($options['currency']) ? pSQL($options['currency']) : null,
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
