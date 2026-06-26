<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcAdminDataProviderInterface.php';
require_once dirname(__DIR__) . '/NtRcRuntimeGuard.php';

class NtRcAdminDashboardDataProvider implements NtRcAdminDataProviderInterface
{
    public function getSummary()
    {
        $queue = $this->queueSummary();
        $billing = $this->billingSummary();
        $notifications = $this->notificationSummary();
        $services = $this->serviceOverview();

        return array(
            'kpis' => $this->kpis($services, $queue, $billing, $notifications),
            'provider_health' => $this->providerHealth(),
            'queue' => $queue,
            'runtime' => $this->runtimeSummary(),
            'service_overview' => $services,
            'failed_operations' => $this->failedOperations(),
            'notifications' => $notifications,
            'quick_actions' => $this->quickActions(),
        );
    }

    protected function kpis(array $services, array $queue, array $billing, array $notifications)
    {
        return array(
            'active_domain_count' => $this->serviceStatusCount($services, 'domain', 'active'),
            'active_tr_domain_count' => $this->serviceStatusCount($services, 'tr_domain', 'active'),
            'active_hosting_count' => $this->serviceStatusCount($services, 'hosting', 'active'),
            'active_ssl_count' => $this->serviceStatusCount($services, 'ssl', 'active'),
            'pending_queue_count' => isset($queue['pending']) ? (int)$queue['pending'] : 0,
            'failed_queue_count' => isset($queue['failed']) ? (int)$queue['failed'] : 0,
            'payment_required_count' => isset($billing['payment_required_count']) ? (int)$billing['payment_required_count'] : 0,
            'provider_credit_required_count' => isset($billing['provider_credit_required_count']) ? (int)$billing['provider_credit_required_count'] : 0,
            'notification_pending_count' => isset($notifications['pending']) ? (int)$notifications['pending'] : 0,
            'notification_failed_count' => isset($notifications['failed']) ? (int)$notifications['failed'] : 0,
        );
    }

    protected function providerHealth()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT h.provider_code, h.status, h.last_error, h.checked_at FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider_health` h '
            . 'INNER JOIN (SELECT provider_code, MAX(checked_at) AS checked_at FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider_health` GROUP BY provider_code) latest '
            . 'ON latest.provider_code=h.provider_code AND latest.checked_at=h.checked_at '
            . 'WHERE h.provider_code IN ("resellerclub", "domainnameapi") '
            . 'ORDER BY h.provider_code ASC'
        );

        $health = array(
            'resellerclub' => $this->emptyProviderHealth('resellerclub'),
            'domainnameapi' => $this->emptyProviderHealth('domainnameapi'),
        );

        foreach ((array)$rows as $row) {
            $providerCode = strtolower((string)$row['provider_code']);
            if (!isset($health[$providerCode])) {
                continue;
            }
            $health[$providerCode] = array(
                'provider_code' => $providerCode,
                'status' => $this->safeText(isset($row['status']) ? $row['status'] : 'unknown'),
                'last_error' => $this->safeText(isset($row['last_error']) ? $row['last_error'] : ''),
                'checked_at' => $this->safeText(isset($row['checked_at']) ? $row['checked_at'] : ''),
            );
        }

        return $health;
    }

    protected function queueSummary()
    {
        $summary = array('total' => 0, 'pending' => 0, 'processing' => 0, 'done' => 0, 'failed' => 0, 'retry' => 0);
        $rows = Db::getInstance()->executeS(
            'SELECT status, COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` GROUP BY status'
        );
        foreach ((array)$rows as $row) {
            $status = isset($row['status']) ? (string)$row['status'] : 'unknown';
            $count = (int)$row['total'];
            $summary['total'] += $count;
            if (isset($summary[$status])) {
                $summary[$status] = $count;
            }
        }

        $todayStart = date('Y-m-d 00:00:00');
        $tomorrowStart = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $summary['done_today'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` '
            . 'WHERE status="done" AND processed_at >= "' . pSQL($todayStart) . '" AND processed_at < "' . pSQL($tomorrowStart) . '"'
        );
        $summary['retry_count'] = isset($summary['retry']) ? (int)$summary['retry'] : 0;
        return $summary;
    }

    protected function billingSummary()
    {
        return array(
            'payment_required_count' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE status="payment_required"'
            ),
            'provider_credit_required_count' => (int)Db::getInstance()->getValue(
                'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE status="provider_credit_required"'
            ),
        );
    }

    protected function runtimeSummary()
    {
        $row = Db::getInstance()->getRow(
            'SELECT memory_limit, memory_usage_bytes, memory_peak_bytes, batch_limit, last_cron_at, checked_at '
            . 'FROM `' . _DB_PREFIX_ . 'ntresellerclub_runtime_health` ORDER BY checked_at DESC, id_ntresellerclub_runtime_health DESC LIMIT 1'
        );

        return array(
            'memory_limit' => $row && isset($row['memory_limit']) ? $this->safeText($row['memory_limit']) : NtRcRuntimeGuard::preferredMemoryLimit(),
            'current_memory' => $row && isset($row['memory_usage_bytes']) ? (int)$row['memory_usage_bytes'] : (int)memory_get_usage(false),
            'peak_memory' => $row && isset($row['memory_peak_bytes']) ? (int)$row['memory_peak_bytes'] : (int)memory_get_peak_usage(false),
            'last_cron_at' => $row && isset($row['last_cron_at']) ? $this->safeText($row['last_cron_at']) : (string)Configuration::get('NTRC_LAST_CRON_AT'),
            'batch_limit' => $row && isset($row['batch_limit']) ? (int)$row['batch_limit'] : NtRcRuntimeGuard::cronBatchLimit(),
            'checked_at' => $row && isset($row['checked_at']) ? $this->safeText($row['checked_at']) : '',
        );
    }

    protected function serviceOverview()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT service_type, status, COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` '
            . 'WHERE service_type IN ("domain", "tr_domain", "hosting", "ssl") '
            . 'GROUP BY service_type, status ORDER BY service_type ASC, status ASC'
        );

        $overview = array();
        foreach (array('domain', 'tr_domain', 'hosting', 'ssl') as $serviceType) {
            $overview[$serviceType] = array('service_type' => $serviceType, 'total' => 0, 'statuses' => array());
        }

        foreach ((array)$rows as $row) {
            $serviceType = isset($row['service_type']) ? (string)$row['service_type'] : '';
            if (!isset($overview[$serviceType])) {
                continue;
            }
            $status = $this->safeText(isset($row['status']) ? $row['status'] : 'unknown');
            $count = (int)$row['total'];
            $overview[$serviceType]['statuses'][$status] = $count;
            $overview[$serviceType]['total'] += $count;
        }

        return $overview;
    }

    protected function failedOperations()
    {
        $rows = Db::getInstance()->executeS(
            'SELECT id_ntresellerclub_operation_queue, provider_code, service_type, action, status, retry_count, last_error, updated_at '
            . 'FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` '
            . 'WHERE status IN ("failed", "provider_credit_required") '
            . 'ORDER BY updated_at DESC, id_ntresellerclub_operation_queue DESC LIMIT 10'
        );

        $failed = array();
        foreach ((array)$rows as $row) {
            $failed[] = array(
                'id' => (int)$row['id_ntresellerclub_operation_queue'],
                'provider_code' => $this->safeText($row['provider_code']),
                'service_type' => $this->safeText($row['service_type']),
                'action' => $this->safeText($row['action']),
                'status' => $this->safeText($row['status']),
                'retry_count' => (int)$row['retry_count'],
                'last_error' => $this->safeText($row['last_error']),
                'updated_at' => $this->safeText($row['updated_at']),
            );
        }

        return $failed;
    }

    protected function notificationSummary()
    {
        $summary = array('pending' => 0, 'processing' => 0, 'sent' => 0, 'failed' => 0, 'retry' => 0, 'total' => 0);
        $rows = Db::getInstance()->executeS(
            'SELECT status, COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_queue` GROUP BY status'
        );

        foreach ((array)$rows as $row) {
            $status = isset($row['status']) ? (string)$row['status'] : 'unknown';
            $count = (int)$row['total'];
            $summary['total'] += $count;
            if (isset($summary[$status])) {
                $summary[$status] = $count;
            }
        }

        $summary['retry'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_queue` WHERE retry_count > 0'
        );

        return $summary;
    }

    protected function quickActions()
    {
        return array(
            array('label' => 'Queue ekranina git', 'controller' => 'AdminNtRcQueue'),
            array('label' => 'Monitoring ekranina git', 'controller' => 'AdminNtRcMonitoring'),
            array('label' => 'Pricing ekranina git', 'controller' => 'AdminNtRcPricing'),
            array('label' => 'Settings ekranina git', 'controller' => 'AdminNtRcSettings'),
        );
    }

    protected function serviceStatusCount(array $overview, $serviceType, $status)
    {
        return isset($overview[$serviceType]['statuses'][$status]) ? (int)$overview[$serviceType]['statuses'][$status] : 0;
    }

    protected function emptyProviderHealth($providerCode)
    {
        return array(
            'provider_code' => $providerCode,
            'status' => 'not_checked',
            'last_error' => '',
            'checked_at' => '',
        );
    }

    protected function safeText($text)
    {
        $text = preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|csr|private_key|private-key|certificate|certificate_raw|cert_raw)=([^&\s]+)/i', '$1=***', (string)$text);
        return preg_replace('/("?(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|csr|private_key|private-key|certificate|certificate_raw|cert_raw)"?\s*:\s*)("[^"]*"|[^,\}\s]+)/i', '$1***', $text);
    }
}
