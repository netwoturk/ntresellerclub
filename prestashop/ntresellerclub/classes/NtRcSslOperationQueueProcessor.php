<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcHostingOperationQueueProcessor.php';
require_once __DIR__ . '/providers/NtRcResellerClubSslAdapter.php';

class NtRcSslOperationQueueProcessor extends NtRcHostingOperationQueueProcessor
{
    protected function dispatch($provider, array $item, array $payload)
    {
        if ($item['service_type'] !== 'ssl') {
            return parent::dispatch($provider, $item, $payload);
        }

        if ($item['provider_code'] !== 'resellerclub') {
            return array('success' => false, 'message' => 'SSL islemleri sadece ResellerClub provider ile calisir.');
        }

        $map = array(
            'ssl/create' => 'createSsl',
            'ssl/renew' => 'renewSsl',
            'ssl/reissue' => 'reissueSsl',
            'ssl/cancel' => 'cancelSsl',
            'ssl/details' => 'getSslDetails',
            'ssl/download' => 'downloadSsl',
            'ssl/validation_status' => 'getValidationStatus',
        );

        $action = isset($item['action']) ? $item['action'] : '';
        $adapter = new NtRcResellerClubSslAdapter();
        if (!isset($map[$action]) || !method_exists($adapter, $map[$action])) {
            return array('success' => false, 'message' => 'SSL action icin provider metodu tanimli degil.');
        }

        $method = $map[$action];
        return $adapter->{$method}($payload);
    }

    protected function afterSuccess(array $item, array $payload, array $response)
    {
        if ($item['service_type'] !== 'ssl') {
            parent::afterSuccess($item, $payload, $response);
            return;
        }

        if (!in_array($item['action'], array('ssl/create', 'ssl/renew', 'ssl/reissue', 'ssl/cancel', 'ssl/details', 'ssl/download', 'ssl/validation_status'), true)) {
            return;
        }

        $idService = !empty($item['id_service']) ? (int)$item['id_service'] : (isset($payload['id_service']) ? (int)$payload['id_service'] : 0);
        if ($idService <= 0) {
            return;
        }

        $status = $item['action'] === 'ssl/cancel' ? 'cancelled' : 'active';
        $fields = array(
            'status' => $status,
            'provider_service_id' => $this->extractFirstValue($response, array('provider_service_id', 'service_id', 'ssl_id', 'certificate_id', 'entityid', 'id')),
            'provider_order_id' => $this->extractFirstValue($response, array('provider_order_id', 'order-id', 'order_id', 'orderid')),
            'ssl_certificate_number' => $this->extractFirstValue($response, array('ssl_certificate_number', 'certificate_number', 'cert_number', 'serial_number')),
            'expiry_date' => $this->extractExpiryDate($response),
        );

        NtRcServiceRepository::markProvisioned($idService, $fields);
        $this->enqueueSslLifecycleNotification($item, $payload, $fields, $idService);
    }

    protected function afterFailure(array $item, array $payload, $error)
    {
        if ($item['service_type'] !== 'ssl') {
            parent::afterFailure($item, $payload, $error);
            return;
        }

        if (!in_array($item['action'], array('ssl/create', 'ssl/renew', 'ssl/reissue', 'ssl/cancel', 'ssl/details', 'ssl/download', 'ssl/validation_status'), true)) {
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

    protected function enqueueSslLifecycleNotification(array $item, array $payload, array $fields, $idService)
    {
        $templateKey = $this->sslLifecycleTemplate((string)$item['action']);
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
                    'ssl_certificate_number' => !empty($fields['ssl_certificate_number']) ? $fields['ssl_certificate_number'] : '',
                    'expiry_date' => !empty($fields['expiry_date']) ? $fields['expiry_date'] : '',
                    'queue_id' => (int)$item['id_ntresellerclub_operation_queue'],
                    'checked_at' => date('Y-m-d H:i:s'),
                ),
                'customer',
                2,
                'ssl_lifecycle:' . $templateKey . ':' . (int)$idService . ':' . $dedupeSeed
            );
        } catch (Exception $e) {
            NtRcLog::add('warning', 'operation_queue_processor', 'SSL notification exception queue=' . (int)$item['id_ntresellerclub_operation_queue'] . ' ' . $this->safeText($e->getMessage()));
        }
    }

    protected function sslLifecycleTemplate($action)
    {
        $map = array(
            'ssl/create' => 'ssl_created',
            'ssl/renew' => 'ssl_renewed',
            'ssl/reissue' => 'ssl_reissue_required',
        );

        return isset($map[$action]) ? $map[$action] : null;
    }
}
