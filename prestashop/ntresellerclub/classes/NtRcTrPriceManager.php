<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcTrPriceManager
{
    const PROVIDER = 'domainnameapi';

    public static function allowedTlds()
    {
        return array('tr', 'com.tr', 'net.tr', 'org.tr', 'av.tr', 'gen.tr', 'web.tr');
    }

    public static function isAllowedTld($tld)
    {
        return in_array(strtolower(ltrim($tld, '.')), self::allowedTlds());
    }

    public static function upsertCost($tld, $currency, array $costs)
    {
        $tld = strtolower(ltrim(trim($tld), '.'));
        if (!self::isAllowedTld($tld)) {
            return false;
        }

        foreach ($costs as $operation => $cost) {
            self::upsertRow($tld, $operation, $currency, (float)$cost);
        }
        return true;
    }

    public static function setSalePrice($tld, $operation, $salePrice, $mode = 'manual', $marginPercent = 0, $marginFixed = 0)
    {
        $tld = strtolower(ltrim(trim($tld), '.'));
        $operation = strtolower(trim($operation));
        if (!self::isAllowedTld($tld)) {
            return false;
        }

        $code = $tld . ':' . $operation;
        $exists = self::getRow($code);
        $data = array(
            'provider_code' => pSQL(self::PROVIDER),
            'product_type' => pSQL('tr_domain'),
            'code' => pSQL($code),
            'years' => 1,
            'sale_price' => (float)$salePrice,
            'margin_mode' => pSQL($mode),
            'margin_percent' => (float)$marginPercent,
            'margin_fixed' => (float)$marginFixed,
            'last_sync' => date('Y-m-d H:i:s'),
        );

        if ($exists) {
            return Db::getInstance()->update('ntresellerclub_price', $data, 'id_ntresellerclub_price=' . (int)$exists['id_ntresellerclub_price']);
        }

        $data['cost_price'] = 0;
        $data['currency'] = pSQL(Configuration::get('PS_CURRENCY_DEFAULT'));
        return Db::getInstance()->insert('ntresellerclub_price', $data);
    }

    public static function getByTld($tld)
    {
        $tld = strtolower(ltrim(trim($tld), '.'));
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_price` WHERE provider_code="' . pSQL(self::PROVIDER) . '" AND product_type="tr_domain" AND code LIKE "' . pSQL($tld) . ':%" ORDER BY code ASC'
        );
    }

    public static function all()
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_price` WHERE provider_code="' . pSQL(self::PROVIDER) . '" AND product_type="tr_domain" ORDER BY code ASC'
        );
    }

    protected static function upsertRow($tld, $operation, $currency, $costPrice)
    {
        $operation = strtolower(trim($operation));
        $code = $tld . ':' . $operation;
        $exists = self::getRow($code);
        $data = array(
            'provider_code' => pSQL(self::PROVIDER),
            'product_type' => pSQL('tr_domain'),
            'code' => pSQL($code),
            'years' => 1,
            'cost_price' => (float)$costPrice,
            'currency' => pSQL($currency),
            'last_sync' => date('Y-m-d H:i:s'),
        );

        if ($exists) {
            return Db::getInstance()->update('ntresellerclub_price', $data, 'id_ntresellerclub_price=' . (int)$exists['id_ntresellerclub_price']);
        }

        $data['sale_price'] = 0;
        $data['margin_mode'] = pSQL('manual');
        $data['margin_percent'] = 0;
        $data['margin_fixed'] = 0;
        return Db::getInstance()->insert('ntresellerclub_price', $data);
    }

    protected static function getRow($code)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_price` WHERE provider_code="' . pSQL(self::PROVIDER) . '" AND product_type="tr_domain" AND code="' . pSQL($code) . '"'
        );
    }
}
