<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcSslProductMappingManager.php';

class AdminNtRcSslController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();
    }

    public function postProcess()
    {
        if (Tools::isSubmit('submitNtRcSaveSslMapping')) {
            $this->saveMappingAction();
        } elseif (Tools::isSubmit('submitNtRcToggleSslMapping')) {
            $this->toggleMappingAction();
        }

        parent::postProcess();
    }

    public function initContent()
    {
        parent::initContent();
        $this->content .= $this->renderMappingListAction();
        $this->context->smarty->assign(array('content' => $this->content));
    }

    protected function renderMappingListAction()
    {
        $rows = NtRcSslProductMappingManager::all(false);
        $html = '<div class="panel"><h3>SSL Product Mapping Backend</h3>';
        $html .= '<p>Backend skeleton only. No provider API call is executed here.</p>';
        $html .= '<table class="table"><thead><tr><th>ID</th><th>Product</th><th>Provider Product</th><th>Type</th><th>Cycle</th><th>Cost</th><th>Sale</th><th>Currency</th><th>Active</th></tr></thead><tbody>';

        foreach ((array)$rows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . (int)$row['id_ntresellerclub_ssl_product_mapping'] . '</td>';
            $html .= '<td>' . (int)$row['id_product'] . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['provider_product_id']) . '</td>';
            $html .= '<td>' . Tools::safeOutput(isset($row['ssl_product_type']) ? $row['ssl_product_type'] : '') . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['billing_cycle']) . '</td>';
            $html .= '<td>' . Tools::safeOutput(isset($row['cost_price']) ? $row['cost_price'] : '0') . '</td>';
            $html .= '<td>' . Tools::safeOutput(isset($row['sale_price']) ? $row['sale_price'] : '0') . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['currency']) . '</td>';
            $html .= '<td>' . ((int)$row['active'] ? '1' : '0') . '</td>';
            $html .= '</tr>';
        }

        if (!$rows) {
            $html .= '<tr><td colspan="9">No SSL mapping rows.</td></tr>';
        }

        return $html . '</tbody></table></div>';
    }

    protected function saveMappingAction()
    {
        if (!$this->checkToken()) {
            $this->errors[] = $this->trans('Invalid token.', array(), 'Admin.Notifications.Error');
            return;
        }

        $result = NtRcSslProductMappingManager::upsert(array(
            'id_product' => (int)Tools::getValue('id_product'),
            'provider_product_id' => trim((string)Tools::getValue('provider_product_id')),
            'ssl_product_type' => trim((string)Tools::getValue('ssl_product_type')),
            'billing_cycle' => trim((string)Tools::getValue('billing_cycle')),
            'cost_price' => (float)Tools::getValue('cost_price'),
            'sale_price' => (float)Tools::getValue('sale_price'),
            'currency' => trim((string)Tools::getValue('currency')),
            'active' => (int)Tools::getValue('active', 1),
        ));

        if (empty($result['success'])) {
            $this->errors[] = isset($result['message']) ? $result['message'] : 'SSL mapping save failed.';
            return;
        }

        $this->confirmations[] = 'SSL mapping saved.';
    }

    protected function toggleMappingAction()
    {
        if (!$this->checkToken()) {
            $this->errors[] = $this->trans('Invalid token.', array(), 'Admin.Notifications.Error');
            return;
        }

        $result = NtRcSslProductMappingManager::toggle((int)Tools::getValue('id_mapping'), (int)Tools::getValue('active'));
        if (empty($result['success'])) {
            $this->errors[] = isset($result['message']) ? $result['message'] : 'SSL mapping toggle failed.';
            return;
        }

        $this->confirmations[] = 'SSL mapping status updated.';
    }
}
