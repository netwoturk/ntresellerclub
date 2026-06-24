<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcLog.php';
require_once __DIR__ . '/NtRcApiContractGuard.php';

class NtRcOperationQueueManager
{
    public static function enqueue($providerCode, $serviceType, $action, array $payload = array(), $idOrder = null, $idCustomer = null, $idService = null, $maxRetries = 3)
    {
        if (!$providerCode || !$serviceType || !$action) {
            return array('success' => false, 'message' => 'Queue icin provider, service type ve action zorunludur.');
        }

        $contract = NtRcApiContractGuard::validate($providerCode, $serviceType, $action, $payload);
        if (empty($contract['success'])) {
            NtRcLog::add('warning', 'operation_queue_contract', 'Denied provider=' . $providerCode . ' service=' . $serviceType . ' action=' . $action . ' message=' . $contract['message']);
            return $contract;
        }

        $data = array(
            'id_order' => $idOrder ? (int)$idOrder : null,
            'id_customer' => $idCustomer ? (int)$idCustomer : null,
            'id_service' => $idService ? (int)$idService : null,
            'provider_code' => pSQL($providerCode),
            'service_type' => pSQL($serviceType),
            'action' => pSQL($action),
            'payload_json' => pSQL(json_encode($payload)),
            'status' => pSQL('pending'),
            'retry_count' => 0,
            'max_retries' => (int)$maxRetries,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $ok = Db::getInstance()->insert('ntresellerclub_operation_queue', $data);
        if (!$ok) {
            NtRcLog::add('error', 'operation_queue', 'Queue insert failed action=' . $action);
            return array('success' => false, 'message' => 'Queue kaydi olusturulamadi.');
        }

        return array('success' => true, 'queue_id' => (int)Db::getInstance()->Insert_ID());
    }

    public static function pending($limit = 10)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE status="pending" ORDER BY id_ntresellerclub_operation_queue ASC LIMIT ' . (int)$limit
        );
    }

    public static function markProcessing($idQueue)
    {
        return Db::getInstance()->update('ntresellerclub_operation_queue', array(
            'status' => pSQL('processing'),
            'locked_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_operation_queue=' . (int)$idQueue);
    }

    public static function markDone($idQueue, array $response = array())
    {
        return Db::getInstance()->update('ntresellerclub_operation_queue', array(
            'status' => pSQL('done'),
            'response_json' => pSQL(json_encode($response)),
            'processed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_operation_queue=' . (int)$idQueue);
    }

    public static function markRetryOrFailed(array $item, $errorMessage)
    {
        $idQueue = (int)$item['id_ntresellerclub_operation_queue'];
        $retry = (int)$item['retry_count'] + 1;
        $max = (int)$item['max_retries'];
        $status = $retry >= $max ? 'failed' : 'pending';

        return Db::getInstance()->update('ntresellerclub_operation_queue', array(
            'status' => pSQL($status),
            'retry_count' => $retry,
            'last_error' => pSQL($errorMessage),
            'locked_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_operation_queue=' . $idQueue);
    }

    public static function last($limit = 20)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` ORDER BY id_ntresellerclub_operation_queue DESC LIMIT ' . (int)$limit
        );
    }
}
