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

        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_cart_domain` WHERE id_cart=' . (int)$idCart
        );

        $normalized = array();
        foreach ((array)$rows as $row) {
            $normalized[] = self::normalizeRow($row);
        }

        return $normalized;
    }

    public static function normalizeRow(array $row)
    {
        $domainName = isset($row['domain_name']) ? strtolower(trim((string)$row['domain_name'])) : '';
        $providerCode = isset($row['provider_code']) ? strtolower(trim((string)$row['provider_code'])) : '';
        $serviceType = isset($row['service_type']) ? strtolower(trim((string)$row['service_type'])) : '';

        if ($serviceType === '' && $providerCode !== '') {
            $serviceType = $providerCode === 'domainnameapi' ? 'tr_domain' : 'domain';
        }

        $row['id_cart'] = isset($row['id_cart']) ? (int)$row['id_cart'] : 0;
        $row['id_product'] = isset($row['id_product']) ? (int)$row['id_product'] : 0;
        $row['domain_name'] = $domainName;
        $row['tld'] = isset($row['tld']) ? strtolower(trim((string)$row['tld'])) : '';
        $row['provider_code'] = $providerCode;
        $row['service_type'] = $serviceType;
        $row['years'] = isset($row['years']) ? max(1, (int)$row['years']) : 1;
        $row['price_snapshot'] = isset($row['price_snapshot']) && $row['price_snapshot'] !== null && $row['price_snapshot'] !== '' ? (float)$row['price_snapshot'] : null;
        $row['currency'] = isset($row['currency']) ? strtoupper(trim((string)$row['currency'])) : '';

        return $row;
    }
}
