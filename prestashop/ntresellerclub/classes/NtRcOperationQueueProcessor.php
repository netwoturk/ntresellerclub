<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcOperationQueueManager.php';
require_once __DIR__ . '/NtRcApiContractGuard.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcProviderCustomerManager.php';
require_once __DIR__ . '/NtRcServiceRepository.php';
require_once __DIR__ . '/NtRcNotificationEngine.php';
require_once __DIR__ . '/providers/NtRcProviderFactory.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcOperationQueueProcessor
{
    public function process($limit = 10)
    {
        NtRcRuntimeGuard::beforeHeavyProcess('operation_queue_processor');
        $limit = min((int)$limit, NtRcRuntimeGuard::cronBatchLimit(10));
        $items = NtRcOperationQueueManager::pending($limit);
        $results = array();

        foreach ((array)$items as $item) {
            try {
                $results[] = $this->processOne($item);
            } catch (Exception $e) {
                NtRcOperationQueueManager::markRetryOrFailed($item, $this->safeText($e->getMessage()));
                NtRcLog::add('error', 'operation_queue_processor', 'Exception queue=' . (int)$item['id_ntresellerclub_operation_queue'] . ' ' . $this->safeText($e->getMessage()));
                $results[] = array('success' => false, 'queue_id' => (int)$item['id_ntresellerclub_operation_queue'], 'error' => $this->safeText($e->getMessage()));
            }
        }

        return array('success' => true, 'limit' => $limit, 'count' => count($results), 'items' => $results);
    }

    protected function processOne(array $item)
    {
        $idQueue = (int)$item['id_ntresellerclub_operation_queue'];
        $lockToken = NtRcOperationQueueManager::markProcessing($idQueue);
        if (!$lockToken) {
            return array('success' => false, 'queue_id' => $idQueue, 'message' => 'Queue kaydi baska cron tarafindan kilitlendi.');
        }

        try {
            $payload = $this->decodePayload($item['payload_json']);
            $contract = NtRcApiContractGuard::validate($item['provider_code'], $item['service_type'], $item['action'], $payload);
            if (empty($contract['success'])) {
                $error = $this->safeText($contract['message']);
                NtRcOperationQueueManager::markRetryOrFailed($item, $error, $lockToken);
                $this->afterFailure($item, $payload, $error);
                return array('success' => false, 'queue_id' => $idQueue, 'message' => $error);
            }

            $provider = NtRcProviderFactory::make($item['provider_code']);
            if (!$provider) {
                $error = 'Provider olusturulamadi.';
                NtRcOperationQueueManager::markRetryOrFailed($item, $error, $lockToken);
                $this->afterFailure($item, $payload, $error);
                return array('success' => false, 'queue_id' => $idQueue, 'message' => $error);
            }

            $response = $this->sanitizeProviderResponse($this->dispatch($provider, $item, $payload));
            if (!empty($response['success'])) {
                $this->afterSuccess($item, $payload, $response);
                NtRcOperationQueueManager::markDone($idQueue, $response, $lockToken);
                NtRcLog::add('info', 'operation_queue_processor', 'Queue done id=' . $idQueue . ' action=' . $item['action']);
                return array('success' => true, 'queue_id' => $idQueue, 'action' => $item['action']);
            }

            $error = isset($response['error']) ? $response['error'] : (isset($response['message']) ? $response['message'] : 'Provider islemi basarisiz.');
            $error = $this->safeText($error);
            NtRcOperationQueueManager::markRetryOrFailed($item, $error, $lockToken);
            $this->afterFailure($item, $payload, $error);
            NtRcLog::add('error', 'operation_queue_processor', 'Queue failed id=' . $idQueue . ' error=' . $error);
            return array('success' => false, 'queue_id' => $idQueue, 'error' => $error);
        } catch (Exception $e) {
            $error = $this->safeText($e->getMessage());
            NtRcOperationQueueManager::markRetryOrFailed($item, $error, $lockToken);
            $this->afterFailure($item, $this->decodePayload($item['payload_json']), $error);
            NtRcLog::add('error', 'operation_queue_processor', 'Exception queue=' . $idQueue . ' ' . $error);
            return array('success' => false, 'queue_id' => $idQueue, 'error' => $error);
        }
    }

    protected function dispatch($provider, array $item, array $payload)
    {
        $action = $item['action'];
        $domain = isset($payload['domain']) ? $payload['domain'] : (isset($payload['domain_name']) ? $payload['domain_name'] : null);
        $years = isset($payload['years']) ? (int)$payload['years'] : 1;

        if ($item['service_type'] === 'customer' && $action === 'create') {
            return $this->dispatchCustomerCreate($provider, $item, $payload);
        }

        if (in_array($item['service_type'], array('domain', 'tr_domain')) && $action === 'register') {
            return $this->dispatchDomainRegister($provider, $item, $payload);
        }

        if (in_array($item['service_type'], array('domain', 'tr_domain')) && $action === 'transfer') {
            return $this->dispatchDomainTransfer($provider, $item, $payload);
        }

        if (in_array($item['service_type'], array('domain', 'tr_domain')) && $action === 'renew') {
            return $this->dispatchDomainRenew($provider, $item, $payload);
        }

        if ($action === 'details' && $domain && method_exists($provider, 'getDetails')) {
            return $provider->getDetails($domain);
        }

        if ($action === 'contact_update' && $domain && method_exists($provider, 'saveContacts')) {
            $contacts = isset($payload['contacts']) && is_array($payload['contacts']) ? $payload['contacts'] : array();
            return $provider->saveContacts($domain, $contacts);
        }

        return array('success' => false, 'message' => 'Bu action icin provider metodu tanimli degil.');
    }

    protected function dispatchDomainRegister($provider, array $item, array $payload)
    {
        $domain = isset($payload['domain']) ? $payload['domain'] : (isset($payload['domain_name']) ? $payload['domain_name'] : '');
        if ($domain === '') {
            return array('success' => false, 'message' => 'Domain register icin domain zorunludur.');
        }

        if ($item['provider_code'] === 'domainnameapi' && !$this->isDomainNameApiContactReady($item, $payload)) {
            return array('success' => false, 'message' => 'DomainNameAPI register icin TR domain contact hazir degil.');
        }

        $payload = $this->withCurrentProviderCustomer($item, $payload);
        $contact = isset($payload['contact']) && is_array($payload['contact']) ? $payload['contact'] : array();
        $nameservers = isset($payload['nameservers']) && is_array($payload['nameservers']) ? $payload['nameservers'] : array();
        $extra = $this->buildProviderExtra($payload);

        return $provider->registerDomain($domain, isset($payload['years']) ? (int)$payload['years'] : 1, $contact, $nameservers, $extra);
    }

    protected function dispatchDomainTransfer($provider, array $item, array $payload)
    {
        $domain = isset($payload['domain']) ? $payload['domain'] : (isset($payload['domain_name']) ? $payload['domain_name'] : '');
        if ($domain === '') {
            return array('success' => false, 'message' => 'Domain transfer icin domain zorunludur.');
        }

        $payload = $this->withCurrentProviderCustomer($item, $payload);
        $extra = $this->buildProviderExtra($payload);
        $extra['contact'] = isset($payload['contact']) && is_array($payload['contact']) ? $payload['contact'] : array();
        $extra['nameservers'] = isset($payload['nameservers']) && is_array($payload['nameservers']) ? $payload['nameservers'] : array();
        $authCode = isset($payload['auth_code']) ? $payload['auth_code'] : '';

        return $provider->transferDomain($domain, $authCode, isset($payload['years']) ? (int)$payload['years'] : 1, $extra);
    }

    protected function dispatchDomainRenew($provider, array $item, array $payload)
    {
        $domain = isset($payload['domain']) ? $payload['domain'] : (isset($payload['domain_name']) ? $payload['domain_name'] : '');
        if ($domain === '') {
            return array('success' => false, 'message' => 'Domain renew icin domain zorunludur.');
        }

        $extra = $this->buildProviderExtra($payload);
        return $provider->renewDomain($domain, isset($payload['years']) ? (int)$payload['years'] : 1, $extra);
    }

    protected function dispatchCustomerCreate($provider, array $item, array $payload)
    {
        $email = isset($payload['email']) ? trim((string)$payload['email']) : '';
        if ($email === '' && isset($payload['contact_profile']['email'])) {
            $email = trim((string)$payload['contact_profile']['email']);
        }
        if ($email === '') {
            return array('success' => false, 'message' => 'Customer email zorunludur.');
        }

        if ($item['provider_code'] === 'domainnameapi') {
            $domainName = isset($payload['domain_name']) ? $payload['domain_name'] : '';
            if (!$domainName || !NtRcApiContractGuard::isDomainNameApiTrDomain($domainName)) {
                return array('success' => false, 'message' => 'DomainNameAPI customer akisi sadece TR domain contact hazirligi icin kullanilir.');
            }

            if (method_exists($provider, 'createCustomer')) {
                return $provider->createCustomer($payload);
            }

            return array('success' => false, 'message' => 'DomainNameAPI contact hazirlik adapteri tanimli degil.');
        }

        if (method_exists($provider, 'searchCustomer')) {
            $search = $provider->searchCustomer($email);
            if (empty($search['success'])) {
                return $search;
            }
            if (!empty($search['found']) && !empty($search['provider_customer_id'])) {
                return array(
                    'success' => true,
                    'source' => 'existing_provider_customer',
                    'provider_customer_id' => $search['provider_customer_id'],
                    'provider_username' => isset($search['provider_username']) ? $search['provider_username'] : $email,
                    'data' => isset($search['data']) ? $search['data'] : array(),
                );
            }
        }

        if (method_exists($provider, 'createCustomer')) {
            return $provider->createCustomer($payload);
        }

        return array('success' => false, 'message' => 'Provider customer create adapter tanimli degil.');
    }

    protected function afterSuccess(array $item, array $payload, array $response)
    {
        if ($item['service_type'] === 'customer' && $item['action'] === 'create') {
            $this->afterCustomerSuccess($item, $payload, $response);
            return;
        }

        if (in_array($item['service_type'], array('domain', 'tr_domain')) && in_array($item['action'], array('register', 'transfer', 'renew'))) {
            $this->afterDomainSuccess($item, $payload, $response);
        }
    }

    protected function afterCustomerSuccess(array $item, array $payload, array $response)
    {
        if (empty($payload['id_customer'])) {
            return;
        }

        if ($item['provider_code'] === 'domainnameapi' && isset($response['source']) && $response['source'] === 'domain_contact_prepared') {
            NtRcProviderCustomerManager::markContactPrepared((int)$payload['id_customer'], $item['provider_code'], $response);
            return;
        }

        $providerCustomerId = $this->extractProviderCustomerId($response);
        if (!$providerCustomerId) {
            return;
        }

        $providerUsername = isset($response['provider_username']) ? $response['provider_username'] : (isset($payload['email']) ? $payload['email'] : null);
        NtRcProviderCustomerManager::markActive((int)$payload['id_customer'], $item['provider_code'], $providerCustomerId, $response, $providerUsername);
    }

    protected function afterDomainSuccess(array $item, array $payload, array $response)
    {
        $idService = !empty($item['id_service']) ? (int)$item['id_service'] : (isset($payload['id_service']) ? (int)$payload['id_service'] : 0);
        if ($idService <= 0) {
            return;
        }

        $providerOrderId = $this->extractFirstValue($response, array('provider_order_id', 'order-id', 'order_id', 'orderid', 'entityid'));
        $providerServiceId = $this->extractFirstValue($response, array('provider_service_id', 'service_id', 'ID', 'id'));
        if (!$providerServiceId) {
            $providerServiceId = $providerOrderId;
        }
        if (!$providerOrderId) {
            $providerOrderId = $providerServiceId;
        }

        $fields = array(
            'status' => $item['action'] === 'transfer' ? 'ready' : 'active',
            'provider_service_id' => $providerServiceId,
            'provider_order_id' => $providerOrderId,
            'provider_customer_id' => $this->extractFirstValue($response, array('customerid', 'customer-id', 'customer_id')),
            'provider_contact_id' => $this->extractFirstValue($response, array('contactid', 'contact-id', 'contact_id')),
            'expiry_date' => $this->extractExpiryDate($response),
        );

        if (empty($fields['provider_customer_id']) && isset($payload['extra']['provider_customer_id'])) {
            $fields['provider_customer_id'] = $payload['extra']['provider_customer_id'];
        }

        NtRcServiceRepository::markProvisioned($idService, $fields);
        $this->enqueueDomainLifecycleNotification($item, $payload, $fields, $idService);
    }

    protected function enqueueDomainLifecycleNotification(array $item, array $payload, array $fields, $idService)
    {
        $templateKey = $this->domainLifecycleTemplate((string)$item['action']);
        if (!$templateKey) {
            return;
        }

        try {
            $domain = isset($payload['domain']) ? $payload['domain'] : (isset($payload['domain_name']) ? $payload['domain_name'] : '');
            $years = isset($payload['years']) ? (int)$payload['years'] : 1;
            $dedupeSeed = !empty($fields['provider_order_id']) ? $fields['provider_order_id'] : (!empty($fields['provider_service_id']) ? $fields['provider_service_id'] : (int)$item['id_ntresellerclub_operation_queue']);
            $engine = new NtRcNotificationEngine();
            $result = $engine->enqueueServiceNotification(
                $templateKey,
                (int)$idService,
                array(
                    'domain_name' => $domain,
                    'action' => $item['action'],
                    'years' => $years,
                    'provider_order_id' => !empty($fields['provider_order_id']) ? $fields['provider_order_id'] : '',
                    'provider_service_id' => !empty($fields['provider_service_id']) ? $fields['provider_service_id'] : '',
                    'expiry_date' => !empty($fields['expiry_date']) ? $fields['expiry_date'] : '',
                    'queue_id' => (int)$item['id_ntresellerclub_operation_queue'],
                    'checked_at' => date('Y-m-d H:i:s'),
                ),
                'customer',
                2,
                'domain_lifecycle:' . $templateKey . ':' . (int)$idService . ':' . $dedupeSeed
            );

            if (empty($result['success'])) {
                $message = isset($result['message']) ? $this->safeText($result['message']) : 'Notification queue kaydi olusturulamadi.';
                NtRcLog::add('warning', 'operation_queue_processor', 'Domain lifecycle notification failed queue=' . (int)$item['id_ntresellerclub_operation_queue'] . ' ' . $message);
            }
        } catch (Exception $e) {
            NtRcLog::add('warning', 'operation_queue_processor', 'Domain lifecycle notification exception queue=' . (int)$item['id_ntresellerclub_operation_queue'] . ' ' . $this->safeText($e->getMessage()));
        }
    }

    protected function domainLifecycleTemplate($action)
    {
        $map = array(
            'register' => 'domain_registered',
            'transfer' => 'domain_transfer_started',
            'renew' => 'domain_renewed',
        );

        return isset($map[$action]) ? $map[$action] : null;
    }

    protected function afterFailure(array $item, array $payload, $error)
    {
        if (!in_array($item['service_type'], array('domain', 'tr_domain')) || !in_array($item['action'], array('register', 'transfer', 'renew'))) {
            return;
        }

        $willFail = ((int)$item['retry_count'] + 1) >= (int)$item['max_retries'];
        if (!$willFail) {
            return;
        }

        $idService = !empty($item['id_service']) ? (int)$item['id_service'] : (isset($payload['id_service']) ? (int)$payload['id_service'] : 0);
        if ($idService > 0) {
            NtRcServiceRepository::updateStatus($idService, 'error');
        }
    }

    protected function buildProviderExtra(array $payload)
    {
        $extra = isset($payload['extra']) && is_array($payload['extra']) ? $payload['extra'] : array();

        foreach (array('provider_order_id', 'provider_service_id', 'expiry_date', 'auto-renew', 'auto_renew', 'invoice-option', 'invoice_option') as $key) {
            if (isset($payload[$key]) && !isset($extra[$key])) {
                $extra[$key] = $payload[$key];
            }
        }

        if (!isset($extra['exp-date']) && !isset($extra['exp_date']) && !empty($payload['expiry_date'])) {
            $timestamp = strtotime($payload['expiry_date']);
            if ($timestamp) {
                $extra['exp-date'] = $timestamp;
            }
        }

        return $extra;
    }

    protected function withCurrentProviderCustomer(array $item, array $payload)
    {
        if (empty($payload['id_customer']) && !empty($item['id_customer'])) {
            $payload['id_customer'] = (int)$item['id_customer'];
        }

        if (empty($payload['id_customer'])) {
            return $payload;
        }

        $mapping = NtRcProviderCustomerManager::getMapping((int)$payload['id_customer'], $item['provider_code']);
        if (!$mapping || empty($mapping['provider_customer_id'])) {
            return $payload;
        }

        if (!isset($payload['extra']) || !is_array($payload['extra'])) {
            $payload['extra'] = array();
        }
        if (empty($payload['extra']['provider_customer_id'])) {
            $payload['extra']['provider_customer_id'] = $mapping['provider_customer_id'];
        }
        if (empty($payload['extra']['customer-id'])) {
            $payload['extra']['customer-id'] = $mapping['provider_customer_id'];
        }

        return $payload;
    }

    protected function isDomainNameApiContactReady(array $item, array $payload)
    {
        $idCustomer = !empty($payload['id_customer']) ? (int)$payload['id_customer'] : (!empty($item['id_customer']) ? (int)$item['id_customer'] : 0);
        if ($idCustomer <= 0) {
            return false;
        }

        $mapping = NtRcProviderCustomerManager::getMapping($idCustomer, 'domainnameapi');
        return $mapping && isset($mapping['status']) && $mapping['status'] === 'contact_ready';
    }

    protected function extractProviderCustomerId(array $response)
    {
        return $this->extractFirstValue($response, array('provider_customer_id', 'customer_id', 'id_customer', 'id'));
    }

    protected function extractFirstValue($data, array $keys)
    {
        if (!is_array($data)) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($data[$key]) && $data[$key] !== '') {
                return $data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $found = $this->extractFirstValue($value, $keys);
                if ($found !== null && $found !== '') {
                    return $found;
                }
            }
        }

        return null;
    }

    protected function extractExpiryDate(array $response)
    {
        $value = $this->extractFirstValue($response, array('expiry_date', 'expiration_date', 'ExpirationDate', 'Expiration', 'endtime'));
        if (!$value) {
            return null;
        }

        if (is_numeric($value)) {
            return gmdate('Y-m-d', (int)$value);
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    protected function sanitizeProviderResponse($response)
    {
        if (!is_array($response)) {
            return is_string($response) ? $this->safeText($response) : $response;
        }

        foreach (array('raw', 'last_url', 'api-key', 'api_key', 'ApiKey', 'passwd', 'password', 'Password', 'auth-code', 'auth_code', 'AuthCode', 'token', 'Token', 'credential', 'Credential', 'csr', 'CSR', 'private_key', 'private-key', 'certificate', 'certificate_raw', 'cert_raw') as $key) {
            if (isset($response[$key])) {
                unset($response[$key]);
            }
        }

        foreach ($response as $key => $value) {
            if (is_array($value)) {
                $response[$key] = $this->sanitizeProviderResponse($value);
            } elseif (is_string($value)) {
                $response[$key] = $this->safeText($value);
            }
        }

        return $response;
    }

    protected function decodePayload($payloadJson)
    {
        $payload = json_decode((string)$payloadJson, true);
        return is_array($payload) ? $payload : array();
    }

    protected function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|csr|private_key|private-key|certificate|certificate_raw|cert_raw)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
