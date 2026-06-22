<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

interface NtRcProviderInterface
{
    public function getCode();

    public function checkAvailability($sld, array $tlds, $period = 1);

    public function registerDomain($domainName, $years, array $contact, array $nameservers, array $extra = array());

    public function renewDomain($domainName, $years);

    public function transferDomain($domainName, $authCode, $years = 1);

    public function getDetails($domainName);
}
