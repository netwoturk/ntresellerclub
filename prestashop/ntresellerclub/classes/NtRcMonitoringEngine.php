<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcHealthChecker.php';
require_once __DIR__ . '/NtRcStatisticsEngine.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcMonitoringEngine
{
    public function run($context = 'cron')
    {
        NtRcRuntimeGuard::beforeHeavyProcess('monitoring_engine');
        NtRcInstaller::ensureMonitoringSchema();

        $startedAt = microtime(true);
        if ($context === 'cron') {
            Configuration::updateValue('NTRC_LAST_CRON_AT', date('Y-m-d H:i:s'));
        }

        $healthChecker = new NtRcHealthChecker();
        $statisticsEngine = new NtRcStatisticsEngine();

        $health = $healthChecker->checkAll($context);
        $statistics = $statisticsEngine->snapshot();
        $durationMs = (int)round((microtime(true) - $startedAt) * 1000);

        NtRcLog::add(
            'info',
            'monitoring_engine',
            'Monitoring snapshot context=' . pSQL($context) . ' duration_ms=' . (int)$durationMs
        );

        return array(
            'success' => true,
            'context' => $context,
            'duration_ms' => $durationMs,
            'health' => $health,
            'statistics' => $statistics,
        );
    }
}
