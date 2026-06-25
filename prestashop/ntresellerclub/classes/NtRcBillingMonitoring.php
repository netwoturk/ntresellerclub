<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcBillingEventManager.php';

class NtRcBillingMonitoring
{
    public static function summary()
    {
        $metrics = NtRcBillingEventManager::metrics();

        $metrics['service_payment_required_count'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE status="payment_required"'
        );
        $metrics['service_provider_credit_required_count'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE status="provider_credit_required"'
        );
        $metrics['queue_provider_credit_required_count'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE status="provider_credit_required"'
        );

        return $metrics;
    }
}
