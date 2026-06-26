<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

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
        $idProduct = isset($row['id_product']) ? (int)$row['id_product'] : 0;
        $providerProductId = isset($row['provider_product_id']) ? trim((string)$row['provider_product_id']) : '';
        $billingCycle = isset($row['billing_cycle']) ? self::normalizeBillingCycle($row['billing_cycle']) : 'yearly';

        if ($idProduct <= 0 || $providerProductId === '') {
            return array('success' => false, 'message' => 'SSL mapping icin product id ve provider product id zorunludur.');
        }

        $existing = self::getByProductId($idProduct, false);
        $now = date('Y-m-d H:i:s');
        $data = array(
            'id_product' => $idProduct,
            'provider_code' => pSQL(self::PROVIDER_CODE),
            'provider_product_id' => pSQL($providerProductId),
            'billing_cycle' => pSQL($billingCycle),
            'currency' => pSQL(self::normalizeCurrency(isset($row['currency']) ? $row['currency'] : 'USD')),
            'active' => isset($row['active']) ? ((int)$row['active'] ? 1 : 0) : 1,
            'updated_at' => $now,
        );

        if ($existing) {
            $ok = Db::getInstance()->update(
                'ntresellerclub_ssl_product_mapping',
                $data,
                'id_ntresellerclub_ssl_product_mapping=' . (int)$existing['id_ntresellerclub_ssl_product_mapping']
            );
            return array('success' => (bool)$ok, 'mapping_id' => (int)$existing['id_ntresellerclub_ssl_product_mapping']);
        }

        $data['created_at'] = $now;
        $ok = Db::getInstance()->insert('ntresellerclub_ssl_product_mapping', $data);

        return array('success' => (bool)$ok, 'mapping_id' => $ok ? (int)Db::getInstance()->Insert_ID() : 0);
    }

    public static function payloadFromMapping(array $mapping, array $extra = array())
    {
        return array(
            'provider_product_id' => isset($mapping['provider_product_id']) ? $mapping['provider_product_id'] : '',
            'billing_cycle' => isset($mapping['billing_cycle']) ? $mapping['billing_cycle'] : 'yearly',
            'currency' => isset($mapping['currency']) ? $mapping['currency'] : 'USD',
            'extra' => $extra,
        );
    }

    public static function normalizeBillingCycle($billingCycle)
    {
        $billingCycle = strtolower(trim((string)$billingCycle));
        $allowed = array('yearly', 'biennial', 'triennial');
        return in_array($billingCycle, $allowed, true) ? $billingCycle : 'yearly';
    }

    protected static function normalizeCurrency($currency)
    {
        $currency = strtoupper(substr(trim((string)$currency), 0, 10));
        return $currency !== '' ? $currency : 'USD';
    }
}
