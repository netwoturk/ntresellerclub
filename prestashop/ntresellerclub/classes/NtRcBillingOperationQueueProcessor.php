<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcHostingOperationQueueProcessor.php';
require_once __DIR__ . '/NtRcBillingEventManager.php';
require_once __DIR__ . '/NtRcNotificationEngine.php';

class NtRcBillingOperationQueueProcessor extends NtRcHostingOperationQueueProcessor
{
    protected function afterFailure(array $item, array $payload, $error)
    {
        if ($this->isProviderCreditError($error)) {
            $this->markProviderCreditRequired($item, $payload, $error);
            return;
        }

        parent::afterFailure($item, $payload, $error);
    }

    protected function markProviderCreditRequired(array $item, array $payload, $error)
    {
        $idQueue = isset($item['id_ntresellerclub_operation_queue']) ? (int)$item['id_ntresellerclub_operation_queue'] : 0;
        $idService = !empty($item['id_service']) ? (int)$item['id_service'] : (isset($payload['id_service']) ? (int)$payload['id_service'] : 0);
        $message = $this->safeText($error);

        if ($idQueue > 0) {
            Db::getInstance()->update('ntresellerclub_operation_queue', array(
                'status' => pSQL('provider_credit_required'),
                'last_error' => pSQL($message),
                'lock_token' => null,
                'locked_at' => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ), 'id_ntresellerclub_operation_queue=' . (int)$idQueue);
        }

        if ($idService > 0) {
            NtRcServiceRepository::updateStatus($idService, 'provider_credit_required');
        }

        $context = array(
            'id_order' => !empty($item['id_order']) ? (int)$item['id_order'] : (isset($payload['id_order']) ? (int)$payload['id_order'] : null),
            'id_customer' => !empty($item['id_customer']) ? (int)$item['id_customer'] : (isset($payload['id_customer']) ? (int)$payload['id_customer'] : null),
            'id_service' => $idService,
            'provider_code' => isset($item['provider_code']) ? $item['provider_code'] : '',
            'service_type' => isset($item['service_type']) ? $item['service_type'] : '',
        );

        NtRcBillingEventManager::record('provider_credit_required', 'action_required', $context, $message, array('queue_id' => $idQueue, 'action' => isset($item['action']) ? $item['action'] : ''));
        $this->notifyProviderCreditRequired($context, $idQueue, $message);
    }

    protected function notifyProviderCreditRequired(array $context, $idQueue, $message)
    {
        try {
            $engine = new NtRcNotificationEngine();
            $engine->enqueueAdminNotification(
                'queue_failed_admin',
                array(
                    'queue_id' => (int)$idQueue,
                    'provider_code' => isset($context['provider_code']) ? $context['provider_code'] : '',
                    'provider_status' => 'provider_credit_required',
                    'error_message' => $message,
                    'checked_at' => date('Y-m-d H:i:s'),
                ),
                'admin',
                'provider_credit_required:' . (int)$idQueue . ':' . date('Y-m-d'),
                1
            );
        } catch (Exception $e) {
            NtRcLog::add('warning', 'billing_queue_processor', 'Provider credit notification failed queue=' . (int)$idQueue . ' ' . $this->safeText($e->getMessage()));
        }
    }

    protected function isProviderCreditError($error)
    {
        $text = strtolower((string)$error);
        return (bool)preg_match('/insufficient|not enough|low balance|balance|credit required|provider credit|kredi|bakiye|yetersiz/', $text);
    }
}
