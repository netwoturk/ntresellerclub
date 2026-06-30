<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/admin/NtRcAdminBaseController.php';

class AdminNtRcDomainsController extends NtRcAdminBaseController
{
    protected $ntRcSection = 'domains';

    protected function renderSectionContent()
    {
        return $this->renderDomainServiceList('domain');
    }

    protected function renderDomainServiceList($serviceType)
    {
        $rows = Db::getInstance()->executeS(
            'SELECT s.*, q.status AS queue_status, q.action AS queue_action, q.last_error AS queue_last_error '
            . 'FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` s '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` q ON q.id_service = s.id_ntresellerclub_service '
            . 'AND q.id_ntresellerclub_operation_queue = ('
                . 'SELECT MAX(q2.id_ntresellerclub_operation_queue) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` q2 '
                . 'WHERE q2.id_service = s.id_ntresellerclub_service'
            . ') '
            . 'WHERE s.service_type="' . pSQL($serviceType) . '" '
            . 'ORDER BY s.created_at DESC LIMIT 100'
        );

        $html = '<section class="' . NtRcAdminThemeHelper::panelClass() . '">';
        $html .= '<h3>' . ($serviceType === 'tr_domain' ? 'TR Domain Servisleri' : 'Domain Servisleri') . '</h3>';
        $html .= '<table class="table"><thead><tr><th>ID</th><th>Domain</th><th>Provider</th><th>Servis</th><th>Queue</th><th>Aksiyon</th><th>Bitiş</th><th>Son Hata</th></tr></thead><tbody>';
        foreach ((array)$rows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . (int)$row['id_ntresellerclub_service'] . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['domain_name']) . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['provider_code']) . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['status']) . '</td>';
            $html .= '<td>' . Tools::safeOutput(!empty($row['queue_status']) ? $row['queue_status'] : '-') . '</td>';
            $html .= '<td>' . Tools::safeOutput(!empty($row['queue_action']) ? $row['queue_action'] : '-') . '</td>';
            $html .= '<td>' . Tools::safeOutput(!empty($row['expiry_date']) ? $row['expiry_date'] : '-') . '</td>';
            $html .= '<td>' . Tools::safeOutput($this->safeAdminText(!empty($row['queue_last_error']) ? $row['queue_last_error'] : '-')) . '</td>';
            $html .= '</tr>';
        }
        if (empty($rows)) {
            $html .= '<tr><td colspan="8">Kayıt bulunamadı.</td></tr>';
        }
        return $html . '</tbody></table></section>';
    }

    protected function safeAdminText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|secret|credential)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
