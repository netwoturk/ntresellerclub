<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcProviderRouter
{
    public static function resolveByDomain($domainName)
    {
        $domainName = strtolower(trim($domainName));

        if (preg_match('/\.(com\.tr|net\.tr|org\.tr|tr)$/', $domainName)) {
            return 'domainnameapi';
        }

        return 'resellerclub';
    }

    public static function resolveByTld($tld)
    {
        $tld = strtolower(ltrim(trim($tld), '.'));

        if (in_array($tld, array('tr', 'com.tr', 'net.tr', 'org.tr'))) {
            return 'domainnameapi';
        }

        return 'resellerclub';
    }
}
