<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcSslProductMappingManager.php';

class NtRcSslMappingAdminRenderer
{
    public static function render(Module $module)
    {
        $rows = NtRcSslProductMappingManager::all(false);
        $action = AdminController::$currentIndex . '&configure=' . $module->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $token = Tools::getAdminTokenLite('AdminModules');

        $html = '<div class="panel"><h3>SSL Product Mapping</h3>';
        $html .= '<p>SSL sadece ResellerClub uzerinden calisir. Bu panel provider API cagrisi yapmaz.</p>';
        $html .= '<table class="table"><thead><tr>';
        $html .= '<th>ID</th><th>PrestaShop Product</th><th>Provider Product</th><th>Type</th><th>Cycle</th><th>Cost</th><th>Sale</th><th>Currency</th><th>Status</th><th>Action</th>';
        $html .= '</tr></thead><tbody>';

        foreach ((array)$rows as $row) {
            $id = (int)$row['id_ntresellerclub_ssl_product_mapping'];
            $productName = self::productName((int)$row['id_product']);
            $html .= '<tr>';
            $html .= '<td>' . $id . '</td>';
            $html .= '<td>' . (int)$row['id_product'] . ' - ' . Tools::safeOutput($productName) . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['provider_product_id']) . '</td>';
            $html .= '<td>' . Tools::safeOutput(isset($row['ssl_product_type']) ? $row['ssl_product_type'] : 'standard') . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['billing_cycle']) . '</td>';
            $html .= '<td>' . Tools::safeOutput(isset($row['cost_price']) ? $row['cost_price'] : '0') . '</td>';
            $html .= '<td>' . Tools::safeOutput(isset($row['sale_price']) ? $row['sale_price'] : '0') . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['currency']) . '</td>';
            $html .= '<td>' . ((int)$row['active'] ? 'Active' : 'Passive') . '</td>';
            $html .= '<td><form method="post" action="' . Tools::safeOutput($action) . '">';
            $html .= '<input type="hidden" name="submitNtRcToggleSslMapping" value="1">';
            $html .= '<input type="hidden" name="nt_ssl_mapping_token" value="' . Tools::safeOutput($token) . '">';
            $html .= '<input type="hidden" name="id_mapping" value="' . $id . '">';
            $html .= '<input type="hidden" name="active" value="' . ((int)$row['active'] ? 0 : 1) . '">';
            $html .= '<button type="submit" class="btn btn-default">' . ((int)$row['active'] ? 'Disable' : 'Enable') . '</button>';
            $html .= '</form></td>';
            $html .= '</tr>';
        }

        if (!$rows) {
            $html .= '<tr><td colspan="10">Henuz SSL mapping kaydi yok.</td></tr>';
        }

        $html .= '</tbody></table>';
        $html .= self::renderForm($action, $token);
        $html .= '</div>';
        return $html;
    }

    protected static function renderForm($action, $token)
    {
        $html = '<h4>Mapping Kaydet</h4>';
        $html .= '<form method="post" action="' . Tools::safeOutput($action) . '" class="form-horizontal">';
        $html .= '<input type="hidden" name="submitNtRcSaveSslMapping" value="1">';
        $html .= '<input type="hidden" name="nt_ssl_mapping_token" value="' . Tools::safeOutput($token) . '">';
        $html .= self::input('id_product', 'PrestaShop Product ID', '');
        $html .= self::input('provider_product_id', 'Provider Product / Plan ID', '');
        $html .= self::select('ssl_product_type', 'SSL Product Type', array('standard', 'premium', 'wildcard', 'ev', 'positive_ssl', 'positive_wildcard'), 'standard');
        $html .= self::select('billing_cycle', 'Billing Cycle', array('yearly', 'biennial', 'triennial'), 'yearly');
        $html .= self::input('cost_price', 'Cost Price', '0');
        $html .= self::input('sale_price', 'Sale Price', '0');
        $html .= self::input('currency', 'Currency', 'USD');
        $html .= '<div class="form-group"><label class="control-label col-lg-3">Active</label><div class="col-lg-9"><select class="form-control" name="active" style="max-width:160px;"><option value="1">Active</option><option value="0">Passive</option></select></div></div>';
        $html .= '<button type="submit" class="btn btn-primary">SSL Mapping Kaydet</button>';
        $html .= '</form>';
        return $html;
    }

    protected static function input($name, $label, $value)
    {
        return '<div class="form-group"><label class="control-label col-lg-3">' . Tools::safeOutput($label) . '</label><div class="col-lg-9"><input class="form-control" name="' . Tools::safeOutput($name) . '" value="' . Tools::safeOutput($value) . '" style="max-width:260px;"></div></div>';
    }

    protected static function select($name, $label, array $options, $selected)
    {
        $html = '<div class="form-group"><label class="control-label col-lg-3">' . Tools::safeOutput($label) . '</label><div class="col-lg-9"><select class="form-control" name="' . Tools::safeOutput($name) . '" style="max-width:260px;">';
        foreach ($options as $option) {
            $html .= '<option value="' . Tools::safeOutput($option) . '"' . ($option === $selected ? ' selected' : '') . '>' . Tools::safeOutput($option) . '</option>';
        }
        return $html . '</select></div></div>';
    }

    protected static function productName($idProduct)
    {
        if ($idProduct <= 0 || !class_exists('Product')) {
            return '';
        }

        $idLang = (int)Configuration::get('PS_LANG_DEFAULT');
        $name = Product::getProductName($idProduct, null, $idLang);
        return $name ? $name : '';
    }
}
