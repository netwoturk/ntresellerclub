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
            return array('success' => true, 'data' => $this->safeData($result));
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
            return array('success' => true, 'data' => $this->safeData($client->renew($domainName, (int)$years)));
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
            return array('success' => true, 'data' => $this->safeData($client->transfer($domainName, $authCode, (int)$years)));
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
            return array('success' => true, 'data' => $this->safeData($client->getDetails($domainName)));
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    public function getContacts($domainName)
    {
        $domainName = trim((string)$domainName);
        if ($domainName === '') {
            return array('success' => false, 'message' => 'DomainNameAPI contact sorgusu icin domain zorunludur.');
        }

        $client = $this->createClient();
        if (!$client) {
            return array('success' => false, 'error' => 'DomainNameAPI library not found');
        }

        if (!method_exists($client, 'getContacts')) {
            return array('success' => false, 'message' => 'DomainNameAPI SDK getContacts metodu bulunamadi.');
        }

        try {
            return array('success' => true, 'data' => $this->safeData($client->getContacts($domainName)));
        } catch (Exception $e) {
            return array('success' => false, 'error' => $e->getMessage());
        }
    }

    public function saveContacts($domainName, array $contacts)
    {
        $domainName = trim((string)$domainName);
        if ($domainName === '') {
            return array('success' => false, 'message' => 'DomainNameAPI contact guncelleme icin domain zorunludur.');
        }

        $client = $this->createClient();
        if (!$client) {
            return array('success' => false, 'error' => 'DomainNameAPI library not found');
        }

        if (!method_exists($client, 'saveContacts')) {
            return array('success' => false, 'message' => 'DomainNameAPI SDK saveContacts metodu bulunamadi.');
        }

        try {
            return array('success' => true, 'data' => $this->safeData($client->saveContacts($domainName, $contacts)));
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

        return array(
            'success' => true,
            'found' => false,
            'provider_customer_id' => null,
            'message' => 'DomainNameAPI tarafinda provider customer account aramasi kullanilmiyor; TR domain contact hazirligi yapilir.',
        );
    }

    public function createCustomer(array $payload)
    {
        $guard = $this->guardTrCustomerPayload($payload);
        if (empty($guard['success'])) {
            return $guard;
        }

        $contacts = $this->buildDomainContactsFromPayload($payload);
        if (empty($contacts['Administrative']) || empty($contacts['Billing']) || empty($contacts['Technical']) || empty($contacts['Registrant'])) {
            return array('success' => false, 'message' => 'DomainNameAPI TR domain contact payload hazirlanamadi.');
        }

        return array(
            'success' => true,
            'source' => 'domain_contact_prepared',
            'provider_customer_id' => null,
            'domain_name' => isset($payload['domain_name']) ? $payload['domain_name'] : null,
            'data' => array('contacts' => $contacts),
            'message' => 'DomainNameAPI customer/create kuyrugu provider customer account olusturmaz; TR domain contact payload hazirlar.',
        );
    }

    public function updateCustomer($providerCustomerId, array $payload)
    {
        $guard = $this->guardTrCustomerPayload($payload);
        if (empty($guard['success'])) {
            return $guard;
        }

        $domainName = isset($payload['domain_name']) ? $payload['domain_name'] : '';
        $contacts = $this->buildDomainContactsFromPayload($payload);
        return $this->saveContacts($domainName, $contacts);
    }

    public function getCustomer($providerCustomerId)
    {
        $domainName = trim((string)$providerCustomerId);
        if ($domainName === '') {
            return array('success' => false, 'message' => 'DomainNameAPI contact sorgusu icin domain zorunludur.');
        }

        return $this->getContacts($domainName);
    }

    protected function guardTrCustomerPayload(array $payload)
    {
        $domainName = isset($payload['domain_name']) ? $payload['domain_name'] : '';
        if (!$domainName || !NtRcApiContractGuard::isDomainNameApiTrDomain($domainName)) {
            return array('success' => false, 'message' => 'DomainNameAPI customer akisi sadece TR domain contact hazirligi icin kullanilir.');
        }

        return array('success' => true);
    }

    protected function buildDomainContactsFromPayload(array $payload)
    {
        $profile = isset($payload['contact_profile']) && is_array($payload['contact_profile']) ? $payload['contact_profile'] : array();
        $firstName = isset($payload['firstname']) ? $payload['firstname'] : (isset($profile['first_name']) ? $profile['first_name'] : '');
        $lastName = isset($payload['lastname']) ? $payload['lastname'] : (isset($profile['last_name']) ? $profile['last_name'] : '');
        $phone = $this->splitPhone(isset($profile['phone']) ? $profile['phone'] : '');

        $contact = array(
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'Company' => isset($profile['company_name']) && $profile['company_name'] !== '' ? $profile['company_name'] : trim($firstName . ' ' . $lastName),
            'EMail' => isset($payload['email']) ? $payload['email'] : (isset($profile['email']) ? $profile['email'] : ''),
            'AddressLine1' => isset($profile['address']) ? $profile['address'] : '',
            'AddressLine2' => isset($profile['address_2']) ? $profile['address_2'] : '',
            'AddressLine3' => isset($profile['address_3']) ? $profile['address_3'] : '',
            'City' => isset($profile['city']) ? $profile['city'] : '',
            'Country' => isset($profile['country_iso']) ? $profile['country_iso'] : '',
            'Fax' => isset($profile['fax']) ? $profile['fax'] : '',
            'FaxCountryCode' => isset($profile['fax_cc']) ? $profile['fax_cc'] : '',
            'Phone' => $phone['number'],
            'PhoneCountryCode' => $phone['cc'],
            'Type' => 'Contact',
            'ZipCode' => isset($profile['postcode']) ? $profile['postcode'] : '',
            'State' => isset($profile['state']) ? $profile['state'] : '',
        );

        return array(
            'Administrative' => $contact,
            'Billing' => $contact,
            'Technical' => $contact,
            'Registrant' => $contact,
        );
    }

    protected function splitPhone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', (string)$phone);
        $cc = '90';
        $number = ltrim($phone, '+');

        if (strpos($phone, '+') === 0) {
            $digits = substr($phone, 1);
            if (strlen($digits) > 10) {
                $cc = substr($digits, 0, strlen($digits) - 10);
                $number = substr($digits, -10);
            }
        }

        return array('cc' => $cc, 'number' => $number);
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
                'raw' => $this->safeData($row),
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

    protected function safeData($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach (array('api-key', 'api_key', 'ApiKey', 'passwd', 'password', 'Password', 'auth-code', 'auth_code', 'AuthCode') as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->safeData($value);
            }
        }

        return $data;
    }
}
