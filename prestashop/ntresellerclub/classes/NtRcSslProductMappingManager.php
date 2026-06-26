<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcPricingManager.php';

class NtRcSslProductMappingManager
{
    const PROVIDER_CODE = 'resellerclub';

    public static function getByProductId($idProduct, $activeOnly = true)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_ssl_product_mapping` '
            . 'WHERE id_product=' . (int)$idProduct . ' '
            . 'AND provider_code="' . pSQL(self::PROVIDER_CODE) . '"';
        if ($activeOnly) {
            $sql .= ' AND active=1';
        }
        $sql .= ' ORDER BY id_ntresellerclub_ssl_product_mapping DESC';

        return Db::getInstance()->getRow($sql);
    }

    public static function getById($idMapping)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_ssl_product_mapping` '
            . 'WHERE id_ntresellerclub_ssl_product_mapping=' . (int)$idMapping
        );
    }

    public static function all($activeOnly = false)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_ssl_product_mapping`';
        if ($activeOnly) {
            $sql .= ' WHERE active=1';
        }
        $sql .= ' ORDER BY provider_code ASC, provider_product_id ASC, billing_cycle ASC';

        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    public static function upsert(array $row)
    {
        $idMapping = isset($row['id_ntresellerclub_ssl_product_mapping']) ? (int)$row['id_ntresellerclub_ssl_product_mapping'] : (isset($row['id_mapping']) ? (int)$row['id_mapping'] : 0);
        $idProduct = isset($row['id_product']) ? (int)$row['id_product'] : 0;
        $providerProductId = isset($row['provider_product_id']) ? trim((string)$row['provider_product_id']) : '';
        $sslProductType = isset($row['ssl_product_type']) ? self::normalizeSslProductType($row['ssl_product_type']) : 'standard';
        $billingCycle = isset($row['billing_cycle']) ? self::normalizeBillingCycle($row['billing_cycle']) : 'yearly';
        $currency = self::normalizeCurrency(isset($row['currency']) ? $row['currency'] : 'USD');

        if ($idProduct <= 0 || $providerProductId === '') {
            return array('success' => false, 'message' => 'SSL mapping icin product id ve provider product id zorunludur.');
        }
        if (class_exists('Validate') && !Validate::isUnsignedId($idProduct)) {
            return array('success' => false, 'message' => 'SSL mapping product id gecersiz.');
        }
        if (class_exists('Validate') && (!Validate::isPrice(isset($row['cost_price']) ? $row['cost_price'] : 0) || !Validate::isPrice(isset($row['sale_price']) ? $row['sale_price'] : 0))) {
            return array('success' => false, 'message' => 'SSL mapping fiyat degeri gecersiz.');
        }

        $existing = $idMapping > 0 ? self::getById($idMapping) : self::getByProductId($idProduct, false);
        $now = date('Y-m-d H:i:s');
        $data = array(
            'id_product' => $idProduct,
            'provider_code' => pSQL(self::PROVIDER_CODE),
            'provider_product_id' => pSQL($providerProductId),
            'ssl_product_type' => pSQL($sslProductType),
            'billing_cycle' => pSQL($billingCycle),
            'cost_price' => isset($row['cost_price']) ? (float)$row['cost_price'] : 0,
            'sale_price' => isset($row['sale_price']) ? (float)$row['sale_price'] : 0,
            'currency' => pSQL($currency),
            'active' => isset($row['active']) ? ((int)$row['active'] ? 1 : 0) : 1,
            'updated_at' => $now,
        );

        if ($existing) {
            $ok = Db::getInstance()->update(
                'ntresellerclub_ssl_product_mapping',
                $data,
                'id_ntresellerclub_ssl_product_mapping=' . (int)$existing['id_ntresellerclub_ssl_product_mapping']
            );
            if ($ok) {
                self::syncPricingRow($data);
            }
            return array('success' => (bool)$ok, 'mapping_id' => (int)$existing['id_ntresellerclub_ssl_product_mapping']);
        }

        $data['created_at'] = $now;
        $ok = Db::getInstance()->insert('ntresellerclub_ssl_product_mapping', $data);
        if ($ok) {
            self::syncPricingRow($data);
        }

        return array('success' => (bool)$ok, 'mapping_id' => $ok ? (int)Db::getInstance()->Insert_ID() : 0);
    }

    public static function toggle($idMapping, $active)
    {
        $mapping = self::getById($idMapping);
        if (!$mapping) {
            return array('success' => false, 'message' => 'SSL mapping bulunamadi.');
        }

        $ok = Db::getInstance()->update(
            'ntresellerclub_ssl_product_mapping',
            array('active' => (int)$active ? 1 : 0, 'updated_at' => date('Y-m-d H:i:s')),
            'id_ntresellerclub_ssl_product_mapping=' . (int)$idMapping
        );

        return array('success' => (bool)$ok, 'mapping_id' => (int)$idMapping);
    }

    public static function payloadFromMapping(array $mapping, array $extra = array())
    {
        return array(
            'provider_product_id' => isset($mapping['provider_product_id']) ? $mapping['provider_product_id'] : '',
            'ssl_product_type' => isset($mapping['ssl_product_type']) ? $mapping['ssl_product_type'] : 'standard',
            'billing_cycle' => isset($mapping['billing_cycle']) ? $mapping['billing_cycle'] : 'yearly',
            'currency' => isset($mapping['currency']) ? $mapping['currency'] : 'USD',
            'months' => self::monthsFromBillingCycle(isset($mapping['billing_cycle']) ? $mapping['billing_cycle'] : 'yearly'),
            'plan-id' => isset($mapping['provider_product_id']) ? $mapping['provider_product_id'] : '',
            'extra' => $extra,
        );
    }

    public static function normalizeBillingCycle($billingCycle)
    {
        $billingCycle = strtolower(trim((string)$billingCycle));
        $allowed = array('yearly', 'biennial', 'triennial');
        return in_array($billingCycle, $allowed, true) ? $billingCycle : 'yearly';
    }

    public static function monthsFromBillingCycle($billingCycle)
    {
        $map = array('yearly' => 12, 'biennial' => 24, 'triennial' => 36);
        $billingCycle = self::normalizeBillingCycle($billingCycle);
        return isset($map[$billingCycle]) ? $map[$billingCycle] : 12;
    }

    public static function normalizeSslProductType($type)
    {
        $type = strtolower(trim((string)$type));
        $allowed = array('standard', 'premium', 'wildcard', 'ev', 'positive_ssl', 'positive_wildcard');
        return in_array($type, $allowed, true) ? $type : 'standard';
    }

    protected static function syncPricingRow(array $data)
    {
        $cycle = isset($data['billing_cycle']) ? $data['billing_cycle'] : 'yearly';
        $years = max(1, (int)(self::monthsFromBillingCycle($cycle) / 12));
        $code = 'ssl:' . (isset($data['provider_product_id']) ? $data['provider_product_id'] : 'default') . ':' . $cycle;
        NtRcPricingManager::upsertPrice(self::PROVIDER_CODE, 'ssl', $code, $years, isset($data['currency']) ? $data['currency'] : 'USD', isset($data['cost_price']) ? (float)$data['cost_price'] : 0, array(
            'sale_price' => isset($data['sale_price']) ? (float)$data['sale_price'] : 0,
            'source' => 'ssl_mapping_backend',
        ));
    }

    protected static function normalizeCurrency($currency)
    {
        $currency = strtoupper(substr(trim((string)$currency), 0, 10));
        return $currency !== '' ? $currency : 'USD';
    }
}
