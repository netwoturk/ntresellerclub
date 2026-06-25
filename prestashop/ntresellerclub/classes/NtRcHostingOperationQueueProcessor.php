<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcOperationQueueProcessor.php';
require_once __DIR__ . '/providers/NtRcResellerClubHostingAdapter.php';

class NtRcHostingOperationQueueProcessor extends NtRcOperationQueueProcessor
{
    protected function dispatch($provider, array $item, array $payload)
    {
        if ($item['service_type'] !== 'hosting') {
            return parent::dispatch($provider, $item, $payload);
        }

        if ($item['provider_code'] !== 'resellerclub') {
            return array('success' => false, 'message' => 'Hosting islemleri sadece ResellerClub provider ile calisir.');
        }

        $map = array(
            'hosting/create' => 'createHosting',
            'hosting/renew' => 'renewHosting',
            'hosting/suspend' => 'suspendHosting',
            'hosting/unsuspend' => 'unsuspendHosting',
            'hosting/details' => 'getHostingDetails',
        );

        $action = isset($item['action']) ? $item['action'] : '';
        $adapter = new NtRcResellerClubHostingAdapter();
        if (!isset($map[$action]) || !method_exists($adapter, $map[$action])) {
            return array('success' => false, 'message' => 'Hosting action icin provider metodu tanimli degil.');
        }

        $method = $map[$action];
        return $adapter->{$method}($payload);
    }

    protected function afterSuccess(array $item, array $payload, array $response)
    {
        if ($item['service_type'] !== 'hosting') {
            parent::afterSuccess($item, $payload, $response);
            return;
        }

        if (!in_array($item['action'], array('hosting/create', 'hosting/renew', 'hosting/suspend', 'hosting/unsuspend'), true)) {
            return;
        }

        $idService = !empty($item['id_service']) ? (int)$item['id_service'] : (isset($payload['id_service']) ? (int)$payload['id_service'] : 0);
        if ($idService <= 0) {
            return;
        }

        $status = $item['action'] === 'hosting/suspend' ? 'suspended' : 'active';
        $fields = array(
            'status' => $status,
            'provider_service_id' => $this->extractFirstValue($response, array('provider_service_id', 'service_id', 'hosting_id', 'entityid', 'id')),
            'provider_order_id' => $this->extractFirstValue($response, array('provider_order_id', 'order-id', 'order_id', 'orderid')),
            'provider_customer_id' => $this->extractFirstValue($response, array('customerid', 'customer-id', 'customer_id')),
            'expiry_date' => $this->extractExpiryDate($response),
        );

        NtRcServiceRepository::markProvisioned($idService, $fields);
        $this->enqueueHostingLifecycleNotification($item, $payload, $fields, $idService);
    }

    protected function afterFailure(array $item, array $payload, $error)
    {
        if ($item['service_type'] !== 'hosting') {
            parent::afterFailure($item, $payload, $error);
            return;
        }

        if (!in_array($item['action'], array('hosting/create', 'hosting/renew', 'hosting/suspend', 'hosting/unsuspend'), true)) {
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

    protected function enqueueHostingLifecycleNotification(array $item, array $payload, array $fields, $idService)
    {
        $templateKey = $this->hostingLifecycleTemplate((string)$item['action']);
        if (!$templateKey) {
            return;
        }

        try {
            $dedupeSeed = !empty($fields['provider_order_id']) ? $fields['provider_order_id'] : (!empty($fields['provider_service_id']) ? $fields['provider_service_id'] : (int)$item['id_ntresellerclub_operation_queue']);
            $engine = new NtRcNotificationEngine();
            $engine->enqueueServiceNotification(
                $templateKey,
                (int)$idService,
                array(
                    'domain_name' => isset($payload['domain_name']) ? $payload['domain_name'] : '',
                    'action' => $item['action'],
                    'provider_order_id' => !empty($fields['provider_order_id']) ? $fields['provider_order_id'] : '',
                    'provider_service_id' => !empty($fields['provider_service_id']) ? $fields['provider_service_id'] : '',
                    'expiry_date' => !empty($fields['expiry_date']) ? $fields['expiry_date'] : '',
                    'queue_id' => (int)$item['id_ntresellerclub_operation_queue'],
                    'checked_at' => date('Y-m-d H:i:s'),
                ),
                'customer',
                2,
                'hosting_lifecycle:' . $templateKey . ':' . (int)$idService . ':' . $dedupeSeed
            );
        } catch (Exception $e) {
            NtRcLog::add('warning', 'operation_queue_processor', 'Hosting notification exception queue=' . (int)$item['id_ntresellerclub_operation_queue'] . ' ' . $this->safeText($e->getMessage()));
        }
    }

    protected function hostingLifecycleTemplate($action)
    {
        $map = array(
            'hosting/create' => 'hosting_created',
            'hosting/renew' => 'hosting_renewed',
        );

        return isset($map[$action]) ? $map[$action] : null;
    }
}
