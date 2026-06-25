<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcHostingMonitoring
{
    public static function summary()
    {
        return array(
            'active_hosting_count' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE service_type="hosting" AND status="active"'
            ),
            'failed_hosting_queue' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE service_type="hosting" AND status="failed"'
            ),
            'pending_hosting_provisioning' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE service_type="hosting" AND status IN ("pending", "provisioning")'
            ),
        );
    }
}
