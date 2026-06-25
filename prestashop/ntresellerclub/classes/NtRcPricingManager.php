<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';
require_once __DIR__ . '/NtRcPricingEngine.php';
require_once __DIR__ . '/NtRcHistoryManager.php';

class NtRcPricingManager
{
    public static function productTypes()
    {
        return array('domain', 'tr_domain', 'hosting', 'ssl');
    }

    public static function defaultResellerClubMappings()
    {
        return array(
            array('product_type' => 'domain', 'code' => 'com:register'),
            array('product_type' => 'domain', 'code' => 'com:transfer'),
            array('product_type' => 'domain', 'code' => 'com:renew'),
            array('product_type' => 'domain', 'code' => 'net:register'),
            array('product_type' => 'domain', 'code' => 'net:transfer'),
            array('product_type' => 'domain', 'code' => 'net:renew'),
            array('product_type' => 'domain', 'code' => 'org:register'),
            array('product_type' => 'domain', 'code' => 'org:transfer'),
            array('product_type' => 'domain', 'code' => 'org:renew'),
            array('product_type' => 'hosting', 'code' => 'hosting:default:create'),
            array('product_type' => 'hosting', 'code' => 'hosting:default:renew'),
            array('product_type' => 'ssl', 'code' => 'ssl:default:create'),
            array('product_type' => 'ssl', 'code' => 'ssl:default:renew'),
        );
    }

    public static function seedResellerClubMappings($currency = 'USD')
    {
        $count = 0;
        foreach (self::defaultResellerClubMappings() as $row) {
            if (self::upsertPrice('resellerclub', $row['product_type'], $row['code'], 1, $currency, 0, array('source' => 'resellerclub_seed'))) {
                $count++;
            }
        }
        return $count;
    }

    public static function upsertPrice($providerCode, $productType, $code, $years, $currency, $costPrice, array $rules = array())
    {
        NtRcInstaller::ensurePricingSchema();
        $providerCode = self::normalizeProvider($providerCode);
        $productType = self::normalizeProductType($productType);
        $code = self::normalizeCode($code);
        $years = max(1, (int)$years);
        $currency = self::normalizeCurrency($currency ?: 'USD');
        $existing = self::getRow($providerCode, $productType, $code, $years);
        $now = date('Y-m-d H:i:s');

        $data = array(
            'provider_code' => pSQL($providerCode),
            'product_type' => pSQL($productType),
            'code' => pSQL($code),
            'years' => $years,
            'cost_price' => (float)$costPrice,
            'currency' => pSQL($currency),
            'target_currency' => pSQL(isset($rules['target_currency']) ? self::normalizeCurrency($rules['target_currency']) : NtRcManualExchangeRate::defaultTargetCurrency()),
            'last_sync' => $now,
            'updated_at' => $now,
        );

        foreach (self::pricingColumnsFromRules($rules) as $column => $value) {
            $data[$column] = $value;
        }

        if ($existing) {
            self::recordPriceHistory($existing, $data, isset($rules['source']) ? $rules['source'] : 'pricing_upsert');
            return Db::getInstance()->update('ntresellerclub_price', $data, 'id_ntresellerclub_price=' . (int)$existing['id_ntresellerclub_price']);
        }

        $data['sale_price'] = isset($rules['sale_price']) ? (float)$rules['sale_price'] : 0;
        if (!isset($data['margin_mode'])) {
            $data['margin_mode'] = pSQL('manual');
        }
        if (!isset($data['margin_percent'])) {
            $data['margin_percent'] = 0;
        }
        if (!isset($data['margin_fixed'])) {
            $data['margin_fixed'] = 0;
        }
        if (!isset($data['tax_included'])) {
            $data['tax_included'] = 1;
        }
        if (!isset($data['tax_rate'])) {
            $data['tax_rate'] = NtRcPricingEngine::taxRate($data['target_currency']);
        }
        if (!isset($data['rounding_mode'])) {
            $data['rounding_mode'] = pSQL('no_round');
        }
        $data['created_at'] = $now;

        $ok = Db::getInstance()->insert('ntresellerclub_price', $data);
        if ($ok) {
            NtRcHistoryManager::addPriceChange($providerCode, $productType, $code, null, (float)$costPrice, null, (float)$data['sale_price'], $currency, isset($rules['source']) ? $rules['source'] : 'pricing_insert');
        }
        return $ok;
    }

    public static function setSaleRules($providerCode, $productType, $code, $years, array $rules)
    {
        NtRcInstaller::ensurePricingSchema();
        $providerCode = self::normalizeProvider($providerCode);
        $productType = self::normalizeProductType($productType);
        $code = self::normalizeCode($code);
        $years = max(1, (int)$years);
        $existing = self::getRow($providerCode, $productType, $code, $years);
        if (!$existing) {
            $currency = isset($rules['currency']) ? $rules['currency'] : 'USD';
            self::upsertPrice($providerCode, $productType, $code, $years, $currency, 0, $rules);
            $existing = self::getRow($providerCode, $productType, $code, $years);
            if (!$existing) {
                return false;
            }
        }

        $data = self::pricingColumnsFromRules($rules);
        $data['updated_at'] = date('Y-m-d H:i:s');
        if (isset($rules['target_currency'])) {
            $data['target_currency'] = pSQL(self::normalizeCurrency($rules['target_currency']));
        }

        self::recordPriceHistory($existing, $data, isset($rules['source']) ? $rules['source'] : 'pricing_rules');
        return Db::getInstance()->update('ntresellerclub_price', $data, 'id_ntresellerclub_price=' . (int)$existing['id_ntresellerclub_price']);
    }

    public static function calculateRow(array $row, $targetCurrency = null, array $options = array())
    {
        return NtRcPricingEngine::calculate($row, $targetCurrency, $options);
    }

    public static function getRow($providerCode, $productType, $code, $years = 1)
    {
        NtRcInstaller::ensurePricingSchema();
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_price` '
            . 'WHERE provider_code="' . pSQL(self::normalizeProvider($providerCode)) . '" '
            . 'AND product_type="' . pSQL(self::normalizeProductType($productType)) . '" '
            . 'AND code="' . pSQL(self::normalizeCode($code)) . '" '
            . 'AND years=' . (int)max(1, (int)$years)
        );
    }

    public static function getByProviderProduct($providerCode, $productType)
    {
        NtRcInstaller::ensurePricingSchema();
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_price` '
            . 'WHERE provider_code="' . pSQL(self::normalizeProvider($providerCode)) . '" '
            . 'AND product_type="' . pSQL(self::normalizeProductType($productType)) . '" '
            . 'ORDER BY code ASC, years ASC'
        );
    }

    public static function all()
    {
        NtRcInstaller::ensurePricingSchema();
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_price` ORDER BY provider_code ASC, product_type ASC, code ASC, years ASC'
        );
    }

    protected static function pricingColumnsFromRules(array $rules)
    {
        $data = array();
        if (isset($rules['sale_price'])) {
            $data['sale_price'] = (float)$rules['sale_price'];
        }
        if (isset($rules['margin_mode'])) {
            $data['margin_mode'] = pSQL(NtRcPricingEngine::normalizeMarginMode($rules['margin_mode']));
        }
        if (isset($rules['margin_percent'])) {
            $data['margin_percent'] = (float)$rules['margin_percent'];
        }
        if (isset($rules['margin_fixed'])) {
            $data['margin_fixed'] = (float)$rules['margin_fixed'];
        }
        if (isset($rules['tax_included'])) {
            $data['tax_included'] = (int)$rules['tax_included'] ? 1 : 0;
        }
        if (isset($rules['tax_rate'])) {
            $data['tax_rate'] = max(0.0, (float)$rules['tax_rate']);
        }
        if (isset($rules['rounding_mode'])) {
            $data['rounding_mode'] = pSQL(NtRcPricingEngine::normalizeRoundingMode($rules['rounding_mode']));
        }
        return $data;
    }

    protected static function recordPriceHistory(array $existing, array $data, $source)
    {
        $oldCost = isset($existing['cost_price']) ? (float)$existing['cost_price'] : null;
        $newCost = array_key_exists('cost_price', $data) ? (float)$data['cost_price'] : $oldCost;
        $oldSale = isset($existing['sale_price']) ? (float)$existing['sale_price'] : null;
        $newSale = array_key_exists('sale_price', $data) ? (float)$data['sale_price'] : $oldSale;

        if ($oldCost !== $newCost || $oldSale !== $newSale) {
            NtRcHistoryManager::addPriceChange(
                $existing['provider_code'],
                $existing['product_type'],
                $existing['code'],
                $oldCost,
                $newCost,
                $oldSale,
                $newSale,
                isset($data['currency']) ? $data['currency'] : $existing['currency'],
                $source
            );
        }
    }

    protected static function normalizeProvider($providerCode)
    {
        return strtolower(trim((string)$providerCode));
    }

    protected static function normalizeProductType($productType)
    {
        $productType = strtolower(trim((string)$productType));
        return in_array($productType, self::productTypes(), true) ? $productType : 'domain';
    }

    protected static function normalizeCode($code)
    {
        return strtolower(trim((string)$code));
    }

    protected static function normalizeCurrency($currency)
    {
        return strtoupper(substr(trim((string)$currency), 0, 10));
    }
}
