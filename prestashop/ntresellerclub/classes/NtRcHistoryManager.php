<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcHistoryManager
{
    public static function addPriceChange($providerCode, $productType, $code, $oldCost, $newCost, $oldSale, $newSale, $currency, $source = 'system')
    {
        return Db::getInstance()->insert('ntresellerclub_price_history', array(
            'provider_code' => pSQL($providerCode),
            'product_type' => pSQL($productType),
            'code' => pSQL($code),
            'old_cost_price' => $oldCost !== null ? (float)$oldCost : null,
            'new_cost_price' => $newCost !== null ? (float)$newCost : null,
            'old_sale_price' => $oldSale !== null ? (float)$oldSale : null,
            'new_sale_price' => $newSale !== null ? (float)$newSale : null,
            'currency' => pSQL($currency),
            'change_source' => pSQL($source),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    public static function addExchangeRateChange($fromCurrency, $toCurrency, $oldRate, $newRate, $source = 'admin')
    {
        return Db::getInstance()->insert('ntresellerclub_exchange_rate_history', array(
            'from_currency' => pSQL(strtoupper($fromCurrency)),
            'to_currency' => pSQL(strtoupper($toCurrency)),
            'old_rate' => $oldRate !== null ? (float)$oldRate : null,
            'new_rate' => (float)$newRate,
            'change_source' => pSQL($source),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    public static function lastPriceChanges($limit = 10)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_price_history` ORDER BY id_ntresellerclub_price_history DESC LIMIT ' . (int)$limit
        );
    }

    public static function lastExchangeRateChanges($limit = 10)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_exchange_rate_history` ORDER BY id_ntresellerclub_exchange_rate_history DESC LIMIT ' . (int)$limit
        );
    }
}
