<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcApiContractGuard
{
    protected static $providerActions = array(
        'resellerclub' => array(
            'domain' => array('check', 'register', 'transfer', 'renew', 'details', 'nameserver_update', 'contact_update'),
            'hosting' => array('create', 'renew', 'suspend', 'unsuspend', 'details'),
            'ssl' => array('create', 'renew', 'details'),
            'customer' => array('create', 'details', 'update'),
        ),
        'domainnameapi' => array(
            'tr_domain' => array('check', 'register', 'transfer', 'renew', 'details', 'nameserver_update', 'contact_update', 'price_sync'),
            'customer' => array('create', 'details', 'update'),
        ),
    );

    protected static $domainNameApiAllowedTlds = array('tr', 'com.tr', 'net.tr', 'org.tr', 'av.tr', 'gen.tr', 'web.tr');

    public static function validate($providerCode, $serviceType, $action, array $payload = array())
    {
        $providerCode = strtolower(trim((string)$providerCode));
        $serviceType = strtolower(trim((string)$serviceType));
        $action = strtolower(trim((string)$action));

        if (!isset(self::$providerActions[$providerCode])) {
            return self::deny('Provider sozlesmede tanimli degil.');
        }

        if (!isset(self::$providerActions[$providerCode][$serviceType])) {
            return self::deny('Servis tipi bu provider icin tanimli degil.');
        }

        if (!in_array($action, self::$providerActions[$providerCode][$serviceType])) {
            return self::deny('Aksiyon bu provider/servis icin izinli degil.');
        }

        if ($providerCode === 'domainnameapi' && $serviceType === 'tr_domain') {
            $domain = isset($payload['domain']) ? $payload['domain'] : (isset($payload['domain_name']) ? $payload['domain_name'] : '');
            if ($domain && !self::isDomainNameApiTrDomain($domain)) {
                return self::deny('DomainNameAPI bu modulde sadece TR uzantilari icin kullanilir.');
            }
        }

        if ($providerCode === 'domainnameapi' && in_array($serviceType, array('hosting', 'ssl'))) {
            return self::deny('DomainNameAPI hosting/SSL bu modulde kapali.');
        }

        return array('success' => true);
    }

    public static function isDomainNameApiTrDomain($domainName)
    {
        $tld = self::extractTld($domainName);
        return in_array($tld, self::$domainNameApiAllowedTlds);
    }

    public static function extractTld($domainName)
    {
        $domainName = strtolower(trim((string)$domainName));
        $domainName = trim(str_replace(array('http://', 'https://', 'www.'), '', $domainName), '/ .');
        $parts = explode('.', $domainName);
        if (count($parts) >= 3) {
            $lastTwo = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
            if (in_array($lastTwo, self::$domainNameApiAllowedTlds)) {
                return $lastTwo;
            }
        }
        return end($parts);
    }

    protected static function deny($message)
    {
        return array('success' => false, 'message' => $message);
    }
}
