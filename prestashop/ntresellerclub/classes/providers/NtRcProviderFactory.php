<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcProviderRegistry.php';
require_once __DIR__ . '/NtRcTldRouteManager.php';
require_once __DIR__ . '/NtRcResellerClubProvider.php';
require_once __DIR__ . '/NtRcDomainNameApiProvider.php';
require_once dirname(__DIR__) . '/NtRcApiClient.php';

class NtRcProviderFactory
{
    public static function make($providerCode, $strictUsable = true)
    {
        $providerCode = strtolower(trim((string)$providerCode));
        if (!$providerCode) {
            return null;
        }

        if ($strictUsable && !NtRcProviderRegistry::isUsable($providerCode)) {
            return null;
        }

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

        return self::makeCustomProvider($providerCode);
    }

    public static function makeByDomain($domainName)
    {
        return self::make(NtRcTldRouteManager::resolveDomain($domainName));
    }

    public static function makeByTld($tld)
    {
        return self::make(NtRcTldRouteManager::resolve($tld));
    }

    protected static function makeCustomProvider($providerCode)
    {
        $className = 'NtRc' . str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $providerCode))) . 'Provider';
        $file = __DIR__ . '/custom/' . $className . '.php';

        if (!file_exists($file)) {
            return null;
        }

        require_once $file;
        if (!class_exists($className)) {
            return null;
        }

        return new $className();
    }
}
