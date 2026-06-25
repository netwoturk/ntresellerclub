<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';
require_once __DIR__ . '/NtRcOperationQueueManager.php';
require_once __DIR__ . '/NtRcContactProfileManager.php';
require_once __DIR__ . '/NtRcProviderCustomerManager.php';
require_once __DIR__ . '/NtRcServiceRepository.php';
require_once __DIR__ . '/NtRcApiContractGuard.php';
require_once __DIR__ . '/NtRcLog.php';
require_once __DIR__ . '/providers/NtRcTldRouteManager.php';

class NtRcDomainManager
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function provisionCartDomain(Order $order, array $cartDomain, array $providerCustomer = array())
    {
        NtRcInstaller::ensureServiceSchema();

        $domainName = isset($cartDomain['domain_name']) ? trim((string)$cartDomain['domain_name']) : '';
        $providerCode = isset($cartDomain['provider_code']) ? strtolower(trim((string)$cartDomain['provider_code'])) : '';
        $years = isset($cartDomain['years']) ? max(1, (int)$cartDomain['years']) : 1;

        if ($domainName === '') {
            return array('success' => false, 'message' => 'Sepet domain bilgisi bulunamadi.');
        }

        if ($providerCode === '') {
            $providerCode = NtRcTldRouteManager::resolveDomain($domainName);
        }

        if (empty($providerCustomer['success'])) {
            $providerCustomer = NtRcProviderCustomerManager::ensure((int)$order->id_customer, $providerCode, $domainName);
            if (empty($providerCustomer['success'])) {
                return $providerCustomer;
            }
        }

        $profileResult = NtRcContactProfileManager::ensureDefault((int)$order->id_customer);
        if (empty($profileResult['success'])) {
            return $profileResult;
        }

        $profile = $profileResult['profile'];
        $options = $this->decodeOptions($cartDomain);
        $idService = $this->createDomainService($order, $domainName, $providerCode, $years, $providerCustomer, isset($cartDomain['id_product']) ? (int)$cartDomain['id_product'] : 0);
        if ($idService <= 0) {
            NtRcLog::add('error', 'domain_provisioning', 'Service insert failed domain=' . $domainName . ' provider=' . $providerCode);
            return array('success' => false, 'message' => 'Domain servis kaydi olusturulamadi.');
        }

        $queue = $this->enqueueRegisterForService($idService, (int)$order->id, (int)$order->id_customer, $providerCode, $domainName, $years, $profile, $providerCustomer, $options);
        if (empty($queue['success'])) {
            NtRcServiceRepository::updateStatus($idService, 'error');
            return $queue;
        }

        return array(
            'success' => true,
            'domain' => $domainName,
            'provider' => $providerCode,
            'years' => $years,
            'status' => 'register_waiting',
            'service_id' => $idService,
            'queue_id' => isset($queue['queue_id']) ? (int)$queue['queue_id'] : 0,
            'contact_ready' => $providerCode !== 'domainnameapi' || (isset($providerCustomer['source']) && $providerCustomer['source'] === 'contact_ready'),
        );
    }

    public function maybeProvisionDomain(Order $order, array $product, array $customerResult)
    {
        $domainName = $this->extractDomainName($product);

        if (!$domainName) {
            return array('success' => true, 'skipped' => true, 'reason' => 'Domain bilgisi bulunamadi.');
        }

        $providerCode = NtRcTldRouteManager::resolveDomain($domainName);
        $providerCustomer = NtRcProviderCustomerManager::ensure((int)$order->id_customer, $providerCode, $domainName);
        if (empty($providerCustomer['success'])) {
            return $providerCustomer;
        }

        return $this->provisionCartDomain($order, array(
            'domain_name' => $domainName,
            'provider_code' => $providerCode,
            'years' => 1,
            'id_product' => isset($product['id_product']) ? (int)$product['id_product'] : 0,
        ), $providerCustomer);
    }

    public function enqueueTransfer($idService, $authCode, array $options = array())
    {
        $service = NtRcServiceRepository::getService((int)$idService);
        if (!$service || empty($service['domain_name'])) {
            return array('success' => false, 'message' => 'Transfer icin servis kaydi bulunamadi.');
        }

        $payload = $this->buildDomainOperationPayload($service, max(1, isset($options['years']) ? (int)$options['years'] : 1), $options);
        $payload['auth_code'] = $authCode;

        return $this->enqueueDomainOperation($service, 'transfer', $payload, 2);
    }

    public function enqueueRenew($idService, $years = 1, array $options = array())
    {
        $service = NtRcServiceRepository::getService((int)$idService);
        if (!$service || empty($service['domain_name'])) {
            return array('success' => false, 'message' => 'Renew icin servis kaydi bulunamadi.');
        }

        $payload = $this->buildDomainOperationPayload($service, max(1, (int)$years), $options);
        $payload['provider_order_id'] = isset($service['provider_order_id']) ? $service['provider_order_id'] : null;
        $payload['provider_service_id'] = isset($service['provider_service_id']) ? $service['provider_service_id'] : null;
        $payload['expiry_date'] = isset($service['expiry_date']) ? $service['expiry_date'] : null;

        return $this->enqueueDomainOperation($service, 'renew', $payload, 2);
    }

    protected function enqueueRegisterForService($idService, $idOrder, $idCustomer, $providerCode, $domainName, $years, array $profile, array $providerCustomer, array $options)
    {
        $service = NtRcServiceRepository::getService((int)$idService);
        if (!$service) {
            return array('success' => false, 'message' => 'Register queue icin servis kaydi bulunamadi.');
        }

        if ($this->hasOpenOperationQueue($idService, 'register')) {
            return array('success' => true, 'queue_id' => 0, 'source' => 'existing_queue');
        }

        $payload = $this->buildDomainOperationPayload($service, $years, $options, $profile, $providerCustomer);
        $payload['id_order'] = (int)$idOrder;
        $payload['id_customer'] = (int)$idCustomer;
        $payload['id_service'] = (int)$idService;

        return $this->enqueueDomainOperation($service, 'register', $payload, 3);
    }

    protected function buildDomainOperationPayload(array $service, $years, array $options = array(), array $profile = array(), array $providerCustomer = array())
    {
        $providerCode = isset($service['provider_code']) ? strtolower($service['provider_code']) : '';
        $domainName = isset($service['domain_name']) ? $service['domain_name'] : '';
        $extra = $this->extractExtraOptions($options);
        $nameservers = $this->extractNameservers($options);

        if (!empty($providerCustomer['provider_customer_id'])) {
            $extra['provider_customer_id'] = $providerCustomer['provider_customer_id'];
            $extra['customer-id'] = $providerCustomer['provider_customer_id'];
        }

        if (!isset($extra['invoice-option']) && !isset($extra['invoice_option'])) {
            $extra['invoice-option'] = 'NoInvoice';
        }
        if (!isset($extra['auto-renew']) && !isset($extra['auto_renew'])) {
            $extra['auto-renew'] = false;
        }

        $contact = $providerCode === 'domainnameapi'
            ? $this->buildDomainNameApiContact($profile)
            : $this->buildResellerClubContactIds($options, $providerCustomer);

        if ($providerCode === 'domainnameapi') {
            $extra = array_merge($extra, $this->buildTrabisAttributes($profile, $options));
        }

        return array(
            'domain' => $domainName,
            'domain_name' => $domainName,
            'years' => max(1, (int)$years),
            'provider_code' => $providerCode,
            'nameservers' => $nameservers,
            'contact' => $contact,
            'extra' => $extra,
            'provider_order_id' => isset($service['provider_order_id']) ? $service['provider_order_id'] : null,
            'provider_service_id' => isset($service['provider_service_id']) ? $service['provider_service_id'] : null,
            'expiry_date' => isset($service['expiry_date']) ? $service['expiry_date'] : null,
        );
    }

    protected function enqueueDomainOperation(array $service, $action, array $payload, $priority)
    {
        $providerCode = strtolower((string)$service['provider_code']);
        $serviceType = $providerCode === 'domainnameapi' ? 'tr_domain' : 'domain';

        return NtRcOperationQueueManager::enqueue(
            $providerCode,
            $serviceType,
            $action,
            $payload,
            isset($service['id_order']) ? (int)$service['id_order'] : null,
            isset($service['id_customer']) ? (int)$service['id_customer'] : null,
            isset($service['id_ntresellerclub_service']) ? (int)$service['id_ntresellerclub_service'] : null,
            3,
            $priority
        );
    }

    protected function createDomainService(Order $order, $domainName, $providerCode, $years, array $providerCustomer, $idProduct = 0)
    {
        $expiryDate = date('Y-m-d', strtotime('+' . max(1, (int)$years) . ' year'));
        $ok = Db::getInstance()->insert('ntresellerclub_service', array(
            'id_customer' => (int)$order->id_customer,
            'id_order' => (int)$order->id,
            'id_product' => (int)$idProduct,
            'provider_code' => pSQL($providerCode),
            'service_type' => pSQL('domain'),
            'domain_name' => pSQL($domainName),
            'provider_service_id' => null,
            'provider_order_id' => null,
            'provider_customer_id' => !empty($providerCustomer['provider_customer_id']) ? pSQL($providerCustomer['provider_customer_id']) : null,
            'provider_contact_id' => null,
            'start_date' => date('Y-m-d'),
            'expiry_date' => pSQL($expiryDate),
            'status' => pSQL('register_waiting'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        return $ok ? (int)Db::getInstance()->Insert_ID() : 0;
    }

    protected function buildDomainNameApiContact(array $profile)
    {
        $phone = $this->splitPhone(isset($profile['phone']) ? $profile['phone'] : '');
        $firstName = isset($profile['first_name']) ? $profile['first_name'] : '';
        $lastName = isset($profile['last_name']) ? $profile['last_name'] : '';

        return array(
            'FirstName' => $firstName,
            'LastName' => $lastName,
            'Company' => isset($profile['company_name']) && $profile['company_name'] !== '' ? $profile['company_name'] : trim($firstName . ' ' . $lastName),
            'EMail' => isset($profile['email']) ? $profile['email'] : '',
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
    }

    protected function buildResellerClubContactIds(array $options, array $providerCustomer)
    {
        $contact = array();
        $contactOptions = isset($options['contact_ids']) && is_array($options['contact_ids']) ? $options['contact_ids'] : $options;
        $sharedContactId = null;

        foreach (array('provider_contact_id', 'contact_id') as $key) {
            if (!empty($contactOptions[$key])) {
                $sharedContactId = $contactOptions[$key];
                break;
            }
        }

        foreach (array(
            'reg-contact-id' => array('reg-contact-id', 'reg_contact_id', 'registrant_contact_id'),
            'admin-contact-id' => array('admin-contact-id', 'admin_contact_id'),
            'tech-contact-id' => array('tech-contact-id', 'tech_contact_id'),
            'billing-contact-id' => array('billing-contact-id', 'billing_contact_id'),
        ) as $apiKey => $keys) {
            $contact[$apiKey] = $this->pickOption($contactOptions, $keys, $sharedContactId);
        }

        return $contact;
    }

    protected function buildTrabisAttributes(array $profile, array $options)
    {
        $extra = array();
        $source = isset($options['extra']) && is_array($options['extra']) ? $options['extra'] : $options;

        foreach (array('TRABISDOMAINCATEGORY', 'TRABISCITIZIENID', 'TRABISNAMESURNAME', 'TRABISCOUNTRYID', 'TRABISCOUNTRYNAME', 'TRABISCITYID', 'TRABISCITYNAME') as $key) {
            if (!empty($source[$key])) {
                $extra[$key] = $source[$key];
            }
        }

        if (empty($extra['TRABISCITIZIENID']) && !empty($profile['tc_number'])) {
            $extra['TRABISCITIZIENID'] = $profile['tc_number'];
        }
        if (empty($extra['TRABISNAMESURNAME'])) {
            $name = trim((isset($profile['first_name']) ? $profile['first_name'] : '') . ' ' . (isset($profile['last_name']) ? $profile['last_name'] : ''));
            if ($name !== '') {
                $extra['TRABISNAMESURNAME'] = $name;
            }
        }
        if (empty($extra['TRABISCOUNTRYNAME']) && !empty($profile['country_iso'])) {
            $extra['TRABISCOUNTRYNAME'] = $profile['country_iso'];
        }
        if (empty($extra['TRABISCITYNAME']) && !empty($profile['city'])) {
            $extra['TRABISCITYNAME'] = $profile['city'];
        }

        return $extra;
    }

    protected function extractExtraOptions(array $options)
    {
        $extra = isset($options['extra']) && is_array($options['extra']) ? $options['extra'] : array();
        foreach ($options as $key => $value) {
            if (in_array($key, array('extra', 'nameservers', 'ns', 'contact_ids'))) {
                continue;
            }
            if (preg_match('/^attr-(name|value)[0-9]+$/', (string)$key) || strpos((string)$key, 'TRABIS') === 0 || in_array($key, array(
                'invoice-option', 'invoice_option', 'auto-renew', 'auto_renew', 'purchase-privacy', 'protect-privacy',
                'discount-amount', 'purchase-premium-dns', 'provider_contact_id', 'contact_id', 'reg-contact-id',
                'reg_contact_id', 'admin-contact-id', 'admin_contact_id', 'tech-contact-id', 'tech_contact_id',
                'billing-contact-id', 'billing_contact_id'
            ))) {
                $extra[$key] = $value;
            }
        }
        return $extra;
    }

    protected function extractNameservers(array $options)
    {
        $nameservers = $this->normalizeList($this->pickOption($options, array('nameservers', 'ns'), array()));
        if (!empty($nameservers)) {
            return $nameservers;
        }

        $defaults = array();
        foreach (array('NTRC_DEFAULT_NS1', 'NTRC_DEFAULT_NS2', 'NTRC_DEFAULT_NS3', 'NTRC_DEFAULT_NS4') as $key) {
            $value = Configuration::get($key);
            if ($value) {
                $defaults[] = $value;
            }
        }
        return $defaults;
    }

    protected function decodeOptions(array $cartDomain)
    {
        if (isset($cartDomain['options']) && is_array($cartDomain['options'])) {
            return $cartDomain['options'];
        }

        if (!empty($cartDomain['options_json'])) {
            $decoded = json_decode($cartDomain['options_json'], true);
            return is_array($decoded) ? $decoded : array();
        }

        return array();
    }

    protected function hasOpenOperationQueue($idService, $action)
    {
        $value = Db::getInstance()->getValue(
            'SELECT id_ntresellerclub_operation_queue FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` '
            . 'WHERE id_service=' . (int)$idService . ' '
            . 'AND action="' . pSQL($action) . '" '
            . 'AND status IN ("pending", "processing")'
        );

        return (bool)$value;
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

    protected function pickOption(array $data, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data) && $data[$key] !== null && $data[$key] !== '') {
                return $data[$key];
            }
        }
        return $default;
    }

    protected function extractDomainName(array $product)
    {
        if (!empty($product['product_reference']) && strpos($product['product_reference'], 'DOMAIN:') === 0) {
            return substr($product['product_reference'], 7);
        }
        return null;
    }
}
