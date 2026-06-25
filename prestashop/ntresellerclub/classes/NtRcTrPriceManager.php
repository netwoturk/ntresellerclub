<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcPricingManager.php';

class NtRcTrPriceManager
{
    const PROVIDER = 'domainnameapi';
    const PRODUCT_TYPE = 'tr_domain';

    public static function allowedTlds()
    {
        return array('tr', 'com.tr', 'net.tr', 'org.tr', 'av.tr', 'gen.tr', 'web.tr');
    }

    public static function isAllowedTld($tld)
    {
        return in_array(strtolower(ltrim($tld, '.')), self::allowedTlds(), true);
    }

    public static function upsertCost($tld, $currency, array $costs, array $rules = array())
    {
        $tld = strtolower(ltrim(trim($tld), '.'));
        if (!self::isAllowedTld($tld)) {
            return false;
        }

        foreach ($costs as $operation => $cost) {
            self::upsertRow($tld, $operation, $currency, (float)$cost, $rules);
        }
        return true;
    }

    public static function setSalePrice($tld, $operation, $salePrice, $mode = 'manual', $marginPercent = 0, $marginFixed = 0, $taxIncluded = null, $taxRate = null, $roundingMode = null, $targetCurrency = null)
    {
        $tld = strtolower(ltrim(trim($tld), '.'));
        $operation = strtolower(trim($operation));
        if (!self::isAllowedTld($tld)) {
            return false;
        }

        $rules = array(
            'sale_price' => (float)$salePrice,
            'margin_mode' => $mode,
            'margin_percent' => (float)$marginPercent,
            'margin_fixed' => (float)$marginFixed,
            'source' => 'admin_tr_price',
        );

        if ($taxIncluded !== null) {
            $rules['tax_included'] = (int)$taxIncluded;
        }
        if ($taxRate !== null) {
            $rules['tax_rate'] = (float)$taxRate;
        }
        if ($roundingMode !== null) {
            $rules['rounding_mode'] = $roundingMode;
        }
        if ($targetCurrency !== null) {
            $rules['target_currency'] = $targetCurrency;
        }

        return NtRcPricingManager::setSaleRules(self::PROVIDER, self::PRODUCT_TYPE, self::code($tld, $operation), 1, $rules);
    }

    public static function getByTld($tld)
    {
        $tld = strtolower(ltrim(trim($tld), '.'));
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_price` WHERE provider_code="' . pSQL(self::PROVIDER) . '" AND product_type="' . pSQL(self::PRODUCT_TYPE) . '" AND code LIKE "' . pSQL($tld) . ':%" ORDER BY code ASC'
        );
    }

    public static function all()
    {
        return NtRcPricingManager::getByProviderProduct(self::PROVIDER, self::PRODUCT_TYPE);
    }

    protected static function upsertRow($tld, $operation, $currency, $costPrice, array $rules = array())
    {
        $operation = strtolower(trim($operation));
        $rules['source'] = isset($rules['source']) ? $rules['source'] : 'dna_sync';
        return NtRcPricingManager::upsertPrice(self::PROVIDER, self::PRODUCT_TYPE, self::code($tld, $operation), 1, $currency, (float)$costPrice, $rules);
    }

    protected static function code($tld, $operation)
    {
        return strtolower(ltrim(trim($tld), '.')) . ':' . strtolower(trim($operation));
    }
}
