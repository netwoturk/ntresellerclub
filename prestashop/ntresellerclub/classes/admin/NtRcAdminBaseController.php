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
        $queue = isset($summary['queue']) ? $summary['queue'] : array();
        $hosting = isset($summary['hosting']) ? $summary['hosting'] : array();
        $ssl = isset($summary['ssl']) ? $summary['ssl'] : array();

        $html = '<section class="' . NtRcAdminThemeHelper::panelClass() . '">';
        $html .= '<h3>Dashboard Foundation</h3>';
        $html .= NtRcAdminWidget::alert('info', 'Dashboard reads existing backend summaries only. No provider API call is executed.');
        $html .= '<div class="ntrc-widget-grid">';
        $html .= NtRcAdminWidget::kpiCard('Readiness', !empty($readiness['success']) ? 'Ready' : 'Check', !empty($readiness['success']) ? 'success' : 'warning', (int)$readiness['check_count'] . ' checks');
        $html .= NtRcAdminWidget::kpiCard('Queue Pending', isset($queue['pending']) ? (int)$queue['pending'] : 0, 'pending', 'Operation queue');
        $html .= NtRcAdminWidget::kpiCard('Hosting Active', isset($hosting['active_hosting_count']) ? (int)$hosting['active_hosting_count'] : 0, 'active', 'Existing service summary');
        $html .= NtRcAdminWidget::kpiCard('SSL Active', isset($ssl['active_ssl_count']) ? (int)$ssl['active_ssl_count'] : 0, 'active', 'Existing SSL monitoring');
        $html .= '</div></section>';
        return $html;
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
