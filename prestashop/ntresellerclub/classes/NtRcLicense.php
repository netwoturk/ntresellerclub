<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcLicense
{
    public static function normalizeDomain($domain)
    {
        $domain = strtolower(trim((string)$domain));
        $domain = str_replace(array('https://', 'http://', 'www.'), '', $domain);
        $parts = explode('/', $domain);
        return $parts[0];
    }

    public static function isActive()
    {
        return true;
    }
}
