<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcManualExchangeRate.php';

class NtRcExchangeRateAdminRenderer
{
    public static function render(Module $module)
    {
        $defaultCurrency = NtRcManualExchangeRate::defaultTargetCurrency();
        $usdTry = NtRcManualExchangeRate::getRate('USD', 'TRY');
        $usdDefault = NtRcManualExchangeRate::getRate('USD', $defaultCurrency);

        $html = '<div class="panel"><h3>Manuel Kur Ayarlari</h3>';
        $html .= '<p>DomainNameAPI maliyetleri USD gelebilir. TR domain satis fiyati hesaplanirken bu manuel kur kullanilir.</p>';
        $html .= '<table class="table"><tbody>';
        $html .= '<tr><td>USD - TRY</td><td>' . Tools::safeOutput($usdTry) . '</td></tr>';
        $html .= '<tr><td>USD - Varsayilan Para Birimi (' . Tools::safeOutput($defaultCurrency) . ')</td><td>' . Tools::safeOutput($usdDefault) . '</td></tr>';
        $html .= '</tbody></table>';
        $html .= self::renderForm();
        $html .= '</div>';
        return $html;
    }

    protected static function renderForm()
    {
        $html = '<form method="post" class="form-inline">';
        $html .= '<input type="hidden" name="submitNtRcManualRate" value="1">';
        $html .= '<label>Kaynak</label> <input type="text" name="nt_rate_from" value="USD" class="form-control" style="width:90px;margin:5px;">';
        $html .= '<label>Hedef</label> <input type="text" name="nt_rate_to" value="TRY" class="form-control" style="width:90px;margin:5px;">';
        $html .= '<label>Kur</label> <input type="text" name="nt_rate_value" value="" class="form-control" style="width:120px;margin:5px;">';
        $html .= '<button type="submit" class="btn btn-default">Kuru Kaydet</button>';
        $html .= '</form>';
        return $html;
    }
}
