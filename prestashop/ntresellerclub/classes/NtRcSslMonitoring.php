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

        $active = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE service_type="ssl" AND status="active"'
        );
        $failedQueue = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE service_type="ssl" AND status="failed"'
        );
        $pendingQueue = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE service_type="ssl" AND status IN ("pending", "processing")'
        );
        $pendingProvisioning = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE service_type="ssl" AND status IN ("pending", "provisioning")'
        );
        $expiring = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` '
            . 'WHERE service_type="ssl" AND status IN ("active", "renewal_due") '
            . 'AND expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ' . (int)$days . ' DAY)'
        );
        $providerCredit = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE service_type="ssl" AND status="provider_credit_required"'
        );

        return array(
            'active_ssl_count' => $active,
            'pending_ssl_queue' => $pendingQueue,
            'failed_ssl_queue' => $failedQueue,
            'ssl_expiring_count' => $expiring,
            'ssl_provider_credit_required_count' => $providerCredit,
            'ssl_active_count' => $active,
            'ssl_failed_queue' => $failedQueue,
            'ssl_pending_provisioning' => $pendingProvisioning,
        );
    }
}
