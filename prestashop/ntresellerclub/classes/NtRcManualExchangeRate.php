<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcManualExchangeRate
{
    const CFG_PREFIX = 'NTRC_MANUAL_RATE_';

    public static function setRate($fromCurrency, $toCurrency, $rate)
    {
        $fromCurrency = strtoupper(trim($fromCurrency));
        $toCurrency = strtoupper(trim($toCurrency));
        $rate = (float)$rate;
        if (!$fromCurrency || !$toCurrency || $rate <= 0) {
            return false;
        }
        return Configuration::updateValue(self::key($fromCurrency, $toCurrency), $rate);
    }

    public static function getRate($fromCurrency, $toCurrency)
    {
        $fromCurrency = strtoupper(trim($fromCurrency));
        $toCurrency = strtoupper(trim($toCurrency));
        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }
        $rate = (float)Configuration::get(self::key($fromCurrency, $toCurrency));
        return $rate > 0 ? $rate : 0.0;
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
        $currency = new Currency((int)Configuration::get('PS_CURRENCY_DEFAULT'));
        return Validate::isLoadedObject($currency) ? strtoupper($currency->iso_code) : 'TRY';
    }

    protected static function key($fromCurrency, $toCurrency)
    {
        return self::CFG_PREFIX . strtoupper($fromCurrency) . '_' . strtoupper($toCurrency);
    }
}
