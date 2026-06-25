<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcBillingEventManager
{
    protected static $schemaChecked = false;

    public static function record($eventType, $eventStatus, array $context = array(), $message = '', array $metadata = array())
    {
        self::ensureSchema();

        $data = array(
            'id_order' => !empty($context['id_order']) ? (int)$context['id_order'] : null,
            'id_customer' => !empty($context['id_customer']) ? (int)$context['id_customer'] : null,
            'id_service' => !empty($context['id_service']) ? (int)$context['id_service'] : null,
            'provider_code' => !empty($context['provider_code']) ? pSQL($context['provider_code']) : null,
            'service_type' => !empty($context['service_type']) ? pSQL($context['service_type']) : null,
            'event_type' => pSQL(self::safeKey($eventType)),
            'event_status' => pSQL(self::safeKey($eventStatus)),
            'message' => pSQL(self::safeText($message)),
            'metadata_json' => pSQL(json_encode(self::safeData($metadata))),
            'created_at' => date('Y-m-d H:i:s'),
        );

        $ok = Db::getInstance()->insert('ntresellerclub_billing_event', $data);
        return array('success' => (bool)$ok, 'event_id' => $ok ? (int)Db::getInstance()->Insert_ID() : 0);
    }

    public static function metrics()
    {
        self::ensureSchema();
        $today = date('Y-m-d');

        return array(
            'payment_required_count' => self::countByEvent('payment_required'),
            'provider_credit_required_count' => self::countByEvent('provider_credit_required'),
            'billing_failed_count' => self::countByStatus('failed'),
            'unpaid_renewal_count' => self::countByEvent('renewal_payment_required'),
            'provisioning_queued_today' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_billing_event` '
                . 'WHERE event_type="provisioning_queued" AND DATE(created_at)="' . pSQL($today) . '"'
            ),
            'order_skipped_count' => self::countByEvent('duplicate_skipped') + self::countByEvent('order_not_paid'),
        );
    }

    public static function ensureSchema()
    {
        if (self::$schemaChecked) {
            return true;
        }

        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'ntresellerclub_billing_event` ('
            . '`id_ntresellerclub_billing_event` INT UNSIGNED NOT NULL AUTO_INCREMENT,'
            . '`id_order` INT UNSIGNED DEFAULT NULL,'
            . '`id_customer` INT UNSIGNED DEFAULT NULL,'
            . '`id_service` INT UNSIGNED DEFAULT NULL,'
            . '`provider_code` VARCHAR(64) DEFAULT NULL,'
            . '`service_type` VARCHAR(50) DEFAULT NULL,'
            . '`event_type` VARCHAR(64) NOT NULL,'
            . '`event_status` VARCHAR(32) NOT NULL,'
            . '`message` TEXT DEFAULT NULL,'
            . '`metadata_json` MEDIUMTEXT DEFAULT NULL,'
            . '`created_at` DATETIME NOT NULL,'
            . 'PRIMARY KEY (`id_ntresellerclub_billing_event`),'
            . 'KEY `idx_billing_event_order` (`id_order`),'
            . 'KEY `idx_billing_event_customer` (`id_customer`),'
            . 'KEY `idx_billing_event_service` (`id_service`),'
            . 'KEY `idx_billing_event_provider` (`provider_code`),'
            . 'KEY `idx_billing_event_type` (`event_type`),'
            . 'KEY `idx_billing_event_status` (`event_status`),'
            . 'KEY `idx_billing_event_created` (`created_at`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        self::$schemaChecked = (bool)Db::getInstance()->execute($sql);
        return self::$schemaChecked;
    }

    protected static function countByEvent($eventType)
    {
        return (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_billing_event` WHERE event_type="' . pSQL(self::safeKey($eventType)) . '"'
        );
    }

    protected static function countByStatus($eventStatus)
    {
        return (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_billing_event` WHERE event_status="' . pSQL(self::safeKey($eventStatus)) . '"'
        );
    }

    protected static function safeKey($key)
    {
        return preg_replace('/[^a-z0-9_\\-]/i', '', strtolower((string)$key));
    }

    public static function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|card|cc)=([^&\\s]+)/i', '$1=***', (string)$text);
    }

    public static function safeData($data)
    {
        if (!is_array($data)) {
            return is_string($data) ? self::safeText($data) : $data;
        }

        foreach (array('raw', 'request', 'response', 'transaction_raw', 'api-key', 'api_key', 'password', 'passwd', 'auth-code', 'auth_code', 'token', 'credential', 'card', 'cc') as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }

        foreach ($data as $key => $value) {
            $data[$key] = is_array($value) ? self::safeData($value) : (is_string($value) ? self::safeText($value) : $value);
        }

        return $data;
    }
}
