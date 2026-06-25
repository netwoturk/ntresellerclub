<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcHistoryManager.php';

class NtRcManualExchangeRate
{
    const CFG_PREFIX = 'NTRC_MANUAL_RATE_';

    public static function supportedCurrencies()
    {
        return array('USD', 'TRY', 'EUR', 'GBP', 'AZN');
    }

    public static function supportedUsdTargets()
    {
        return array('TRY', 'EUR', 'GBP', 'AZN');
    }

    public static function setRate($fromCurrency, $toCurrency, $rate, $source = 'admin')
    {
        $fromCurrency = self::normalizeCurrency($fromCurrency);
        $toCurrency = self::normalizeCurrency($toCurrency);
        $rate = (float)$rate;
        if (!self::isSupportedCurrency($fromCurrency) || !self::isSupportedCurrency($toCurrency) || $rate <= 0) {
            return false;
        }

        $oldRate = self::getRate($fromCurrency, $toCurrency);
        $ok = Configuration::updateValue(self::key($fromCurrency, $toCurrency), $rate);
        if ($ok && (float)$oldRate !== (float)$rate) {
            NtRcHistoryManager::addExchangeRateChange($fromCurrency, $toCurrency, $oldRate > 0 ? $oldRate : null, $rate, $source);
        }
        return $ok;
    }

    public static function setUsdRate($toCurrency, $rate, $source = 'admin')
    {
        return self::setRate('USD', $toCurrency, $rate, $source);
    }

    public static function getRate($fromCurrency, $toCurrency)
    {
        $fromCurrency = self::normalizeCurrency($fromCurrency);
        $toCurrency = self::normalizeCurrency($toCurrency);
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }
        if (!self::isSupportedCurrency($fromCurrency) || !self::isSupportedCurrency($toCurrency)) {
            return 0.0;
        }

        $rate = (float)Configuration::get(self::key($fromCurrency, $toCurrency));
        if ($rate > 0) {
            return $rate;
        }

        $inverse = (float)Configuration::get(self::key($toCurrency, $fromCurrency));
        if ($inverse > 0) {
            return round(1 / $inverse, 6);
        }

        return 0.0;
    }

    public static function getUsdRate($toCurrency)
    {
        return self::getRate('USD', $toCurrency);
    }

    public static function allUsdRates()
    {
        $rates = array();
        foreach (self::supportedUsdTargets() as $target) {
            $rates[$target] = self::getUsdRate($target);
        }
        return $rates;
    }

    public static function ensureDefaultRates()
    {
        $defaults = array(
            'TRY' => 40,
            'EUR' => 0.92,
            'GBP' => 0.78,
            'AZN' => 1.70,
        );

        foreach ($defaults as $target => $rate) {
            if (self::getUsdRate($target) <= 0) {
                self::setUsdRate($target, $rate, 'install_default');
            }
        }
        return true;
    }

    public static function convert($amount, $fromCurrency, $toCurrency)
    {
        $rate = self::getRate($fromCurrency, $toCurrency);
        if ($rate <= 0) {
            return null;
        }
        return round(((float)$amount) * $rate, 6);
    }

    public static function defaultTargetCurrency()
    {
        if (class_exists('Currency')) {
            $currency = new Currency((int)Configuration::get('PS_CURRENCY_DEFAULT'));
            if (Validate::isLoadedObject($currency) && self::isSupportedCurrency($currency->iso_code)) {
                return self::normalizeCurrency($currency->iso_code);
            }
        }
        return 'TRY';
    }

    public static function isSupportedCurrency($currency)
    {
        return in_array(self::normalizeCurrency($currency), self::supportedCurrencies(), true);
    }

    protected static function normalizeCurrency($currency)
    {
        return strtoupper(substr(trim((string)$currency), 0, 10));
    }

    protected static function key($fromCurrency, $toCurrency)
    {
        return self::CFG_PREFIX . self::normalizeCurrency($fromCurrency) . '_' . self::normalizeCurrency($toCurrency);
    }
}
