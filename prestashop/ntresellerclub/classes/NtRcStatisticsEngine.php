<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';
require_once __DIR__ . '/NtRcBillingMonitoring.php';
require_once __DIR__ . '/providers/NtRcProviderRegistry.php';

class NtRcStatisticsEngine
{
    public function snapshot($metricDate = null)
    {
        NtRcInstaller::ensureMonitoringSchema();

        $metricDate = $metricDate ?: date('Y-m-d');
        $rows = array();
        $providers = NtRcProviderRegistry::all(false);

        foreach ((array)$providers as $provider) {
            if (empty($provider['provider_code'])) {
                continue;
            }

            $row = $this->buildProviderStatistics($provider['provider_code'], $metricDate);
            $this->upsertProviderStatistics($row);
            $rows[] = $row;
        }

        return array('success' => true, 'metric_date' => $metricDate, 'providers' => $rows);
    }

    public function hostingSummary()
    {
        NtRcInstaller::ensureServiceSchema();
        NtRcInstaller::ensureOperationQueueSchema();

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

    public function billingSummary()
    {
        NtRcInstaller::ensureBillingEventSchema();
        return NtRcBillingMonitoring::summary();
    }

    public function queueSummary($providerCode = null)
    {
        NtRcInstaller::ensureMonitoringSchema();

        $where = '';
        if ($providerCode !== null && $providerCode !== '') {
            $where = ' WHERE provider_code="' . pSQL($providerCode) . '"';
        }

        $summary = array(
            'total' => 0,
            'pending' => 0,
            'processing' => 0,
            'done' => 0,
            'failed' => 0,
            'retry' => 0,
        );

        $rows = Db::getInstance()->executeS(
            'SELECT status, COUNT(*) AS total FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue`' . $where . ' GROUP BY status'
        );

        foreach ((array)$rows as $row) {
            $status = isset($row['status']) ? $row['status'] : 'unknown';
            $count = (int)$row['total'];
            $summary['total'] += $count;
            if (isset($summary[$status])) {
                $summary[$status] = $count;
            }
        }

        $retryWhere = $where === '' ? ' WHERE retry_count > 0' : $where . ' AND retry_count > 0';
        $summary['retry'] = (int)Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue`' . $retryWhere
        );

        return $summary;
    }

    protected function buildProviderStatistics($providerCode, $metricDate)
    {
        $summary = $this->queueSummary($providerCode);
        $avgRetry = (float)Db::getInstance()->getValue(
            'SELECT AVG(retry_count) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE provider_code="' . pSQL($providerCode) . '"'
        );

        return array(
            'provider_code' => $providerCode,
            'metric_date' => $metricDate,
            'total_queue' => (int)$summary['total'],
            'pending_queue' => (int)$summary['pending'],
            'processing_queue' => (int)$summary['processing'],
            'done_queue' => (int)$summary['done'],
            'failed_queue' => (int)$summary['failed'],
            'retry_queue' => (int)$summary['retry'],
            'avg_retry' => round($avgRetry, 4),
            'last_success_at' => $this->lastQueueDate($providerCode, 'done'),
            'last_failure_at' => $this->lastQueueDate($providerCode, 'failed'),
            'hosting' => $providerCode === 'resellerclub' ? $this->hostingSummary() : array(),
        );
    }

    protected function upsertProviderStatistics(array $row)
    {
        $existing = Db::getInstance()->getValue(
            'SELECT id_ntresellerclub_provider_statistics FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider_statistics` '
            . 'WHERE provider_code="' . pSQL($row['provider_code']) . '" AND metric_date="' . pSQL($row['metric_date']) . '"'
        );

        $data = array(
            'provider_code' => pSQL($row['provider_code']),
            'metric_date' => pSQL($row['metric_date']),
            'total_queue' => (int)$row['total_queue'],
            'pending_queue' => (int)$row['pending_queue'],
            'processing_queue' => (int)$row['processing_queue'],
            'done_queue' => (int)$row['done_queue'],
            'failed_queue' => (int)$row['failed_queue'],
            'retry_queue' => (int)$row['retry_queue'],
            'avg_retry' => (float)$row['avg_retry'],
            'last_success_at' => $row['last_success_at'] ? pSQL($row['last_success_at']) : null,
            'last_failure_at' => $row['last_failure_at'] ? pSQL($row['last_failure_at']) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        );

        if ($existing) {
            return Db::getInstance()->update('ntresellerclub_provider_statistics', $data, 'id_ntresellerclub_provider_statistics=' . (int)$existing);
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        return Db::getInstance()->insert('ntresellerclub_provider_statistics', $data);
    }

    protected function lastQueueDate($providerCode, $status)
    {
        return Db::getInstance()->getValue(
            'SELECT MAX(' . ($status === 'done' ? 'processed_at' : 'updated_at') . ') FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` '
            . 'WHERE provider_code="' . pSQL($providerCode) . '" AND status="' . pSQL($status) . '"'
        );
    }
}
