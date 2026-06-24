<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcProviderInterface.php';
require_once dirname(__DIR__) . '/NtRcApiClient.php';

class NtRcResellerClubProvider implements NtRcProviderInterface
{
    protected $client;

    public function __construct(NtRcApiClient $client)
    {
        $this->client = $client;
    }

    public function getCode()
    {
        return 'resellerclub';
    }

    public function checkAvailability($sld, array $tlds, $period = 1)
    {
        $response = $this->client->domainAvailability($sld, $tlds);
        if (!$response['success']) {
            return $this->safeResponse($response);
        }

        return array(
            'success' => true,
            'data' => $this->normalizeAvailability($response['data'])
        );
    }

    public function registerDomain($domainName, $years, array $contact, array $nameservers, array $extra = array())
    {
        $params = array_merge($extra, array(
            'domain-name' => $domainName,
            'years' => (int)$years,
        ));

        return $this->safeResponse($this->client->api('domains', 'register', $params, 'POST'));
    }

    public function renewDomain($domainName, $years)
    {
        return $this->safeResponse($this->client->api('domains', 'renew', array(
            'domain-name' => $domainName,
            'years' => (int)$years,
        ), 'POST'));
    }

    public function transferDomain($domainName, $authCode, $years = 1)
    {
        return $this->safeResponse($this->client->api('domains', 'transfer', array(
            'domain-name' => $domainName,
            'auth-code' => $authCode,
            'years' => (int)$years,
        ), 'POST'));
    }

    public function getDetails($domainName)
    {
        return $this->safeResponse($this->client->api('domains', 'details', array(
            'domain-name' => $domainName,
        ), 'GET'));
    }

    public function searchCustomer($email)
    {
        $email = trim((string)$email);
        if ($email === '') {
            return array('success' => false, 'message' => 'Customer email zorunludur.');
        }

        // TODO: ResellerClub customer search filtreleri canlı API dokümanıyla tekrar doğrulanmalı.
        $response = $this->client->api('customers', 'search', array(
            'username' => $email,
            'no-of-records' => 10,
            'page-no' => 1,
        ), 'GET');

        if (empty($response['success'])) {
            return $this->safeResponse($response);
        }

        $customerId = $this->extractCustomerId($response['data'], false);
        return array(
            'success' => true,
            'found' => $customerId ? true : false,
            'provider_customer_id' => $customerId,
            'data' => $this->safeData($response['data']),
        );
    }

    public function createCustomer(array $payload)
    {
        $params = $this->buildCustomerParams($payload);
        if (empty($params['username'])) {
            return array('success' => false, 'message' => 'Customer email zorunludur.');
        }

        // TODO: ResellerClub customers/signup zorunlu alanlari canlı API dokümanıyla tekrar doğrulanmalı.
        $response = $this->client->api('customers', 'signup', $params, 'POST');
        if (empty($response['success'])) {
            return $this->safeResponse($response);
        }

        $customerId = $this->extractCustomerId($response['data'], true);
        if (!$customerId) {
            return array('success' => false, 'message' => 'ResellerClub customer ID cevaptan okunamadi.');
        }

        return array(
            'success' => true,
            'provider_customer_id' => $customerId,
            'data' => $this->safeData($response['data']),
        );
    }

    public function updateCustomer($providerCustomerId, array $payload)
    {
        $providerCustomerId = trim((string)$providerCustomerId);
        if ($providerCustomerId === '') {
            return array('success' => false, 'message' => 'Provider customer ID zorunludur.');
        }

        $params = $this->buildCustomerParams($payload);
        $params['customer-id'] = $providerCustomerId;

        // TODO: ResellerClub customers/modify parametreleri canlı API dokümanıyla tekrar doğrulanmalı.
        return $this->safeResponse($this->client->api('customers', 'modify', $params, 'POST'));
    }

    public function getCustomer($providerCustomerId)
    {
        $providerCustomerId = trim((string)$providerCustomerId);
        if ($providerCustomerId === '') {
            return array('success' => false, 'message' => 'Provider customer ID zorunludur.');
        }

        // TODO: ResellerClub customers/details parametre adı canlı API dokümanıyla tekrar doğrulanmalı.
        return $this->safeResponse($this->client->api('customers', 'details', array(
            'customer-id' => $providerCustomerId,
        ), 'GET'));
    }

    protected function buildCustomerParams(array $payload)
    {
        $contact = isset($payload['contact_profile']) && is_array($payload['contact_profile']) ? $payload['contact_profile'] : array();
        $email = isset($payload['email']) ? $payload['email'] : (isset($contact['email']) ? $contact['email'] : '');
        $firstName = isset($payload['firstname']) ? $payload['firstname'] : (isset($contact['first_name']) ? $contact['first_name'] : '');
        $lastName = isset($payload['lastname']) ? $payload['lastname'] : (isset($contact['last_name']) ? $contact['last_name'] : '');
        $name = trim($firstName . ' ' . $lastName);
        $phoneParts = $this->normalizePhone(isset($contact['phone']) ? $contact['phone'] : '');

        $password = isset($payload['provider_password']) ? $payload['provider_password'] : '';
        if ($password === '' && class_exists('Tools')) {
            $password = Tools::passwdGen(16);
        }
        if ($password === '') {
            $password = sha1(uniqid('', true));
        }

        return array(
            'username' => $email,
            'passwd' => $password,
            'name' => $name,
            'company' => isset($contact['company_name']) ? $contact['company_name'] : '',
            'address-line-1' => isset($contact['address']) ? $contact['address'] : '',
            'city' => isset($contact['city']) ? $contact['city'] : '',
            'state' => isset($contact['state']) && $contact['state'] !== '' ? $contact['state'] : 'NA',
            'country' => isset($contact['country_iso']) ? $contact['country_iso'] : '',
            'zipcode' => isset($contact['postcode']) ? $contact['postcode'] : '',
            'phone-cc' => $phoneParts['cc'],
            'phone' => $phoneParts['number'],
            'lang-pref' => Configuration::get('NTRC_LANG_PREF') ?: 'en',
        );
    }

    protected function normalizePhone($phone)
    {
        $phone = preg_replace('/[^0-9+]/', '', (string)$phone);
        $cc = '90';
        $number = $phone;

        if (strpos($phone, '+') === 0) {
            $digits = substr($phone, 1);
            if (strlen($digits) > 10) {
                $cc = substr($digits, 0, strlen($digits) - 10);
                $number = substr($digits, -10);
            }
        }

        return array('cc' => $cc, 'number' => ltrim($number, '+'));
    }

    protected function extractCustomerId($data, $allowScalar = false)
    {
        if ($allowScalar && is_scalar($data) && trim((string)$data) !== '') {
            return (string)$data;
        }

        if (!is_array($data)) {
            return null;
        }

        foreach (array('customerid', 'customer-id', 'customer_id', 'id', 'entityid') as $key) {
            if (!empty($data[$key])) {
                return (string)$data[$key];
            }
        }

        foreach ($data as $row) {
            if (is_array($row)) {
                $id = $this->extractCustomerId($row, false);
                if ($id) {
                    return $id;
                }
            }
        }

        return null;
    }

    protected function normalizeAvailability($data)
    {
        $items = array();
        foreach ((array)$data as $domain => $row) {
            if (is_array($row) && isset($row['status'])) {
                $status = $row['status'];
            } else {
                $status = is_string($row) ? $row : 'unknown';
            }

            $items[$domain] = array(
                'status' => $status,
                'provider' => $this->getCode(),
                'raw' => $row,
            );
        }
        return $items;
    }

    protected function safeResponse(array $response)
    {
        unset($response['raw'], $response['last_url']);
        if (isset($response['data'])) {
            $response['data'] = $this->safeData($response['data']);
        }
        return $response;
    }

    protected function safeData($data)
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach (array('api-key', 'api_key', 'passwd', 'password', 'auth-code', 'auth_code') as $key) {
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
