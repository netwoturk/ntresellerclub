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
            'provider_username' => $email,
            'data' => $this->safeData($response['data']),
        );
    }

    public function createCustomer(array $payload)
    {
        $params = $this->buildCustomerParams($payload, true);
        if (empty($params['username'])) {
            return array('success' => false, 'message' => 'Customer email zorunludur.');
        }

        $response = $this->client->api('customers', 'v2/signup', $params, 'POST');
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
            'provider_username' => $params['username'],
            'data' => $this->safeData($response['data']),
        );
    }

    public function updateCustomer($providerCustomerId, array $payload)
    {
        $providerCustomerId = trim((string)$providerCustomerId);
        if ($providerCustomerId === '') {
            return array('success' => false, 'message' => 'Provider customer ID zorunludur.');
        }

        $params = $this->buildCustomerParams($payload, false);
        $params['customer-id'] = $providerCustomerId;

        return $this->safeResponse($this->client->api('customers', 'modify', $params, 'POST'));
    }

    public function getCustomer($username)
    {
        $username = trim((string)$username);
        if ($username === '') {
            return array('success' => false, 'message' => 'Customer username zorunludur.');
        }

        return $this->safeResponse($this->client->api('customers', 'details', array(
            'username' => $username,
        ), 'GET'));
    }

    protected function buildCustomerParams(array $payload, $includePassword)
    {
        $contact = isset($payload['contact_profile']) && is_array($payload['contact_profile']) ? $payload['contact_profile'] : array();
        $email = isset($payload['email']) ? $payload['email'] : (isset($contact['email']) ? $contact['email'] : '');
        $firstName = isset($payload['firstname']) ? $payload['firstname'] : (isset($contact['first_name']) ? $contact['first_name'] : '');
        $lastName = isset($payload['lastname']) ? $payload['lastname'] : (isset($contact['last_name']) ? $contact['last_name'] : '');
        $name = trim($firstName . ' ' . $lastName);
        $phoneParts = $this->normalizePhone(isset($contact['phone']) ? $contact['phone'] : '');
        $company = isset($contact['company_name']) ? trim((string)$contact['company_name']) : '';

        if ($company === '') {
            $company = $name;
        }

        $params = array(
            'username' => $email,
            'name' => $name,
            'company' => $company,
            'address-line-1' => isset($contact['address']) ? $contact['address'] : '',
            'city' => isset($contact['city']) ? $contact['city'] : '',
            'state' => isset($contact['state']) && $contact['state'] !== '' ? $contact['state'] : 'Not Applicable',
            'country' => isset($contact['country_iso']) ? $contact['country_iso'] : '',
            'zipcode' => isset($contact['postcode']) ? $contact['postcode'] : '',
            'phone-cc' => $phoneParts['cc'],
            'phone' => $phoneParts['number'],
            'lang-pref' => Configuration::get('NTRC_LANG_PREF') ?: 'en',
        );

        foreach (array(
            'address-line-2' => 'address_2',
            'address-line-3' => 'address_3',
            'other-state' => 'other_state',
            'mobile-cc' => 'mobile_cc',
            'mobile' => 'mobile',
            'fax-cc' => 'fax_cc',
            'fax' => 'fax',
            'vat-id' => 'vat_id',
            'indian-gst-id' => 'indian_gst_id',
            'russia-vat-id' => 'russia_vat_id',
            'australia-gst-id' => 'australia_gst_id',
            'newzealand-gst-id' => 'newzealand_gst_id',
            'singapore-gst-id' => 'singapore_gst_id',
        ) as $apiKey => $payloadKey) {
            if (isset($contact[$payloadKey]) && trim((string)$contact[$payloadKey]) !== '') {
                $params[$apiKey] = $contact[$payloadKey];
            }
        }

        if ($includePassword) {
            $password = isset($payload['provider_password']) ? $payload['provider_password'] : '';
            if ($password === '' && class_exists('Tools')) {
                $password = Tools::passwdGen(16);
            }
            if ($password === '') {
                $password = sha1(uniqid('', true));
            }
            $params['passwd'] = $password;

            foreach (array('sms-consent', 'accept-policy', 'marketing-email-consent') as $optionalBool) {
                if (array_key_exists($optionalBool, $payload)) {
                    $params[$optionalBool] = $payload[$optionalBool] ? 'true' : 'false';
                }
            }
        }

        return $params;
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
