<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcManualExchangeRate.php';

class NtRcPricingEngine
{
    const CFG_DEFAULT_TAX_RATE = 'NTRC_DEFAULT_TAX_RATE';
    const CFG_DEFAULT_TAX_INCLUDED = 'NTRC_DEFAULT_TAX_INCLUDED';
    const CFG_DEFAULT_ROUNDING_MODE = 'NTRC_DEFAULT_ROUNDING_MODE';
    const CFG_TAX_RATE_PREFIX = 'NTRC_TAX_RATE_';

    public static function marginModes()
    {
        return array('manual', 'percent', 'fixed', 'hybrid');
    }

    public static function roundingModes()
    {
        return array('no_round', 'nearest_1', 'nearest_5', 'nearest_10', 'psychological_99');
    }

    public static function calculate(array $row, $targetCurrency = null, array $options = array())
    {
        $costCurrency = !empty($row['currency']) ? strtoupper($row['currency']) : 'USD';
        $targetCurrency = $targetCurrency ? strtoupper($targetCurrency) : self::targetCurrencyFromRow($row);
        $costPrice = isset($row['cost_price']) ? (float)$row['cost_price'] : 0.0;

        return self::calculateFromValues($costPrice, $costCurrency, $targetCurrency, array_merge($row, $options));
    }

    public static function calculateFromValues($costPrice, $costCurrency, $targetCurrency, array $rules = array())
    {
        $costPrice = max(0.0, (float)$costPrice);
        $costCurrency = strtoupper(trim((string)$costCurrency));
        $targetCurrency = strtoupper(trim((string)$targetCurrency));
        if ($costCurrency === '') {
            $costCurrency = 'USD';
        }
        if ($targetCurrency === '') {
            $targetCurrency = NtRcManualExchangeRate::defaultTargetCurrency();
        }

        $convertedCost = NtRcManualExchangeRate::convert($costPrice, $costCurrency, $targetCurrency);
        if ($convertedCost === null) {
            return array(
                'success' => false,
                'message' => 'Kur tanimli degil.',
                'cost_price' => self::money($costPrice),
                'cost_currency' => $costCurrency,
                'target_currency' => $targetCurrency,
                'currency' => $targetCurrency,
            );
        }

        $mode = self::normalizeMarginMode(isset($rules['margin_mode']) ? $rules['margin_mode'] : 'manual');
        $saleInput = isset($rules['sale_price']) ? (float)$rules['sale_price'] : 0.0;
        $marginPercent = isset($rules['margin_percent']) ? (float)$rules['margin_percent'] : 0.0;
        $marginFixed = isset($rules['margin_fixed']) ? (float)$rules['margin_fixed'] : 0.0;
        $taxIncluded = self::boolRule($rules, 'tax_included', self::defaultTaxIncluded());
        $taxRate = self::taxRate($targetCurrency, isset($rules['country_iso']) ? $rules['country_iso'] : null, $rules);
        $roundingMode = self::normalizeRoundingMode(isset($rules['rounding_mode']) ? $rules['rounding_mode'] : Configuration::get(self::CFG_DEFAULT_ROUNDING_MODE));

        $saleWithoutTax = 0.0;
        $marginAmount = 0.0;

        if ($mode === 'manual') {
            if ($taxIncluded) {
                $saleWithTax = max(0.0, $saleInput);
                $saleWithoutTax = self::removeTax($saleWithTax, $taxRate);
            } else {
                $saleWithoutTax = max(0.0, $saleInput);
            }
            $marginAmount = $saleWithoutTax - $convertedCost;
        } elseif ($mode === 'percent') {
            $marginAmount = $convertedCost * ($marginPercent / 100);
            $saleWithoutTax = $convertedCost + $marginAmount;
        } elseif ($mode === 'fixed') {
            $marginAmount = $marginFixed;
            $saleWithoutTax = $convertedCost + $marginAmount;
        } elseif ($mode === 'hybrid') {
            $base = $convertedCost + $marginFixed;
            $percentAmount = $base * ($marginPercent / 100);
            $marginAmount = $marginFixed + $percentAmount;
            $saleWithoutTax = $convertedCost + $marginAmount;
        }

        $saleWithoutTax = max(0.0, $saleWithoutTax);
        $taxAmount = $saleWithoutTax * ($taxRate / 100);
        $saleWithTax = $saleWithoutTax + $taxAmount;
        $finalSalePrice = self::roundPrice($saleWithTax, $roundingMode);
        $exchangeRate = $costPrice > 0 ? $convertedCost / $costPrice : NtRcManualExchangeRate::getRate($costCurrency, $targetCurrency);

        return array(
            'success' => true,
            'cost_price' => self::money($costPrice),
            'cost_currency' => $costCurrency,
            'converted_cost' => self::money($convertedCost),
            'target_currency' => $targetCurrency,
            'margin_amount' => self::money($marginAmount),
            'tax_amount' => self::money($taxAmount),
            'sale_price_without_tax' => self::money($saleWithoutTax),
            'sale_price_with_tax' => self::money($saleWithTax),
            'rounding_mode' => $roundingMode,
            'final_sale_price' => self::money($finalSalePrice),
            'cost_source' => self::money($costPrice),
            'source_currency' => $costCurrency,
            'cost_converted' => self::money($convertedCost),
            'sale_price' => self::money($finalSalePrice),
            'currency' => $targetCurrency,
            'mode' => $mode,
            'margin_mode' => $mode,
            'margin_percent' => $marginPercent,
            'margin_fixed' => self::money($marginFixed),
            'tax_rate' => self::rate($taxRate),
            'tax_included' => $taxIncluded ? 1 : 0,
            'exchange_rate' => self::rate($exchangeRate),
        );
    }

    public static function roundPrice($amount, $mode)
    {
        $amount = max(0.0, (float)$amount);
        $mode = self::normalizeRoundingMode($mode);

        if ($mode === 'nearest_1') {
            return round($amount / 1) * 1;
        }
        if ($mode === 'nearest_5') {
            return round($amount / 5) * 5;
        }
        if ($mode === 'nearest_10') {
            return round($amount / 10) * 10;
        }
        if ($mode === 'psychological_99') {
            if ($amount <= 0) {
                return 0.0;
            }
            $candidate = ceil($amount) - 0.01;
            if ($candidate + 0.000001 < $amount) {
                $candidate = ceil($amount + 1) - 0.01;
            }
            return $candidate;
        }

        return round($amount, 2);
    }

    public static function taxRate($targetCurrency = null, $countryIso = null, array $rules = array())
    {
        if (isset($rules['tax_rate']) && $rules['tax_rate'] !== '' && $rules['tax_rate'] !== null) {
            return max(0.0, (float)$rules['tax_rate']);
        }

        $targetCurrency = $targetCurrency ? strtoupper($targetCurrency) : NtRcManualExchangeRate::defaultTargetCurrency();
        $currencyRate = Configuration::get(self::CFG_TAX_RATE_PREFIX . $targetCurrency);
        if ($currencyRate !== false && $currencyRate !== '' && $currencyRate !== null) {
            return max(0.0, (float)$currencyRate);
        }

        $defaultRate = Configuration::get(self::CFG_DEFAULT_TAX_RATE);
        if ($defaultRate !== false && $defaultRate !== '' && $defaultRate !== null) {
            return max(0.0, (float)$defaultRate);
        }

        return 20.0;
    }

    public static function normalizeMarginMode($mode)
    {
        $mode = strtolower(trim((string)$mode));
        return in_array($mode, self::marginModes(), true) ? $mode : 'manual';
    }

    public static function normalizeRoundingMode($mode)
    {
        $mode = strtolower(trim((string)$mode));
        return in_array($mode, self::roundingModes(), true) ? $mode : 'no_round';
    }

    protected static function targetCurrencyFromRow(array $row)
    {
        if (!empty($row['target_currency'])) {
            return strtoupper($row['target_currency']);
        }
        return NtRcManualExchangeRate::defaultTargetCurrency();
    }

    protected static function removeTax($amountWithTax, $taxRate)
    {
        $factor = 1 + (max(0.0, (float)$taxRate) / 100);
        return $factor > 0 ? ((float)$amountWithTax) / $factor : (float)$amountWithTax;
    }

    protected static function defaultTaxIncluded()
    {
        $value = Configuration::get(self::CFG_DEFAULT_TAX_INCLUDED);
        if ($value === false || $value === '' || $value === null) {
            return true;
        }
        return (int)$value === 1;
    }

    protected static function boolRule(array $rules, $key, $default)
    {
        if (!array_key_exists($key, $rules) || $rules[$key] === null || $rules[$key] === '') {
            return (bool)$default;
        }
        return (int)$rules[$key] === 1 || $rules[$key] === true || $rules[$key] === 'true';
    }

    protected static function money($value)
    {
        return round((float)$value, 2);
    }

    protected static function rate($value)
    {
        return round((float)$value, 6);
    }
}
