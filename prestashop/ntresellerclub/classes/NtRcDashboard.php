<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/providers/NtRcProviderRegistry.php';
require_once __DIR__ . '/providers/NtRcTldRouteManager.php';
require_once __DIR__ . '/NtRcStatisticsEngine.php';

class NtRcDashboard
{
    public static function summary()
    {
        return array(
            'providers' => self::providerStats(),
            'services' => self::serviceStats(),
            'hosting' => self::hostingStats(),
            'ssl' => self::sslStats(),
            'billing' => self::billingStats(),
            'routes' => NtRcTldRouteManager::all(),
            'notices' => self::noticeStats(),
            'logs' => self::latestLogs(),
        );
    }

    public static function providerStats()
    {
        return NtRcProviderRegistry::all(false);
    }

    public static function serviceStats()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT provider_code, service_type, status, COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` GROUP BY provider_code, service_type, status ORDER BY provider_code ASC, service_type ASC, status ASC'
        );
        return is_array($rows) ? $rows : array();
    }

    public static function noticeStats()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT notice_type, days_before, COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'ntresellerclub_notice` GROUP BY notice_type, days_before ORDER BY notice_type ASC, days_before DESC'
        );
        return is_array($rows) ? $rows : array();
    }

    public static function hostingStats()
    {
        $statistics = new NtRcStatisticsEngine();
        return $statistics->hostingSummary();
    }

    public static function sslStats()
    {
        $statistics = new NtRcStatisticsEngine();
        return $statistics->sslSummary();
    }

    public static function billingStats()
    {
        $statistics = new NtRcStatisticsEngine();
        return $statistics->billingSummary();
    }

    public static function latestLogs($limit = 10)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_log` ORDER BY id_ntresellerclub_log DESC LIMIT ' . (int)$limit
        );
        return is_array($rows) ? $rows : array();
    }
}
