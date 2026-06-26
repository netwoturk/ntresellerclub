<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcAdminDataProviderInterface.php';
require_once dirname(__DIR__) . '/NtRcProductionReadinessVerifier.php';
require_once dirname(__DIR__) . '/NtRcStatisticsEngine.php';

class NtRcAdminDashboardDataProvider implements NtRcAdminDataProviderInterface
{
    public function getSummary()
    {
        $statistics = new NtRcStatisticsEngine();

        return array(
            'readiness' => NtRcProductionReadinessVerifier::summary(),
            'queue' => $statistics->queueSummary(),
            'hosting' => $statistics->hostingSummary(),
            'ssl' => $statistics->sslSummary(),
            'billing' => $statistics->billingSummary(),
        );
    }
}
