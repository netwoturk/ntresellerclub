<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcProviderRouter.php';
require_once __DIR__ . '/NtRcResellerClubProvider.php';
require_once __DIR__ . '/NtRcDomainNameApiProvider.php';
require_once dirname(__DIR__) . '/NtRcApiClient.php';

class NtRcProviderFactory
{
    public static function make($providerCode)
    {
        $providerCode = strtolower(trim($providerCode));

        if ($providerCode === 'resellerclub') {
            $client = new NtRcApiClient(
                (bool)Configuration::get('NTRC_LIVE_MODE'),
                Configuration::get('NTRC_RESELLER_ID'),
                Configuration::get('NTRC_API_KEY'),
                Configuration::get('NTRC_LANG_PREF') ?: 'en'
            );
            return new NtRcResellerClubProvider($client);
        }

        if ($providerCode === 'domainnameapi') {
            return new NtRcDomainNameApiProvider(
                Configuration::get('NTRC_DNA_USERNAME'),
                Configuration::get('NTRC_DNA_PASSWORD'),
                (bool)Configuration::get('NTRC_DNA_TEST_MODE')
            );
        }

        return null;
    }

    public static function makeByDomain($domainName)
    {
        return self::make(NtRcProviderRouter::resolveByDomain($domainName));
    }

    public static function makeByTld($tld)
    {
        return self::make(NtRcProviderRouter::resolveByTld($tld));
    }
}
