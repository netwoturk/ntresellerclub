<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcAdminDashboardDataProvider.php';
require_once __DIR__ . '/NtRcAdminLayout.php';
require_once __DIR__ . '/NtRcAdminNavigationBuilder.php';
require_once __DIR__ . '/NtRcAdminWidget.php';

abstract class NtRcAdminBaseController extends ModuleAdminController
{
    protected $ntRcSection = 'dashboard';

    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();
        $section = NtRcAdminNavigationBuilder::section($this->ntRcSection);
        $this->page_header_toolbar_title = $section['label'];
    }

    public function initContent()
    {
        parent::initContent();
        $section = NtRcAdminNavigationBuilder::section($this->ntRcSection);
        $this->content .= NtRcAdminLayout::render($this, array(
            'section' => $this->ntRcSection,
            'title' => $section['label'],
            'content' => $this->renderSectionContent(),
        ));
        $this->context->smarty->assign(array('content' => $this->content));
    }

    protected function renderSectionContent()
    {
        if (!$this->hasPermission('view')) {
            return NtRcAdminWidget::alert('danger', 'You do not have permission to view this page.');
        }

        if ($this->ntRcSection === 'dashboard') {
            return $this->renderDashboardSkeleton();
        }

        $section = NtRcAdminNavigationBuilder::section($this->ntRcSection);
        $html = '<section class="' . NtRcAdminThemeHelper::panelClass() . '">';
        $html .= '<h3>' . NtRcAdminThemeHelper::esc($section['label']) . '</h3>';
        $html .= NtRcAdminWidget::alert('info', 'Admin framework skeleton is ready. Data binding will be implemented by the related screen engine.');
        $html .= '<div class="ntrc-widget-grid">';
        $html .= NtRcAdminWidget::statisticTile('Framework', 'Ready', 'Shared layout, navigation, widgets, and security helpers are active.');
        $html .= NtRcAdminWidget::statisticTile('Provider API', 'Not called', 'This admin framework page reads no external provider endpoint.');
        $html .= '</div></section>';
        return $html;
    }

    protected function renderDashboardSkeleton()
    {
        $provider = new NtRcAdminDashboardDataProvider();
        $summary = $provider->getSummary();
        $kpis = isset($summary['kpis']) ? $summary['kpis'] : array();

        $html = '<section class="' . NtRcAdminThemeHelper::panelClass() . '">';
        $html .= '<h3>Dashboard</h3>';
        $html .= NtRcAdminWidget::alert('info', 'Dashboard reads existing backend summaries only. No provider API call is executed.');
        $html .= '<div class="ntrc-widget-grid">';
        $html .= NtRcAdminWidget::kpiCard('Active Domains', $this->dashboardValue($kpis, 'active_domain_count'), 'active', 'Global domain');
        $html .= NtRcAdminWidget::kpiCard('Active TR Domains', $this->dashboardValue($kpis, 'active_tr_domain_count'), 'active', 'TR domain');
        $html .= NtRcAdminWidget::kpiCard('Active Hosting', $this->dashboardValue($kpis, 'active_hosting_count'), 'active', 'Hosting services');
        $html .= NtRcAdminWidget::kpiCard('Active SSL', $this->dashboardValue($kpis, 'active_ssl_count'), 'active', 'SSL services');
        $html .= NtRcAdminWidget::kpiCard('Pending Queue', $this->dashboardValue($kpis, 'pending_queue_count'), 'pending', 'Operation queue');
        $html .= NtRcAdminWidget::kpiCard('Failed Queue', $this->dashboardValue($kpis, 'failed_queue_count'), 'failed', 'Needs attention');
        $html .= NtRcAdminWidget::kpiCard('Payment Required', $this->dashboardValue($kpis, 'payment_required_count'), 'warning', 'Billing');
        $html .= NtRcAdminWidget::kpiCard('Provider Credit', $this->dashboardValue($kpis, 'provider_credit_required_count'), 'warning', 'Provider balance');
        $html .= NtRcAdminWidget::kpiCard('Notification Pending', $this->dashboardValue($kpis, 'notification_pending_count'), 'pending', 'Mail queue');
        $html .= NtRcAdminWidget::kpiCard('Notification Failed', $this->dashboardValue($kpis, 'notification_failed_count'), 'failed', 'Mail queue');
        $html .= '</div>';
        $html .= $this->renderDashboardDetails($summary);
        $html .= '</section>';
        return $html;
    }

    protected function renderDashboardDetails(array $summary)
    {
        $html = '<div class="ntrc-dashboard-grid">';
        $html .= $this->renderProviderHealth(isset($summary['provider_health']) ? $summary['provider_health'] : array());
        $html .= $this->renderQueueSummary(isset($summary['queue']) ? $summary['queue'] : array());
        $html .= $this->renderRuntimeSummary(isset($summary['runtime']) ? $summary['runtime'] : array());
        $html .= $this->renderNotificationSummary(isset($summary['notifications']) ? $summary['notifications'] : array());
        $html .= '</div>';
        $html .= $this->renderServiceOverview(isset($summary['service_overview']) ? $summary['service_overview'] : array());
        $html .= $this->renderFailedOperations(isset($summary['failed_operations']) ? $summary['failed_operations'] : array());
        $html .= $this->renderQuickActions(isset($summary['quick_actions']) ? $summary['quick_actions'] : array());
        return $html;
    }

    protected function renderProviderHealth(array $health)
    {
        $rows = array();
        foreach (array('resellerclub', 'domainnameapi') as $providerCode) {
            $row = isset($health[$providerCode]) ? $health[$providerCode] : array();
            $rows[] = array(
                strtoupper($providerCode),
                isset($row['status']) ? $row['status'] : 'not_checked',
                isset($row['last_error']) ? $row['last_error'] : '',
                isset($row['checked_at']) ? $row['checked_at'] : '',
            );
        }

        return '<section class="ntrc-widget"><h4>Provider Health</h4>'
            . NtRcAdminWidget::table(array('Provider', 'Status', 'Last Error', 'Checked At'), $rows)
            . '</section>';
    }

    protected function renderQueueSummary(array $queue)
    {
        $rows = array(
            array('Pending', $this->dashboardValue($queue, 'pending')),
            array('Processing', $this->dashboardValue($queue, 'processing')),
            array('Done Today', $this->dashboardValue($queue, 'done_today')),
            array('Failed', $this->dashboardValue($queue, 'failed')),
            array('Retry Count', $this->dashboardValue($queue, 'retry_count')),
        );

        return '<section class="ntrc-widget"><h4>Queue Summary</h4>'
            . NtRcAdminWidget::table(array('Metric', 'Value'), $rows)
            . '</section>';
    }

    protected function renderRuntimeSummary(array $runtime)
    {
        $rows = array(
            array('Memory Limit', $this->dashboardValue($runtime, 'memory_limit')),
            array('Current Memory', $this->formatBytes($this->dashboardValue($runtime, 'current_memory'))),
            array('Peak Memory', $this->formatBytes($this->dashboardValue($runtime, 'peak_memory'))),
            array('Cron Last Run', $this->dashboardValue($runtime, 'last_cron_at')),
            array('Batch Limit', $this->dashboardValue($runtime, 'batch_limit')),
        );

        return '<section class="ntrc-widget"><h4>Runtime Summary</h4>'
            . NtRcAdminWidget::table(array('Metric', 'Value'), $rows)
            . '</section>';
    }

    protected function renderNotificationSummary(array $notifications)
    {
        $rows = array(
            array('Pending', $this->dashboardValue($notifications, 'pending')),
            array('Processing', $this->dashboardValue($notifications, 'processing')),
            array('Sent', $this->dashboardValue($notifications, 'sent')),
            array('Failed', $this->dashboardValue($notifications, 'failed')),
            array('Retry', $this->dashboardValue($notifications, 'retry')),
        );

        return '<section class="ntrc-widget"><h4>Notification Summary</h4>'
            . NtRcAdminWidget::table(array('Metric', 'Value'), $rows)
            . '</section>';
    }

    protected function renderServiceOverview(array $overview)
    {
        $rows = array();
        foreach (array('domain', 'tr_domain', 'hosting', 'ssl') as $serviceType) {
            $row = isset($overview[$serviceType]) ? $overview[$serviceType] : array('total' => 0, 'statuses' => array());
            $rows[] = array($serviceType, isset($row['total']) ? (int)$row['total'] : 0, $this->statusSummary(isset($row['statuses']) ? $row['statuses'] : array()));
        }

        return '<section class="ntrc-widget ntrc-dashboard-wide"><h4>Service Overview</h4>'
            . NtRcAdminWidget::table(array('Service Type', 'Total', 'Statuses'), $rows)
            . '</section>';
    }

    protected function renderFailedOperations(array $operations)
    {
        $rows = array();
        foreach ($operations as $operation) {
            $rows[] = array(
                isset($operation['id']) ? (int)$operation['id'] : 0,
                isset($operation['provider_code']) ? $operation['provider_code'] : '',
                isset($operation['service_type']) ? $operation['service_type'] : '',
                isset($operation['action']) ? $operation['action'] : '',
                isset($operation['status']) ? $operation['status'] : '',
                isset($operation['retry_count']) ? (int)$operation['retry_count'] : 0,
                isset($operation['last_error']) ? $operation['last_error'] : '',
                isset($operation['updated_at']) ? $operation['updated_at'] : '',
            );
        }

        return '<section class="ntrc-widget ntrc-dashboard-wide"><h4>Failed Operations</h4>'
            . NtRcAdminWidget::table(array('ID', 'Provider', 'Service', 'Action', 'Status', 'Retry', 'Last Error', 'Updated'), $rows, 'No failed operations.')
            . '</section>';
    }

    protected function renderQuickActions(array $actions)
    {
        $html = '<section class="ntrc-widget ntrc-dashboard-wide"><h4>Quick Actions</h4><div class="ntrc-quick-actions">';
        foreach ($actions as $action) {
            $controller = isset($action['controller']) ? $action['controller'] : '';
            $label = isset($action['label']) ? $action['label'] : $controller;
            if ($controller === '') {
                continue;
            }
            $url = $this->context->link->getAdminLink($controller);
            $html .= '<a class="btn btn-default" href="' . NtRcAdminThemeHelper::esc($url) . '">' . NtRcAdminThemeHelper::esc($label) . '</a> ';
        }
        return $html . '</div></section>';
    }

    protected function dashboardValue(array $row, $key)
    {
        return isset($row[$key]) ? $row[$key] : 0;
    }

    protected function statusSummary(array $statuses)
    {
        $parts = array();
        foreach ($statuses as $status => $count) {
            $parts[] = $status . ': ' . (int)$count;
        }
        return implode(', ', $parts);
    }

    protected function formatBytes($bytes)
    {
        $bytes = (float)$bytes;
        if ($bytes <= 0) {
            return '0 B';
        }
        $units = array('B', 'KB', 'MB', 'GB');
        $index = 0;
        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes = $bytes / 1024;
            $index++;
        }
        return round($bytes, 2) . ' ' . $units[$index];
    }

    protected function hasPermission($action = 'view')
    {
        if (method_exists($this, 'access')) {
            return (bool)$this->access($action);
        }
        return true;
    }

    protected function currentAdminToken()
    {
        return Tools::getAdminTokenLite($this->controller_name);
    }

    protected function isValidCsrfToken($fieldName = 'token')
    {
        $token = Tools::getValue($fieldName);
        if (!$token) {
            return false;
        }

        if (function_exists('hash_equals')) {
            return hash_equals($this->currentAdminToken(), (string)$token);
        }

        return $this->currentAdminToken() === (string)$token;
    }

    protected function flash($type, $message)
    {
        if ($type === 'error') {
            $this->errors[] = $message;
        } elseif ($type === 'warning') {
            $this->warnings[] = $message;
        } else {
            $this->confirmations[] = $message;
        }
    }
}
