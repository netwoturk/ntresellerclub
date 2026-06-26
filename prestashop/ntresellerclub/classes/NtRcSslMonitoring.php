<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';

class NtRcSslMonitoring
{
    public static function summary($days = 30)
    {
        NtRcInstaller::ensureServiceSchema();
        NtRcInstaller::ensureOperationQueueSchema();

        $days = (int)$days;
        if ($days <= 0) {
            $days = 30;
        }

        return array(
            'ssl_active_count' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE service_type="ssl" AND status="active"'
            ),
            'ssl_failed_queue' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE service_type="ssl" AND status="failed"'
            ),
            'ssl_pending_provisioning' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE service_type="ssl" AND status IN ("pending", "provisioning")'
            ),
            'ssl_expiring_count' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` '
                . 'WHERE service_type="ssl" AND status IN ("active", "renewal_due") '
                . 'AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ' . (int)$days . ' DAY)'
            ),
        );
    }
}
