<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcRuntimeAdminRenderer
{
    public static function render(Module $module)
    {
        $memory = Configuration::get('NTRC_MEMORY_LIMIT') ?: '512M';
        $time = Configuration::get('NTRC_TIME_LIMIT') ?: 120;
        $batch = Configuration::get('NTRC_CRON_BATCH_LIMIT') ?: 10;

        $html = '<div class="panel"><h3>Hosting / Sunucu Uyumluluk Ayarlari</h3>';
        $html .= '<p>Paylasimli hostinglerde 500 hatasini azaltmak icin agir islemler limitli calisir.</p>';
        $html .= '<form method="post" class="form-horizontal">';
        $html .= '<input type="hidden" name="submitNtRcRuntimeSettings" value="1">';
        $html .= '<div class="form-group"><label class="control-label col-lg-3">Memory Limit</label><div class="col-lg-3"><input class="form-control" name="NTRC_MEMORY_LIMIT" value="' . Tools::safeOutput($memory) . '"></div></div>';
        $html .= '<div class="form-group"><label class="control-label col-lg-3">Time Limit Saniye</label><div class="col-lg-3"><input class="form-control" name="NTRC_TIME_LIMIT" value="' . Tools::safeOutput($time) . '"></div></div>';
        $html .= '<div class="form-group"><label class="control-label col-lg-3">Cron Batch Limit</label><div class="col-lg-3"><input class="form-control" name="NTRC_CRON_BATCH_LIMIT" value="' . Tools::safeOutput($batch) . '"><p class="help-block">Guvenlik icin en fazla 25 uygulanir.</p></div></div>';
        $html .= '<div class="panel-footer"><button type="submit" class="btn btn-default pull-right">Runtime Ayarlarini Kaydet</button></div>';
        $html .= '</form></div>';
        return $html;
    }
}
