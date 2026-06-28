<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcAdminDataProviderInterface.php';
require_once dirname(__DIR__) . '/NtRcInstaller.php';
require_once dirname(__DIR__) . '/NtRcProductionReadinessVerifier.php';
require_once dirname(__DIR__) . '/NtRcRuntimeGuard.php';
require_once dirname(__DIR__) . '/NtRcStatisticsEngine.php';

class NtRcAdminDashboardDataProvider implements NtRcAdminDataProviderInterface
{
    public function getSummary()
    {
        $statistics = new NtRcStatisticsEngine();
        $queue = $this->queueSummary($statistics);
        $hosting = $statistics->hostingSummary();
        $ssl = $statistics->sslSummary();
        $billing = $statistics->billingSummary();
        $notifications = $this->notificationSummary();

        return array(
            'readiness' => NtRcProductionReadinessVerifier::summary(),
            'kpis' => $this->kpis($queue, $hosting, $ssl, $billing, $notifications),
            'provider_health' => $this->providerHealth(),
            'queue' => $queue,
            'runtime' => $this->runtimeSummary(),
            'service_overview' => $this->serviceOverview(),
            'failed_operations' => $this->failedOperations(10),
            'notifications' => $notifications,
            'quick_actions' => $this->quickActions(),
            'hosting' => $hosting,
            'ssl' => $ssl,
            'billing' => $billing,
        );
    }

    protected function kpis(array $queue, array $hosting, array $ssl, array $billing, array $notifications)
    {
        NtRcInstaller::ensureServiceSchema();

        return array(
            'active_domain_count' => $this->countServices('domain', 'active'),
            'active_tr_domain_count' => $this->countServices('tr_domain', 'active'),
            'active_hosting_count' => isset($hosting['active_hosting_count']) ? (int)$hosting['active_hosting_count'] : $this->countServices('hosting', 'active'),
            'active_ssl_count' => isset($ssl['active_ssl_count']) ? (int)$ssl['active_ssl_count'] : $this->countServices('ssl', 'active'),
            'pending_queue_count' => isset($queue['pending']) ? (int)$queue['pending'] : 0,
            'failed_queue_count' => isset($queue['failed']) ? (int)$queue['failed'] : 0,
            'payment_required_count' => isset($billing['service_payment_required_count']) ? (int)$billing['service_payment_required_count'] : $this->countServices(null, 'payment_required'),
            'provider_credit_required_count' => $this->providerCreditRequiredCount($billing),
            'notification_pending_count' => isset($notifications['pending']) ? (int)$notifications['pending'] : 0,
            'notification_failed_count' => isset($notifications['failed']) ? (int)$notifications['failed'] : 0,
        );
    }

    protected function queueSummary(NtRcStatisticsEngine $statistics)
    {
        $summary = $statistics->queueSummary();
        $summary['done_today'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` '
            . 'WHERE status="done" AND processed_at >= CURDATE()'
        );
        $summary['retry_count'] = isset($summary['retry']) ? (int)$summary['retry'] : 0;

        return $summary;
    }

    protected function providerHealth()
    {
        NtRcInstaller::ensureMonitoringSchema();

        return array(
            'resellerclub' => $this->latestProviderHealth('resellerclub', 'ResellerClub'),
            'domainnameapi' => $this->latestProviderHealth('domainnameapi', 'DomainNameAPI'),
        );
    }

    protected function latestProviderHealth($providerCode, $label)
    {
        $row = Db::getInstance()->getRow(
            'SELECT provider_code, status, last_error, checked_at FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider_health` '
            . 'WHERE provider_code="' . pSQL($providerCode) . '" '
            . 'ORDER BY checked_at DESC, id_ntresellerclub_provider_health DESC'
        );

        if (!is_array($row)) {
            $row = array();
        }

        return array(
            'provider_code' => $providerCode,
            'label' => $label,
            'status' => isset($row['status']) ? $row['status'] : 'unknown',
            'last_error' => isset($row['last_error']) ? $this->safeText($row['last_error']) : '',
            'checked_at' => isset($row['checked_at']) ? $row['checked_at'] : '',
        );
    }

    protected function runtimeSummary()
    {
        NtRcInstaller::ensureMonitoringSchema();

        $row = Db::getInstance()->getRow(
            'SELECT memory_limit, memory_usage_bytes, memory_peak_bytes, last_cron_at, batch_limit, checked_at FROM `' . _DB_PREFIX_ . 'ntresellerclub_runtime_health` '
            . 'ORDER BY checked_at DESC, id_ntresellerclub_runtime_health DESC'
        );

        if (!is_array($row)) {
            $row = array();
        }

        return array(
            'memory_limit' => !empty($row['memory_limit']) ? $row['memory_limit'] : ini_get('memory_limit'),
            'current_memory' => $this->formatBytes(memory_get_usage(true)),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'last_snapshot_memory' => isset($row['memory_usage_bytes']) ? $this->formatBytes((int)$row['memory_usage_bytes']) : '',
            'last_snapshot_peak' => isset($row['memory_peak_bytes']) ? $this->formatBytes((int)$row['memory_peak_bytes']) : '',
            'cron_last_run' => !empty($row['last_cron_at']) ? $row['last_cron_at'] : (Configuration::get('NTRC_LAST_CRON_AT') ?: ''),
            'batch_limit' => isset($row['batch_limit']) && (int)$row['batch_limit'] > 0 ? (int)$row['batch_limit'] : NtRcRuntimeGuard::cronBatchLimit(10),
            'checked_at' => isset($row['checked_at']) ? $row['checked_at'] : '',
        );
    }

    protected function serviceOverview()
    {
        NtRcInstaller::ensureServiceSchema();

        $overview = array();
        foreach (array('domain', 'tr_domain', 'hosting', 'ssl') as $type) {
            $overview[$type] = array(
                'service_type' => $type,
                'active' => 0,
                'pending' => 0,
                'provisioning' => 0,
                'payment_required' => 0,
                'provider_credit_required' => 0,
                'failed' => 0,
                'total' => 0,
            );
        }

        $rows = Db::getInstance()->executeS(
            'SELECT service_type, status, COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` '
            . 'WHERE service_type IN ("domain", "tr_domain", "hosting", "ssl") '
            . 'GROUP BY service_type, status'
        );

        foreach ((array)$rows as $row) {
            $type = isset($row['service_type']) ? $row['service_type'] : '';
            $status = isset($row['status']) ? $row['status'] : '';
            $total = isset($row['total']) ? (int)$row['total'] : 0;

            if (!isset($overview[$type])) {
                continue;
            }

            if (isset($overview[$type][$status])) {
                $overview[$type][$status] += $total;
            } elseif (in_array($status, array('failed', 'error'), true)) {
                $overview[$type]['failed'] += $total;
            }
            $overview[$type]['total'] += $total;
        }

        return array_values($overview);
    }

    protected function failedOperations($limit = 10)
    {
        NtRcInstaller::ensureOperationQueueSchema();

        $limit = max(1, min(10, (int)$limit));
        $rows = Db::getInstance()->executeS(
            'SELECT id_ntresellerclub_operation_queue, provider_code, service_type, action, status, retry_count, last_error, updated_at '
            . 'FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` '
            . 'WHERE status IN ("failed", "provider_credit_required") '
            . 'ORDER BY updated_at DESC, id_ntresellerclub_operation_queue DESC LIMIT ' . (int)$limit
        );

        $safeRows = array();
        foreach ((array)$rows as $row) {
            $safeRows[] = array(
                'id' => isset($row['id_ntresellerclub_operation_queue']) ? (int)$row['id_ntresellerclub_operation_queue'] : 0,
                'provider' => isset($row['provider_code']) ? $row['provider_code'] : '',
                'service' => isset($row['service_type']) ? $row['service_type'] : '',
                'action' => isset($row['action']) ? $row['action'] : '',
                'status' => isset($row['status']) ? $row['status'] : '',
                'retry_count' => isset($row['retry_count']) ? (int)$row['retry_count'] : 0,
                'last_error' => isset($row['last_error']) ? $this->safeText($row['last_error']) : '',
                'updated_at' => isset($row['updated_at']) ? $row['updated_at'] : '',
            );
        }

        return $safeRows;
    }

    protected function notificationSummary()
    {
        NtRcInstaller::ensureNotificationSchema();

        return array(
            'pending' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_queue` WHERE status="pending"'
            ),
            'sent_today' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_queue` WHERE status="sent" AND sent_at >= CURDATE()'
            ),
            'failed' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_queue` WHERE status="failed"'
            ),
            'last_error' => $this->safeText(Db::getInstance()->getValue(
                'SELECT last_error FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_queue` '
                . 'WHERE last_error IS NOT NULL AND last_error != "" ORDER BY updated_at DESC, id_ntresellerclub_notification_queue DESC'
            )),
        );
    }

    protected function quickActions()
    {
        return array(
            array('label' => 'Queue ekranina git', 'class_name' => 'AdminNtRcQueue'),
            array('label' => 'Monitoring ekranina git', 'class_name' => 'AdminNtRcMonitoring'),
            array('label' => 'Pricing ekranina git', 'class_name' => 'AdminNtRcPricing'),
            array('label' => 'Settings ekranina git', 'class_name' => 'AdminNtRcSettings'),
        );
    }

    protected function countServices($serviceType = null, $status = null)
    {
        $where = array();
        if ($serviceType !== null) {
            $where[] = 'service_type="' . pSQL($serviceType) . '"';
        }
        if ($status !== null) {
            $where[] = 'status="' . pSQL($status) . '"';
        }

        return (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service`'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
        );
    }

    protected function providerCreditRequiredCount(array $billing)
    {
        $service = isset($billing['service_provider_credit_required_count']) ? (int)$billing['service_provider_credit_required_count'] : 0;
        $queue = isset($billing['queue_provider_credit_required_count']) ? (int)$billing['queue_provider_credit_required_count'] : 0;
        return $service + $queue;
    }

    protected function formatBytes($bytes)
    {
        $bytes = (int)$bytes;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    protected function safeText($text)
    {
        $text = (string)$text;
        $patterns = array(
            '/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|secret|private_key|private-key|csr|certificate|certificate_raw|cert_raw)=([^&\s]+)/i',
            '/("(?:api-key|api_key|auth-code|auth_code|passwd|password|token|credential|secret|private_key|private-key|csr|certificate|certificate_raw|cert_raw)"\s*:\s*)"[^"]*"/i',
        );

        $text = preg_replace($patterns[0], '$1=***', $text);
        return preg_replace($patterns[1], '$1"***"', $text);
    }
}
