<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcLog.php';
require_once __DIR__ . '/NtRcApiContractGuard.php';

class NtRcOperationQueueManager
{
    protected static $schemaChecked = false;

    public static function enqueue($providerCode, $serviceType, $action, array $payload = array(), $idOrder = null, $idCustomer = null, $idService = null, $maxRetries = 3, $priority = 3)
    {
        self::ensureSchema();

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
            'priority' => self::normalizePriority($priority),
            'payload_json' => pSQL(json_encode($payload)),
            'status' => pSQL('pending'),
            'retry_count' => 0,
            'max_retries' => (int)$maxRetries,
            'lock_token' => null,
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
        self::ensureSchema();
        $limit = self::normalizeLimit($limit);

        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE status="pending" ORDER BY priority ASC, id_ntresellerclub_operation_queue ASC LIMIT ' . (int)$limit
        );
    }

    public static function markProcessing($idQueue)
    {
        self::ensureSchema();

        $idQueue = (int)$idQueue;
        if ($idQueue <= 0) {
            return false;
        }

        $token = self::generateLockToken($idQueue);
        $now = date('Y-m-d H:i:s');
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` SET '
            . 'status="processing", '
            . 'lock_token="' . pSQL($token) . '", '
            . 'locked_at="' . pSQL($now) . '", '
            . 'updated_at="' . pSQL($now) . '" '
            . 'WHERE id_ntresellerclub_operation_queue=' . $idQueue . ' '
            . 'AND status="pending" '
            . 'AND (lock_token IS NULL OR lock_token="")';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        $storedToken = Db::getInstance()->getValue(
            'SELECT lock_token FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE id_ntresellerclub_operation_queue=' . $idQueue . ' AND status="processing"'
        );

        return $storedToken === $token ? $token : false;
    }

    public static function markDone($idQueue, array $response = array(), $lockToken = null)
    {
        self::ensureSchema();

        return Db::getInstance()->update('ntresellerclub_operation_queue', array(
            'status' => pSQL('done'),
            'response_json' => pSQL(json_encode($response)),
            'lock_token' => null,
            'locked_at' => null,
            'processed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), self::queueWhere((int)$idQueue, $lockToken));
    }

    public static function markRetryOrFailed(array $item, $errorMessage, $lockToken = null)
    {
        self::ensureSchema();

        $idQueue = (int)$item['id_ntresellerclub_operation_queue'];
        $retry = (int)$item['retry_count'] + 1;
        $max = (int)$item['max_retries'];
        $status = $retry >= $max ? 'failed' : 'pending';

        return Db::getInstance()->update('ntresellerclub_operation_queue', array(
            'status' => pSQL($status),
            'retry_count' => $retry,
            'last_error' => pSQL($errorMessage),
            'lock_token' => null,
            'locked_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ), self::queueWhere($idQueue, $lockToken));
    }

    public static function retryFailed($idQueue)
    {
        self::ensureSchema();

        $item = self::get($idQueue);
        if (!$item || $item['status'] !== 'failed') {
            return array('success' => false, 'message' => 'Failed queue kaydi bulunamadi.');
        }

        $payload = self::decodePayload($item['payload_json']);
        $contract = NtRcApiContractGuard::validate($item['provider_code'], $item['service_type'], $item['action'], $payload);
        if (empty($contract['success'])) {
            NtRcLog::add('warning', 'operation_queue_contract', 'Retry denied queue=' . (int)$idQueue . ' message=' . $contract['message']);
            return $contract;
        }

        $ok = Db::getInstance()->update('ntresellerclub_operation_queue', array(
            'status' => pSQL('pending'),
            'retry_count' => 0,
            'last_error' => null,
            'lock_token' => null,
            'locked_at' => null,
            'processed_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_operation_queue=' . (int)$idQueue . ' AND status="failed"');

        return array('success' => (bool)$ok, 'queue_id' => (int)$idQueue);
    }

    public static function cleanupDone($days = 30)
    {
        self::ensureSchema();

        $days = (int)$days;
        if ($days <= 0) {
            $days = 30;
        }

        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` '
            . 'WHERE status="done" '
            . 'AND processed_at IS NOT NULL '
            . 'AND processed_at < DATE_SUB(NOW(), INTERVAL ' . (int)$days . ' DAY)';

        return Db::getInstance()->execute($sql);
    }

    public static function get($idQueue)
    {
        self::ensureSchema();

        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE id_ntresellerclub_operation_queue=' . (int)$idQueue
        );
    }

    public static function last($limit = 20)
    {
        self::ensureSchema();
        $limit = self::normalizeLimit($limit);

        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` ORDER BY id_ntresellerclub_operation_queue DESC LIMIT ' . (int)$limit
        );
    }

    protected static function ensureSchema()
    {
        if (self::$schemaChecked) {
            return true;
        }

        self::addColumnIfMissing('priority', 'INT UNSIGNED NOT NULL DEFAULT 3 AFTER `action`');
        self::addColumnIfMissing('lock_token', 'VARCHAR(128) DEFAULT NULL AFTER `last_error`');
        self::$schemaChecked = true;

        return true;
    }

    protected static function addColumnIfMissing($column, $definition)
    {
        $table = _DB_PREFIX_ . 'ntresellerclub_operation_queue';
        $exists = Db::getInstance()->getValue('SHOW COLUMNS FROM `' . pSQL($table) . '` LIKE "' . pSQL($column) . '"');
        if ($exists) {
            return true;
        }

        return Db::getInstance()->execute('ALTER TABLE `' . pSQL($table) . '` ADD `' . pSQL($column) . '` ' . $definition);
    }

    protected static function queueWhere($idQueue, $lockToken = null)
    {
        $where = 'id_ntresellerclub_operation_queue=' . (int)$idQueue;
        if ($lockToken !== null && $lockToken !== '') {
            $where .= ' AND lock_token="' . pSQL($lockToken) . '"';
        }
        return $where;
    }

    protected static function generateLockToken($idQueue)
    {
        return sha1((int)$idQueue . '|' . uniqid('', true) . '|' . mt_rand());
    }

    protected static function normalizePriority($priority)
    {
        $priority = (int)$priority;
        if ($priority < 1) {
            return 1;
        }
        if ($priority > 4) {
            return 4;
        }
        return $priority;
    }

    protected static function normalizeLimit($limit)
    {
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 10;
        }
        if ($limit > 25) {
            $limit = 25;
        }
        return $limit;
    }

    protected static function decodePayload($payloadJson)
    {
        $payload = json_decode((string)$payloadJson, true);
        return is_array($payload) ? $payload : array();
    }
}
