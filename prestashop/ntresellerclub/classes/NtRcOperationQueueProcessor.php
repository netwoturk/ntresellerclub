<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcOperationQueueManager.php';
require_once __DIR__ . '/NtRcApiContractGuard.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcProviderCustomerManager.php';
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
                NtRcOperationQueueManager::markRetryOrFailed($item, $e->getMessage());
                NtRcLog::add('error', 'operation_queue_processor', 'Exception queue=' . (int)$item['id_ntresellerclub_operation_queue'] . ' ' . $e->getMessage());
                $results[] = array('success' => false, 'queue_id' => (int)$item['id_ntresellerclub_operation_queue'], 'error' => $e->getMessage());
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
                NtRcOperationQueueManager::markRetryOrFailed($item, $contract['message'], $lockToken);
                return array('success' => false, 'queue_id' => $idQueue, 'message' => $contract['message']);
            }

            $provider = NtRcProviderFactory::make($item['provider_code']);
            if (!$provider) {
                NtRcOperationQueueManager::markRetryOrFailed($item, 'Provider olusturulamadi.', $lockToken);
                return array('success' => false, 'queue_id' => $idQueue, 'message' => 'Provider olusturulamadi.');
            }

            $response = $this->sanitizeProviderResponse($this->dispatch($provider, $item, $payload));
            if (!empty($response['success'])) {
                $this->afterSuccess($item, $payload, $response);
                NtRcOperationQueueManager::markDone($idQueue, $response, $lockToken);
                NtRcLog::add('info', 'operation_queue_processor', 'Queue done id=' . $idQueue . ' action=' . $item['action']);
                return array('success' => true, 'queue_id' => $idQueue, 'action' => $item['action']);
            }

            $error = isset($response['error']) ? $response['error'] : (isset($response['message']) ? $response['message'] : 'Provider islemi basarisiz.');
            NtRcOperationQueueManager::markRetryOrFailed($item, $error, $lockToken);
            NtRcLog::add('error', 'operation_queue_processor', 'Queue failed id=' . $idQueue . ' error=' . $error);
            return array('success' => false, 'queue_id' => $idQueue, 'error' => $error);
        } catch (Exception $e) {
            NtRcOperationQueueManager::markRetryOrFailed($item, $e->getMessage(), $lockToken);
            NtRcLog::add('error', 'operation_queue_processor', 'Exception queue=' . $idQueue . ' ' . $e->getMessage());
            return array('success' => false, 'queue_id' => $idQueue, 'error' => $e->getMessage());
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

        if ($action === 'details' && $domain && method_exists($provider, 'getDetails')) {
            return $provider->getDetails($domain);
        }

        if ($action === 'renew' && $domain && method_exists($provider, 'renewDomain')) {
            return $provider->renewDomain($domain, $years);
        }

        if ($action === 'register' && $domain && method_exists($provider, 'registerDomain')) {
            $contact = isset($payload['contact']) && is_array($payload['contact']) ? $payload['contact'] : array();
            $nameservers = isset($payload['nameservers']) && is_array($payload['nameservers']) ? $payload['nameservers'] : array();
            $extra = isset($payload['extra']) && is_array($payload['extra']) ? $payload['extra'] : array();
            return $provider->registerDomain($domain, $years, $contact, $nameservers, $extra);
        }

        if ($action === 'transfer' && $domain && method_exists($provider, 'transferDomain')) {
            $authCode = isset($payload['auth_code']) ? $payload['auth_code'] : '';
            return $provider->transferDomain($domain, $authCode, $years);
        }

        return array('success' => false, 'message' => 'Bu action icin provider metodu tanimli degil.');
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
                return array('success' => false, 'message' => 'DomainNameAPI customer akisi sadece TR domain icin kullanilir.');
            }
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
        if ($item['service_type'] !== 'customer' || $item['action'] !== 'create') {
            return;
        }

        $providerCustomerId = $this->extractProviderCustomerId($response);
        if (!$providerCustomerId || empty($payload['id_customer'])) {
            return;
        }

        NtRcProviderCustomerManager::markActive((int)$payload['id_customer'], $item['provider_code'], $providerCustomerId, $response);
    }

    protected function extractProviderCustomerId(array $response)
    {
        foreach (array('provider_customer_id', 'customer_id', 'id_customer', 'id') as $key) {
            if (!empty($response[$key])) {
                return $response[$key];
            }
        }

        if (!empty($response['data']) && is_array($response['data'])) {
            foreach (array('provider_customer_id', 'customer_id', 'id_customer', 'id') as $key) {
                if (!empty($response['data'][$key])) {
                    return $response['data'][$key];
                }
            }
        }

        return null;
    }

    protected function sanitizeProviderResponse($response)
    {
        if (!is_array($response)) {
            return $response;
        }

        foreach (array('raw', 'last_url', 'api-key', 'api_key', 'passwd', 'password', 'auth-code', 'auth_code') as $key) {
            if (isset($response[$key])) {
                unset($response[$key]);
            }
        }

        foreach ($response as $key => $value) {
            if (is_array($value)) {
                $response[$key] = $this->sanitizeProviderResponse($value);
            }
        }

        return $response;
    }

    protected function decodePayload($payloadJson)
    {
        $payload = json_decode((string)$payloadJson, true);
        return is_array($payload) ? $payload : array();
    }
}
