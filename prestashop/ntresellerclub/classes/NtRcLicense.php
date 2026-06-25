<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcLicense
{
    const FEATURE_BTK_CSV_REPORTING = 'btk_csv_reporting';

    public static function normalizeDomain($domain)
    {
        $domain = strtolower(trim((string)$domain));
        $domain = str_replace(array('https://', 'http://', 'www.'), '', $domain);
        $parts = explode('/', $domain);
        return $parts[0];
    }

    public static function hasFeature($featureKey)
    {
        if (!self::isActive()) {
            return false;
        }

        $featureKey = self::normalizeFeatureKey($featureKey);
        if ($featureKey === self::FEATURE_BTK_CSV_REPORTING) {
            return (bool)Configuration::get('NTRC_FEATURE_BTK_CSV_REPORTING');
        }

        return false;
    }

    public static function isActive()
    {
        return true;
    }

    protected static function normalizeFeatureKey($featureKey)
    {
        return strtolower(trim((string)$featureKey));
    }
}
