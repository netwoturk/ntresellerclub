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
        $readiness = isset($summary['readiness']) ? $summary['readiness'] : array('success' => false, 'check_count' => 0, 'failed_count' => 0);
        $kpis = isset($summary['kpis']) ? $summary['kpis'] : array();

        $html = '<section class="' . NtRcAdminThemeHelper::panelClass() . '">';
        $html .= '<h3>Dashboard</h3>';
        $html .= NtRcAdminWidget::alert('info', 'Dashboard reads existing backend summaries only. No provider API call is executed.');
        $html .= '<div class="ntrc-widget-grid">';
        $html .= NtRcAdminWidget::kpiCard('Readiness', !empty($readiness['success']) ? 'Ready' : 'Check', !empty($readiness['success']) ? 'success' : 'warning', (int)$readiness['check_count'] . ' checks');
        $html .= $this->renderKpiCard($kpis, 'Active Domains', 'active_domain_count', 'active', 'domain services');
        $html .= $this->renderKpiCard($kpis, 'Active TR Domains', 'active_tr_domain_count', 'active', 'tr_domain services');
        $html .= $this->renderKpiCard($kpis, 'Active Hosting', 'active_hosting_count', 'active', 'hosting services');
        $html .= $this->renderKpiCard($kpis, 'Active SSL', 'active_ssl_count', 'active', 'ssl services');
        $html .= $this->renderKpiCard($kpis, 'Queue Pending', 'pending_queue_count', 'pending', 'operation queue');
        $html .= $this->renderKpiCard($kpis, 'Queue Failed', 'failed_queue_count', 'failed', 'operation queue');
        $html .= $this->renderKpiCard($kpis, 'Payment Required', 'payment_required_count', 'warning', 'service status');
        $html .= $this->renderKpiCard($kpis, 'Provider Credit', 'provider_credit_required_count', 'provider_credit_required', 'service and queue');
        $html .= $this->renderKpiCard($kpis, 'Notifications Pending', 'notification_pending_count', 'pending', 'mail queue');
        $html .= $this->renderKpiCard($kpis, 'Notifications Failed', 'notification_failed_count', 'failed', 'mail queue');
        $html .= '</div>';
        $html .= $this->renderQuickActions(isset($summary['quick_actions']) ? $summary['quick_actions'] : array());
        $html .= '</section>';

        $html .= $this->renderProviderHealth(isset($summary['provider_health']) ? $summary['provider_health'] : array());
        $html .= $this->renderQueueSummary(isset($summary['queue']) ? $summary['queue'] : array());
        $html .= $this->renderRuntimeSummary(isset($summary['runtime']) ? $summary['runtime'] : array());
        $html .= $this->renderServiceOverview(isset($summary['service_overview']) ? $summary['service_overview'] : array());
        $html .= $this->renderFailedOperations(isset($summary['failed_operations']) ? $summary['failed_operations'] : array());
        $html .= $this->renderNotificationSummary(isset($summary['notifications']) ? $summary['notifications'] : array());

        return $html;
    }

    protected function renderKpiCard(array $kpis, $title, $key, $status, $description)
    {
        return NtRcAdminWidget::kpiCard($title, isset($kpis[$key]) ? (int)$kpis[$key] : 0, $status, $description);
    }

    protected function renderProviderHealth(array $providers)
    {
        $rows = array();
        foreach ($providers as $provider) {
            $rows[] = array(
                isset($provider['label']) ? $provider['label'] : '',
                isset($provider['status']) ? $provider['status'] : 'unknown',
                isset($provider['last_error']) && $provider['last_error'] !== '' ? $provider['last_error'] : '-',
                isset($provider['checked_at']) && $provider['checked_at'] !== '' ? $provider['checked_at'] : '-',
            );
        }

        return $this->renderDashboardPanel('Provider Health', NtRcAdminWidget::table(
            array('Provider', 'Status', 'Last error', 'Checked at'),
            $rows,
            'No provider health snapshot found.'
        ));
    }

    protected function renderQueueSummary(array $queue)
    {
        $rows = array(
            array('Pending', isset($queue['pending']) ? (int)$queue['pending'] : 0),
            array('Processing', isset($queue['processing']) ? (int)$queue['processing'] : 0),
            array('Done today', isset($queue['done_today']) ? (int)$queue['done_today'] : 0),
            array('Failed', isset($queue['failed']) ? (int)$queue['failed'] : 0),
            array('Retry count', isset($queue['retry_count']) ? (int)$queue['retry_count'] : 0),
        );

        return $this->renderDashboardPanel('Queue Summary', NtRcAdminWidget::table(array('Metric', 'Value'), $rows));
    }

    protected function renderRuntimeSummary(array $runtime)
    {
        $rows = array(
            array('Memory limit', isset($runtime['memory_limit']) ? $runtime['memory_limit'] : ''),
            array('Current memory', isset($runtime['current_memory']) ? $runtime['current_memory'] : ''),
            array('Peak memory', isset($runtime['peak_memory']) ? $runtime['peak_memory'] : ''),
            array('Cron last run', !empty($runtime['cron_last_run']) ? $runtime['cron_last_run'] : '-'),
            array('Batch limit', isset($runtime['batch_limit']) ? (int)$runtime['batch_limit'] : 0),
        );

        return $this->renderDashboardPanel('Runtime Summary', NtRcAdminWidget::table(array('Metric', 'Value'), $rows));
    }

    protected function renderServiceOverview(array $overview)
    {
        $rows = array();
        foreach ($overview as $row) {
            $rows[] = array(
                isset($row['service_type']) ? $row['service_type'] : '',
                isset($row['active']) ? (int)$row['active'] : 0,
                isset($row['pending']) ? (int)$row['pending'] : 0,
                isset($row['provisioning']) ? (int)$row['provisioning'] : 0,
                isset($row['payment_required']) ? (int)$row['payment_required'] : 0,
                isset($row['provider_credit_required']) ? (int)$row['provider_credit_required'] : 0,
                isset($row['failed']) ? (int)$row['failed'] : 0,
                isset($row['total']) ? (int)$row['total'] : 0,
            );
        }

        return $this->renderDashboardPanel('Service Overview', NtRcAdminWidget::table(
            array('Type', 'Active', 'Pending', 'Provisioning', 'Payment', 'Credit', 'Failed', 'Total'),
            $rows,
            'No service records found.'
        ));
    }

    protected function renderFailedOperations(array $operations)
    {
        $rows = array();
        foreach ($operations as $operation) {
            $rows[] = array(
                isset($operation['id']) ? (int)$operation['id'] : 0,
                isset($operation['provider']) ? $operation['provider'] : '',
                isset($operation['service']) ? $operation['service'] : '',
                isset($operation['action']) ? $operation['action'] : '',
                isset($operation['status']) ? $operation['status'] : '',
                isset($operation['retry_count']) ? (int)$operation['retry_count'] : 0,
                isset($operation['last_error']) ? $operation['last_error'] : '',
                isset($operation['updated_at']) ? $operation['updated_at'] : '',
            );
        }

        return $this->renderDashboardPanel('Failed Operations', NtRcAdminWidget::table(
            array('ID', 'Provider', 'Service', 'Action', 'Status', 'Retry', 'Last error', 'Updated at'),
            $rows,
            'No failed operations found.'
        ));
    }

    protected function renderNotificationSummary(array $notifications)
    {
        $rows = array(
            array('Pending', isset($notifications['pending']) ? (int)$notifications['pending'] : 0),
            array('Sent today', isset($notifications['sent_today']) ? (int)$notifications['sent_today'] : 0),
            array('Failed', isset($notifications['failed']) ? (int)$notifications['failed'] : 0),
            array('Last error', !empty($notifications['last_error']) ? $notifications['last_error'] : '-'),
        );

        return $this->renderDashboardPanel('Notification Summary', NtRcAdminWidget::table(array('Metric', 'Value'), $rows));
    }

    protected function renderQuickActions(array $actions)
    {
        $html = '<div class="ntrc-quick-actions">';
        foreach ($actions as $action) {
            if (empty($action['class_name']) || empty($action['label'])) {
                continue;
            }
            $url = $this->context->link->getAdminLink($action['class_name']);
            $html .= '<a class="btn btn-default" href="' . NtRcAdminThemeHelper::esc($url) . '">' . NtRcAdminThemeHelper::esc($action['label']) . '</a>';
        }
        return $html . '</div>';
    }

    protected function renderDashboardPanel($title, $content)
    {
        return '<section class="' . NtRcAdminThemeHelper::panelClass() . '"><h3>' . NtRcAdminThemeHelper::esc($title) . '</h3>' . $content . '</section>';
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
