<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcLicense.php';

class NtRcFeature
{
    public static function isCoreActive()
    {
        return (bool)Configuration::get('NTRC_FEATURE_CORE');
    }

    public static function isResellerClubActive()
    {
        return self::isCoreActive() && (bool)Configuration::get('NTRC_FEATURE_RESELLERCLUB');
    }

    public static function isDomainNameApiActive()
    {
        return self::isCoreActive() && (bool)Configuration::get('NTRC_FEATURE_DOMAINNAMEAPI');
    }

    public static function isHostingManagerActive()
    {
        return self::isCoreActive() && (bool)Configuration::get('NTRC_FEATURE_HOSTING');
    }

    public static function isBtkCsvReportingActive()
    {
        return self::isCoreActive() && NtRcLicense::hasFeature(NtRcLicense::FEATURE_BTK_CSV_REPORTING);
    }

    public static function providerEnabled($providerCode)
    {
        if ($providerCode === 'resellerclub') {
            return self::isResellerClubActive();
        }
        if ($providerCode === 'domainnameapi') {
            return self::isDomainNameApiActive();
        }
        return false;
    }
}
