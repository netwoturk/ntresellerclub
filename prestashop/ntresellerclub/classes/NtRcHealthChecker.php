<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcStatisticsEngine.php';
require_once __DIR__ . '/providers/NtRcProviderRegistry.php';

class NtRcHealthChecker
{
    public function checkAll($context = 'cron')
    {
        NtRcInstaller::ensureMonitoringSchema();

        $runtime = $this->checkRuntime($context);
        $providers = $this->checkProviders();

        return array(
            'success' => true,
            'checked_at' => date('Y-m-d H:i:s'),
            'runtime' => $runtime,
            'providers' => $providers,
        );
    }

    public function checkRuntime($context = 'cron')
    {
        NtRcInstaller::ensureMonitoringSchema();

        $statistics = new NtRcStatisticsEngine();
        $queue = $statistics->queueSummary();
        $status = 'ok';

        if ((int)$queue['failed'] > 0) {
            $status = 'warning';
        }
        if ((int)$queue['processing'] > NtRcRuntimeGuard::cronBatchLimit(10)) {
            $status = 'warning';
        }

        $row = array(
            'context' => $context,
            'status' => $status,
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage_bytes' => memory_get_usage(true),
            'memory_peak_bytes' => memory_get_peak_usage(true),
            'max_execution_time' => (int)ini_get('max_execution_time'),
            'batch_limit' => NtRcRuntimeGuard::cronBatchLimit(10),
            'php_sapi' => PHP_SAPI,
            'queue_pending' => (int)$queue['pending'],
            'queue_processing' => (int)$queue['processing'],
            'queue_failed' => (int)$queue['failed'],
            'last_cron_at' => Configuration::get('NTRC_LAST_CRON_AT') ?: null,
            'checked_at' => date('Y-m-d H:i:s'),
        );

        $this->insertRuntimeHealth($row);
        return $row;
    }

    public function checkProviders()
    {
        NtRcInstaller::ensureMonitoringSchema();

        $rows = array();
        $providers = NtRcProviderRegistry::all(false);
        $statistics = new NtRcStatisticsEngine();

        foreach ((array)$providers as $provider) {
            if (empty($provider['provider_code'])) {
                continue;
            }

            $startedAt = microtime(true);
            $queue = $statistics->queueSummary($provider['provider_code']);
            $status = $this->providerStatus($provider, $queue);
            $row = array(
                'provider_code' => $provider['provider_code'],
                'status' => $status,
                'is_enabled' => (int)$provider['is_enabled'],
                'is_licensed' => (int)$provider['is_licensed'],
                'queue_pending' => (int)$queue['pending'],
                'queue_failed' => (int)$queue['failed'],
                'last_error' => $this->latestProviderError($provider['provider_code']),
                'response_time_ms' => (int)round((microtime(true) - $startedAt) * 1000),
                'checked_at' => date('Y-m-d H:i:s'),
            );

            $this->insertProviderHealth($row);
            $rows[] = $row;
        }

        return $rows;
    }

    protected function providerStatus(array $provider, array $queue)
    {
        if ((int)$provider['is_enabled'] !== 1) {
            return 'disabled';
        }
        if ((int)$provider['is_licensed'] !== 1) {
            return 'unlicensed';
        }
        if ((int)$queue['failed'] > 0) {
            return 'warning';
        }
        return 'ok';
    }

    protected function insertRuntimeHealth(array $row)
    {
        return Db::getInstance()->insert('ntresellerclub_runtime_health', array(
            'context' => pSQL($row['context']),
            'status' => pSQL($row['status']),
            'memory_limit' => pSQL($row['memory_limit']),
            'memory_usage_bytes' => (int)$row['memory_usage_bytes'],
            'memory_peak_bytes' => (int)$row['memory_peak_bytes'],
            'max_execution_time' => (int)$row['max_execution_time'],
            'batch_limit' => (int)$row['batch_limit'],
            'php_sapi' => pSQL($row['php_sapi']),
            'queue_pending' => (int)$row['queue_pending'],
            'queue_processing' => (int)$row['queue_processing'],
            'queue_failed' => (int)$row['queue_failed'],
            'last_cron_at' => $row['last_cron_at'] ? pSQL($row['last_cron_at']) : null,
            'checked_at' => pSQL($row['checked_at']),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    protected function insertProviderHealth(array $row)
    {
        return Db::getInstance()->insert('ntresellerclub_provider_health', array(
            'provider_code' => pSQL($row['provider_code']),
            'status' => pSQL($row['status']),
            'is_enabled' => (int)$row['is_enabled'],
            'is_licensed' => (int)$row['is_licensed'],
            'queue_pending' => (int)$row['queue_pending'],
            'queue_failed' => (int)$row['queue_failed'],
            'last_error' => $row['last_error'] ? pSQL($this->safeText($row['last_error'])) : null,
            'response_time_ms' => (int)$row['response_time_ms'],
            'checked_at' => pSQL($row['checked_at']),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    protected function latestProviderError($providerCode)
    {
        $value = Db::getInstance()->getValue(
            'SELECT last_error FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` '
            . 'WHERE provider_code="' . pSQL($providerCode) . '" AND last_error IS NOT NULL AND last_error != "" '
            . 'ORDER BY updated_at DESC, id_ntresellerclub_operation_queue DESC'
        );

        return $value ? $this->safeText($value) : null;
    }

    protected function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
