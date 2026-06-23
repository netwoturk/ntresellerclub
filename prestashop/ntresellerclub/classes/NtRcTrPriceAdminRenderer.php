<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcTrPriceManager.php';

class NtRcTrPriceAdminRenderer
{
    public static function render(Module $module)
    {
        $rows = NtRcTrPriceManager::all();
        $html = '<div class="panel"><h3>TR Domain Fiyatlari</h3>';
        $html .= '<p>DomainNameAPI sadece TR uzantilari icin kullanilir. Global domainler ResellerClub uzerinden calisir.</p>';
        $html .= '<table class="table"><thead><tr>';
        $html .= '<th>Uzanti / Islem</th><th>Alis</th><th>Doviz</th><th>Satis</th><th>Mod</th><th>Yuzde</th><th>Sabit</th><th>Son Sync</th>';
        $html .= '</tr></thead><tbody>';

        foreach ((array)$rows as $row) {
            $html .= '<tr>';
            $html .= '<td>' . Tools::safeOutput($row['code']) . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['cost_price']) . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['currency']) . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['sale_price']) . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['margin_mode']) . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['margin_percent']) . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['margin_fixed']) . '</td>';
            $html .= '<td>' . Tools::safeOutput($row['last_sync']) . '</td>';
            $html .= '</tr>';
        }

        if (!$rows) {
            $html .= '<tr><td colspan="8">Henuz TR domain fiyat kaydi yok.</td></tr>';
        }

        $html .= '</tbody></table>';
        $html .= self::renderSeedForm($module);
        $html .= '</div>';
        return $html;
    }

    protected static function renderSeedForm(Module $module)
    {
        $html = '<form method="post" class="form-inline">';
        $html .= '<input type="hidden" name="submitNtRcSeedTrPrices" value="1">';
        $html .= '<button type="submit" class="btn btn-default">Varsayilan TR Fiyat Satirlarini Olustur</button>';
        $html .= '</form>';
        return $html;
    }
}
