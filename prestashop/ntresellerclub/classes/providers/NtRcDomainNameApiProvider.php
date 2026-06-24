<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcProviderInterface.php';
require_once dirname(__DIR__) . '/NtRcApiContractGuard.php';

class NtRcDomainNameApiProvider implements NtRcProviderInterface
{
    protected $username;
    protected $password;
    protected $testMode;

    public function __construct($username, $password, $testMode = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->testMode = (bool)$testMode;
    }

    public function getCode()
    {
        return 'domainnameapi';
    }

    public function checkAvailability($sld, array $tlds, $period = 1)
    {
        $client = $this->createClient();
        if (!$client) {
            return array('success' => false, 'error' => 'DomainNameAPI library not found');
        }

        try {
            $result = $client->checkAvailability(array($sld), $tlds, (int)$period, 'create');
            return array('success' => true, 'data' => $this->normalizeAvailability($result));
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    public function getTrPrices()
    {
        $client = $this->createClient();
        if (!$client) {
            return array('success' => false, 'error' => 'DomainNameAPI library not found');
        }

        try {
            $raw = null;
            if (method_exists($client, 'getPrices')) {
                $raw = $client->getPrices();
            } elseif (method_exists($client, 'getResellerPrices')) {
                $raw = $client->getResellerPrices();
            } elseif (method_exists($client, 'getTldPrices')) {
                $raw = $client->getTldPrices();
            }

            if ($raw === null) {
                return array('success' => false, 'error' => 'DomainNameAPI price method not found');
            }

            return array('success' => true, 'data' => $this->normalizeTrPrices($raw));
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    public function registerDomain($domainName, $years, array $contact, array $nameservers, array $extra = array())
    {
        $client = $this->createClient();
        if (!$client) {
            return array('success' => false, 'error' => 'DomainNameAPI library not found');
        }

        $contacts = array(
            'Administrative' => $contact,
            'Billing' => $contact,
            'Technical' => $contact,
            'Registrant' => $contact,
        );

        try {
            $result = $client->registerWithContactInfo($domainName, (int)$years, $contacts, $nameservers, true, false, $extra);
            return array('success' => true, 'data' => $result);
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    public function renewDomain($domainName, $years)
    {
        $client = $this->createClient();
        if (!$client) {
            return array('success' => false, 'error' => 'DomainNameAPI library not found');
        }

        try {
            return array('success' => true, 'data' => $client->renew($domainName, (int)$years));
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    public function transferDomain($domainName, $authCode, $years = 1)
    {
        $client = $this->createClient();
        if (!$client) {
            return array('success' => false, 'error' => 'DomainNameAPI library not found');
        }

        try {
            return array('success' => true, 'data' => $client->transfer($domainName, $authCode, (int)$years));
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    public function getDetails($domainName)
    {
        $client = $this->createClient();
        if (!$client) {
            return array('success' => false, 'error' => 'DomainNameAPI library not found');
        }

        try {
            return array('success' => true, 'data' => $client->getDetails($domainName));
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    public function searchCustomer($email)
    {
        $email = trim((string)$email);
        if ($email === '') {
            return array('success' => false, 'message' => 'Customer email zorunludur.');
        }

        // TODO: DomainNameAPI PHP SDK customer/contact arama metodu resmi dokümanla doğrulanınca buraya bağlanacak.
        return array('success' => true, 'found' => false, 'provider_customer_id' => null, 'message' => 'DomainNameAPI customer search adapter dogrulama bekliyor.');
    }

    public function createCustomer(array $payload)
    {
        $guard = $this->guardTrCustomerPayload($payload);
        if (empty($guard['success'])) {
            return $guard;
        }

        // TODO: DomainNameAPI PHP SDK customer/contact create metodu resmi dokümanla doğrulanınca buraya bağlanacak.
        return array('success' => false, 'message' => 'DomainNameAPI customer/contact create adapter dogrulama bekliyor.');
    }

    public function updateCustomer($providerCustomerId, array $payload)
    {
        $guard = $this->guardTrCustomerPayload($payload);
        if (empty($guard['success'])) {
            return $guard;
        }

        // TODO: DomainNameAPI PHP SDK customer/contact update metodu resmi dokümanla doğrulanınca buraya bağlanacak.
        return array('success' => false, 'message' => 'DomainNameAPI customer/contact update adapter dogrulama bekliyor.');
    }

    public function getCustomer($providerCustomerId)
    {
        // TODO: DomainNameAPI PHP SDK customer/contact details metodu resmi dokümanla doğrulanınca buraya bağlanacak.
        return array('success' => false, 'message' => 'DomainNameAPI customer/contact details adapter dogrulama bekliyor.');
    }

    protected function guardTrCustomerPayload(array $payload)
    {
        $domainName = isset($payload['domain_name']) ? $payload['domain_name'] : '';
        if (!$domainName || !NtRcApiContractGuard::isDomainNameApiTrDomain($domainName)) {
            return array('success' => false, 'message' => 'DomainNameAPI customer akisi sadece TR domain icin kullanilir.');
        }

        return array('success' => true);
    }

    protected function createClient()
    {
        $library = _PS_MODULE_DIR_ . 'ntresellerclub/vendor/domainnameapi/DomainNameApi/DomainNameAPI_PHPLibrary.php';
        if (!file_exists($library)) {
            return null;
        }
        require_once $library;
        return new \DomainNameApi\DomainNameAPI_PHPLibrary($this->username, $this->password, $this->testMode);
    }

    protected function normalizeAvailability($result)
    {
        $items = array();
        foreach ((array)$result as $row) {
            if (!isset($row['DomainName']) || !isset($row['TLD'])) {
                continue;
            }
            $domain = $row['DomainName'] . '.' . $row['TLD'];
            $status = isset($row['Status']) ? strtolower($row['Status']) : 'unknown';
            $items[$domain] = array(
                'status' => $status === 'available' ? 'available' : 'regthroughothers',
                'provider' => $this->getCode(),
                'price' => isset($row['Price']) ? $row['Price'] : null,
                'currency' => isset($row['Currency']) ? $row['Currency'] : null,
                'raw' => $row,
            );
        }
        return $items;
    }

    protected function normalizeTrPrices($raw)
    {
        $allowed = array('tr', 'com.tr', 'net.tr', 'org.tr', 'av.tr', 'gen.tr', 'web.tr');
        $items = array();

        foreach ((array)$raw as $row) {
            $tld = '';
            if (isset($row['TLD'])) {
                $tld = strtolower(ltrim($row['TLD'], '.'));
            } elseif (isset($row['tld'])) {
                $tld = strtolower(ltrim($row['tld'], '.'));
            } elseif (isset($row['Extension'])) {
                $tld = strtolower(ltrim($row['Extension'], '.'));
            }

            if (!$tld || !in_array($tld, $allowed)) {
                continue;
            }

            $items[$tld] = array(
                'currency' => isset($row['Currency']) ? $row['Currency'] : (isset($row['currency']) ? $row['currency'] : 'USD'),
                'register' => $this->pickPrice($row, array('Register', 'Create', 'Registration', 'register')),
                'transfer' => $this->pickPrice($row, array('Transfer', 'transfer')),
                'renew' => $this->pickPrice($row, array('Renew', 'Renewal', 'renew')),
                'restore' => $this->pickPrice($row, array('Restore', 'Redemption', 'restore')),
                'trustee' => $this->pickPrice($row, array('Trustee', 'trustee')),
                'backorder' => $this->pickPrice($row, array('Backorder', 'backorder')),
            );
        }

        return $items;
    }

    protected function pickPrice(array $row, array $keys)
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && is_numeric($row[$key])) {
                return (float)$row[$key];
            }
        }
        return 0;
    }
}
