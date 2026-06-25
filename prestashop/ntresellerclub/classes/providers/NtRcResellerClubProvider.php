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
        $params = array(
            'domain-name' => $domainName,
            'years' => max(1, (int)$years),
            'ns' => array_values($nameservers),
            'invoice-option' => $this->pickParam($extra, array('invoice-option', 'invoice_option'), 'NoInvoice'),
            'auto-renew' => $this->boolParam($this->pickParam($extra, array('auto-renew', 'auto_renew'), false)),
        );

        $this->applyCustomerAndContactParams($params, $contact, $extra);
        $this->copyOptionalDomainParams($params, $extra, array(
            'purchase-privacy', 'protect-privacy', 'discount-amount', 'purchase-premium-dns'
        ));
        $this->copyAttrParams($params, $extra);

        $missing = $this->missingRequired($params, array(
            'domain-name', 'years', 'ns', 'customer-id', 'reg-contact-id', 'admin-contact-id',
            'tech-contact-id', 'billing-contact-id', 'invoice-option', 'auto-renew'
        ));
        if (!empty($missing)) {
            return array('success' => false, 'message' => 'ResellerClub register parametreleri eksik.', 'missing' => $missing);
        }

        return $this->normalizeDomainActionResponse($this->client->api('domains', 'register', $params, 'POST'));
    }

    public function renewDomain($domainName, $years, array $extra = array())
    {
        $expDate = $this->pickParam($extra, array('exp-date', 'exp_date'));
        if (!$expDate && !empty($extra['expiry_date'])) {
            $timestamp = strtotime($extra['expiry_date']);
            $expDate = $timestamp ? $timestamp : null;
        }

        $params = array(
            'order-id' => $this->pickParam($extra, array('order-id', 'order_id', 'provider_order_id', 'provider_service_id')),
            'years' => max(1, (int)$years),
            'exp-date' => $expDate,
            'invoice-option' => $this->pickParam($extra, array('invoice-option', 'invoice_option'), 'NoInvoice'),
            'auto-renew' => $this->boolParam($this->pickParam($extra, array('auto-renew', 'auto_renew'), false)),
        );

        $this->copyOptionalDomainParams($params, $extra, array(
            'purchase-privacy', 'discount-amount', 'purchase-premium-dns'
        ));
        $this->copyAttrParams($params, $extra);

        $missing = $this->missingRequired($params, array('order-id', 'years', 'exp-date', 'invoice-option', 'auto-renew'));
        if (!empty($missing)) {
            return array('success' => false, 'message' => 'ResellerClub renew parametreleri eksik.', 'missing' => $missing);
        }

        return $this->normalizeDomainActionResponse($this->client->api('domains', 'renew', $params, 'POST'));
    }

    public function transferDomain($domainName, $authCode, $years = 1, array $extra = array())
    {
        $params = array(
            'domain-name' => $domainName,
            'invoice-option' => $this->pickParam($extra, array('invoice-option', 'invoice_option'), 'NoInvoice'),
            'auto-renew' => $this->boolParam($this->pickParam($extra, array('auto-renew', 'auto_renew'), false)),
        );

        if (trim((string)$authCode) !== '') {
            $params['auth-code'] = $authCode;
        }

        $nameservers = $this->normalizeList($this->pickParam($extra, array('ns', 'nameservers'), array()));
        if (!empty($nameservers)) {
            $params['ns'] = $nameservers;
        }

        $this->applyCustomerAndContactParams($params, isset($extra['contact']) && is_array($extra['contact']) ? $extra['contact'] : array(), $extra);
        $this->copyOptionalDomainParams($params, $extra, array(
            'purchase-privacy', 'protect-privacy', 'purchase-premium-dns'
        ));
        $this->copyAttrParams($params, $extra);

        $missing = $this->missingRequired($params, array(
            'domain-name', 'customer-id', 'reg-contact-id', 'admin-contact-id',
            'tech-contact-id', 'billing-contact-id', 'invoice-option', 'auto-renew'
        ));
        if (!empty($missing)) {
            return array('success' => false, 'message' => 'ResellerClub transfer parametreleri eksik.', 'missing' => $missing);
        }

        return $this->normalizeDomainActionResponse($this->client->api('domains', 'transfer', $params, 'POST'));
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

    protected function applyCustomerAndContactParams(array &$params, array $contact, array $extra)
    {
        $params['customer-id'] = $this->pickParam($extra, array('customer-id', 'customer_id', 'provider_customer_id'));

        $sharedContactId = $this->pickParam($contact, array('provider_contact_id', 'contact_id'));
        if ($sharedContactId === null || $sharedContactId === '') {
            $sharedContactId = $this->pickParam($extra, array('provider_contact_id', 'contact_id'));
        }

        foreach (array(
            'reg-contact-id' => array('reg-contact-id', 'reg_contact_id', 'registrant_contact_id'),
            'admin-contact-id' => array('admin-contact-id', 'admin_contact_id'),
            'tech-contact-id' => array('tech-contact-id', 'tech_contact_id'),
            'billing-contact-id' => array('billing-contact-id', 'billing_contact_id'),
        ) as $apiKey => $keys) {
            $value = $this->pickParam($contact, $keys);
            if ($value === null || $value === '') {
                $value = $this->pickParam($extra, $keys, $sharedContactId);
            }
            $params[$apiKey] = $value;
        }
    }

    protected function copyOptionalDomainParams(array &$params, array $extra, array $keys)
    {
        foreach ($keys as $key) {
            $altKey = str_replace('-', '_', $key);
            $value = $this->pickParam($extra, array($key, $altKey));
            if ($value === null || $value === '') {
                continue;
            }

            if (in_array($key, array('purchase-privacy', 'protect-privacy', 'purchase-premium-dns'))) {
                $params[$key] = $this->boolParam($value);
            } else {
                $params[$key] = $value;
            }
        }
    }

    protected function copyAttrParams(array &$params, array $extra)
    {
        foreach ($extra as $key => $value) {
            if (preg_match('/^attr-(name|value)[0-9]+$/', (string)$key)) {
                $params[$key] = $value;
            }
        }
    }

    protected function normalizeDomainActionResponse(array $response)
    {
        $response = $this->safeResponse($response);
        if (empty($response['success'])) {
            return $response;
        }

        $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : array();

        if (isset($data['status']) && strtoupper(trim((string)$data['status'])) === 'ERROR') {
            $response['success'] = false;
            $response['message'] = isset($data['message']) ? $data['message'] : (isset($data['error']) ? $data['error'] : 'ResellerClub domain action basarisiz.');
            return $response;
        }

        if (isset($data['actionstatus'])) {
            $status = strtolower(trim((string)$data['actionstatus']));
            if (in_array($status, array('failed', 'failure', 'error', 'cancelled', 'canceled'), true)) {
                $response['success'] = false;
                $response['message'] = isset($data['actionstatusdesc']) ? $data['actionstatusdesc'] : 'ResellerClub domain action basarisiz.';
            }
        }

        return $response;
    }

    protected function missingRequired(array $params, array $required)
    {
        $missing = array();
        foreach ($required as $key) {
            if (!array_key_exists($key, $params) || $params[$key] === null || $params[$key] === '' || $params[$key] === array()) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    protected function pickParam(array $data, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                return $data[$key];
            }
        }
        return $default;
    }

    protected function boolParam($value)
    {
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, array('1', 'true', 'yes', 'on'), true) ? 'true' : 'false';
        }
        return $value ? 'true' : 'false';
    }

    protected function normalizeList($value)
    {
        if (is_array($value)) {
            return array_values(array_filter($value, 'strlen'));
        }
        if (is_string($value) && trim($value) !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $value)), 'strlen'));
        }
        return array();
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
        if (isset($response['error'])) {
            $response['error'] = $this->safeText($response['error']);
        }
        if (isset($response['message'])) {
            $response['message'] = $this->safeText($response['message']);
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
            } elseif (is_string($value)) {
                $data[$key] = $this->safeText($value);
            }
        }

        return $data;
    }

    protected function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}