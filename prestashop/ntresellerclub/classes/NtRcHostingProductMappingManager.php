<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';

class NtRcHostingProductMappingManager
{
    const PROVIDER_CODE = 'resellerclub';

    public static function ensureSchema()
    {
        return NtRcInstaller::ensureHostingProductMappingSchema();
    }

    public static function getByProductId($idProduct, $activeOnly = true)
    {
        self::ensureSchema();
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_hosting_product_mapping` '
            . 'WHERE id_product=' . (int)$idProduct . ' '
            . 'AND provider_code="' . pSQL(self::PROVIDER_CODE) . '"';
        if ($activeOnly) {
            $sql .= ' AND active=1';
        }
        $sql .= ' ORDER BY id_ntresellerclub_hosting_product_mapping DESC';

        return Db::getInstance()->getRow($sql);
    }

    public static function getById($idMapping)
    {
        self::ensureSchema();
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_hosting_product_mapping` '
            . 'WHERE id_ntresellerclub_hosting_product_mapping=' . (int)$idMapping
        );
    }

    public static function all($activeOnly = false)
    {
        self::ensureSchema();
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_hosting_product_mapping`';
        if ($activeOnly) {
            $sql .= ' WHERE active=1';
        }
        $sql .= ' ORDER BY provider_code ASC, package_name ASC, billing_cycle ASC';

        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    public static function upsert(array $row)
    {
        self::ensureSchema();

        $idProduct = isset($row['id_product']) ? (int)$row['id_product'] : 0;
        $providerProductId = isset($row['provider_product_id']) ? trim((string)$row['provider_product_id']) : '';
        $packageName = isset($row['package_name']) ? trim((string)$row['package_name']) : '';
        $billingCycle = isset($row['billing_cycle']) ? self::normalizeBillingCycle($row['billing_cycle']) : 'yearly';

        if ($idProduct <= 0 || $providerProductId === '' || $packageName === '') {
            return array('success' => false, 'message' => 'Hosting mapping icin product id, provider product id ve package name zorunludur.');
        }

        $existing = self::getByProductId($idProduct, false);
        $now = date('Y-m-d H:i:s');
        $data = array(
            'id_product' => $idProduct,
            'provider_code' => pSQL(self::PROVIDER_CODE),
            'provider_product_id' => pSQL($providerProductId),
            'package_name' => pSQL($packageName),
            'billing_cycle' => pSQL($billingCycle),
            'cost_price' => isset($row['cost_price']) ? (float)$row['cost_price'] : 0,
            'sale_price' => isset($row['sale_price']) ? (float)$row['sale_price'] : 0,
            'currency' => pSQL(self::normalizeCurrency(isset($row['currency']) ? $row['currency'] : 'USD')),
            'active' => isset($row['active']) ? ((int)$row['active'] ? 1 : 0) : 1,
            'updated_at' => $now,
        );

        if ($existing) {
            $ok = Db::getInstance()->update(
                'ntresellerclub_hosting_product_mapping',
                $data,
                'id_ntresellerclub_hosting_product_mapping=' . (int)$existing['id_ntresellerclub_hosting_product_mapping']
            );
            return array('success' => (bool)$ok, 'mapping_id' => (int)$existing['id_ntresellerclub_hosting_product_mapping']);
        }

        $data['created_at'] = $now;
        $ok = Db::getInstance()->insert('ntresellerclub_hosting_product_mapping', $data);

        return array('success' => (bool)$ok, 'mapping_id' => $ok ? (int)Db::getInstance()->Insert_ID() : 0);
    }

    public static function payloadFromMapping(array $mapping, array $extra = array())
    {
        return array(
            'provider_product_id' => isset($mapping['provider_product_id']) ? $mapping['provider_product_id'] : '',
            'package_name' => isset($mapping['package_name']) ? $mapping['package_name'] : '',
            'billing_cycle' => isset($mapping['billing_cycle']) ? $mapping['billing_cycle'] : 'yearly',
            'cost_price' => isset($mapping['cost_price']) ? (float)$mapping['cost_price'] : 0,
            'sale_price' => isset($mapping['sale_price']) ? (float)$mapping['sale_price'] : 0,
            'currency' => isset($mapping['currency']) ? $mapping['currency'] : 'USD',
            'extra' => $extra,
        );
    }

    public static function normalizeBillingCycle($billingCycle)
    {
        $billingCycle = strtolower(trim((string)$billingCycle));
        $allowed = array('monthly', 'quarterly', 'semiannual', 'yearly', 'biennial', 'triennial');
        return in_array($billingCycle, $allowed, true) ? $billingCycle : 'yearly';
    }

    protected static function normalizeCurrency($currency)
    {
        $currency = strtoupper(substr(trim((string)$currency), 0, 10));
        return $currency !== '' ? $currency : 'USD';
    }
}
