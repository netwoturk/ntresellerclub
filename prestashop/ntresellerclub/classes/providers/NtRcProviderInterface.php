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

    public function searchCustomer($email);

    public function createCustomer(array $payload);

    public function updateCustomer($providerCustomerId, array $payload);

    public function getCustomer($providerCustomerId);
}
