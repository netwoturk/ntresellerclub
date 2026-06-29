<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/admin/NtRcAdminBaseController.php';
require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcInstaller.php';
require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcManualExchangeRate.php';
require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcTrPriceManager.php';
require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcTrPriceCalculator.php';
require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcFeature.php';
require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/providers/NtRcProviderFactory.php';

class AdminNtRcSettingsController extends NtRcAdminBaseController
{
    protected $ntRcSection = 'settings';

    const CFG_RC_ENABLED = 'NTRC_FEATURE_RESELLERCLUB';
    const CFG_RC_LIVE_MODE = 'NTRC_LIVE_MODE';
    const CFG_RC_AUTH_USERID = 'NTRC_RESELLER_ID';
    const CFG_RC_API_KEY = 'NTRC_API_KEY';
    const CFG_RC_RESELLER_ID = 'NTRC_RC_RESELLER_ID';
    const CFG_RC_LANG_PREF = 'NTRC_LANG_PREF';
    const CFG_DNA_ENABLED = 'NTRC_FEATURE_DOMAINNAMEAPI';
    const CFG_DNA_USERNAME = 'NTRC_DNA_USERNAME';
    const CFG_DNA_PASSWORD = 'NTRC_DNA_PASSWORD';
    const CFG_DNA_TEST_MODE = 'NTRC_DNA_TEST_MODE';
    const CFG_LICENSE_KEY = 'NTRC_LICENSE_KEY';
    const CFG_DOMAIN_PRODUCT_ID = 'NTRC_DOMAIN_PRODUCT_ID';
    const CFG_TR_DOMAIN_PRODUCT_ID = 'NTRC_TR_DOMAIN_PRODUCT_ID';
    const CFG_MEMORY_LIMIT = 'NTRC_MEMORY_LIMIT';
    const CFG_TIME_LIMIT = 'NTRC_TIME_LIMIT';
    const CFG_CRON_BATCH_LIMIT = 'NTRC_CRON_BATCH_LIMIT';
    const CFG_CRON_TOKEN = 'NTRC_CRON_TOKEN';

    public function postProcess()
    {
        parent::postProcess();

        if (Tools::isSubmit('testNtRcResellerClub')) {
            $this->handleConnectionTest('resellerclub');
            return;
        }

        if (Tools::isSubmit('testNtRcDomainNameApi')) {
            $this->handleConnectionTest('domainnameapi');
            return;
        }

        if (Tools::isSubmit('submitNtRcSeedTrPrices')) {
            $this->handleSeedTrPrices();
            return;
        }

        if (Tools::isSubmit('submitNtRcApiSettings')) {
            $this->handleSaveSettings();
        }
    }

    protected function renderSectionContent()
    {
        if (!$this->hasPermission('view')) {
            return NtRcAdminWidget::alert('danger', 'Bu sayfayı görüntüleme yetkiniz yok.');
        }

        $html = '<form method="post" action="' . NtRcAdminThemeHelper::esc($this->context->link->getAdminLink($this->controller_name)) . '" class="ntrc-settings-v1">';
        $html .= '<input type="hidden" name="token" value="' . NtRcAdminThemeHelper::esc($this->currentAdminToken()) . '">';
        $html .= $this->renderSetupStatusBlock();
        $html .= $this->renderApiConnectionsBlock();
        $html .= $this->renderDomainSalesBlock();
        $html .= $this->renderPriceAndCurrencyBlock();
        $html .= $this->renderSystemBlock();
        $html .= $this->renderAdvancedBlock();
        $html .= '</form>';

        return $html;
    }

    protected function handleSaveSettings()
    {
        if (!$this->hasPermission('edit') || !$this->isValidCsrfToken()) {
            $this->flash('error', 'Geçersiz token veya yetki yok.');
            return;
        }

        Configuration::updateValue(self::CFG_LICENSE_KEY, $this->cleanValue(self::CFG_LICENSE_KEY));
        Configuration::updateValue(self::CFG_RC_ENABLED, $this->boolValue(self::CFG_RC_ENABLED));
        Configuration::updateValue(self::CFG_RC_LIVE_MODE, $this->modeValue('nt_rc_mode') === 'live' ? 1 : 0);
        Configuration::updateValue(self::CFG_RC_AUTH_USERID, $this->cleanValue(self::CFG_RC_AUTH_USERID));
        Configuration::updateValue(self::CFG_RC_RESELLER_ID, $this->cleanValue(self::CFG_RC_RESELLER_ID));
        Configuration::updateValue(self::CFG_RC_LANG_PREF, $this->cleanValue(self::CFG_RC_LANG_PREF, 'en'));

        $apiKey = $this->cleanValue(self::CFG_RC_API_KEY);
        if ($apiKey !== '' && !$this->isMaskedValue($apiKey)) {
            Configuration::updateValue(self::CFG_RC_API_KEY, $apiKey);
        }

        Configuration::updateValue(self::CFG_DNA_ENABLED, $this->boolValue(self::CFG_DNA_ENABLED));
        Configuration::updateValue(self::CFG_DNA_TEST_MODE, $this->modeValue('nt_dna_mode') === 'sandbox' ? 1 : 0);
        Configuration::updateValue(self::CFG_DNA_USERNAME, $this->cleanValue(self::CFG_DNA_USERNAME));

        $password = $this->cleanValue(self::CFG_DNA_PASSWORD);
        if ($password !== '' && !$this->isMaskedValue($password)) {
            Configuration::updateValue(self::CFG_DNA_PASSWORD, $password);
        }

        Configuration::updateValue(self::CFG_DOMAIN_PRODUCT_ID, max(0, (int)Tools::getValue(self::CFG_DOMAIN_PRODUCT_ID)));
        Configuration::updateValue(self::CFG_TR_DOMAIN_PRODUCT_ID, max(0, (int)Tools::getValue(self::CFG_TR_DOMAIN_PRODUCT_ID)));
        $this->saveRuntimeSettings();
        $this->saveManualRateIfProvided();
        $this->saveTrPricesIfProvided();

        $this->flash('success', 'Ayarlar kaydedildi.');
    }

    protected function handleSeedTrPrices()
    {
        if (!$this->hasPermission('edit') || !$this->isValidCsrfToken()) {
            $this->flash('error', 'Geçersiz token veya yetki yok.');
            return;
        }

        foreach (NtRcTrPriceManager::allowedTlds() as $tld) {
            NtRcTrPriceManager::upsertCost($tld, 'USD', array(
                'register' => 0,
                'transfer' => 0,
                'renew' => 0,
                'restore' => 0,
                'trustee' => 0,
                'backorder' => 0,
            ));
        }

        $this->flash('success', 'DomainNameAPI TR fiyat satırları hazırlandı.');
    }

    protected function handleConnectionTest($providerCode)
    {
        if (!$this->hasPermission('edit') || !$this->isValidCsrfToken()) {
            $this->flash('error', 'Geçersiz token veya yetki yok.');
            return;
        }

        $result = $providerCode === 'resellerclub'
            ? $this->testResellerClub()
            : $this->testDomainNameApi();

        $this->recordProviderHealth($providerCode, $result);

        if (!empty($result['success'])) {
            $this->flash('success', $result['message']);
            return;
        }

        $this->flash('error', $result['message']);
    }

    protected function testResellerClub()
    {
        $authUserId = trim((string)Configuration::get(self::CFG_RC_AUTH_USERID));
        $apiKey = trim((string)Configuration::get(self::CFG_RC_API_KEY));

        if ($authUserId === '' || $apiKey === '') {
            return array('success' => false, 'message' => 'ResellerClub bilgileri eksik.');
        }

        $provider = NtRcProviderFactory::make('resellerclub', false);
        if (!$provider) {
            return array('success' => false, 'message' => 'ResellerClub bağlantısı hazırlanamadı.');
        }

        $response = $provider->searchCustomer('ntresellerclub-connection-test@example.invalid');
        if (!empty($response['success'])) {
            return array('success' => true, 'message' => 'ResellerClub bağlantı testi başarılı.');
        }

        return array('success' => false, 'message' => $this->responseError($response, 'ResellerClub bağlantı testi başarısız.'));
    }

    protected function testDomainNameApi()
    {
        $username = trim((string)Configuration::get(self::CFG_DNA_USERNAME));
        $password = trim((string)Configuration::get(self::CFG_DNA_PASSWORD));

        if ($username === '' || $password === '') {
            return array('success' => false, 'message' => 'DomainNameAPI bilgileri eksik.');
        }

        $provider = NtRcProviderFactory::make('domainnameapi', false);
        if (!$provider) {
            return array('success' => false, 'message' => 'DomainNameAPI bağlantısı hazırlanamadı.');
        }

        $response = $provider->getTrPrices();
        if (!empty($response['success'])) {
            return array('success' => true, 'message' => 'DomainNameAPI bağlantı testi başarılı.');
        }

        return array('success' => false, 'message' => $this->responseError($response, 'DomainNameAPI bağlantı testi başarısız.'));
    }

    protected function renderSetupStatusBlock()
    {
        $items = array(
            array('Lisans', Configuration::get(self::CFG_LICENSE_KEY) !== '', Configuration::get(self::CFG_LICENSE_KEY) !== '' ? 'Lisans anahtarı girilmiş' : 'Lisans anahtarı eksik'),
            array('ResellerClub', (bool)Configuration::get(self::CFG_RC_ENABLED), Configuration::get(self::CFG_RC_ENABLED) ? 'Aktif' : 'Pasif'),
            array('DomainNameAPI', (bool)Configuration::get(self::CFG_DNA_ENABLED), Configuration::get(self::CFG_DNA_ENABLED) ? 'Aktif' : 'Pasif'),
            array('Global domain ürünü', (int)Configuration::get(self::CFG_DOMAIN_PRODUCT_ID) > 0, $this->productStatusText((int)Configuration::get(self::CFG_DOMAIN_PRODUCT_ID))),
            array('TR domain ürünü', (int)Configuration::get(self::CFG_TR_DOMAIN_PRODUCT_ID) > 0, $this->productStatusText((int)Configuration::get(self::CFG_TR_DOMAIN_PRODUCT_ID))),
            array('Cron URL', Configuration::get(self::CFG_CRON_TOKEN) !== '', Configuration::get(self::CFG_CRON_TOKEN) !== '' ? 'Hazır' : 'Eksik'),
        );

        $html = $this->blockStart('Kurulum Durumu', 'Satışa başlamadan önce temel bağlantı ve ürün eşleşmelerini buradan kontrol edin.');
        $html .= '<div class="ntrc-status-list">';
        foreach ($items as $item) {
            $html .= '<div class="ntrc-status-row"><span>' . $this->badge($item[1] ? 'success' : 'warning', $item[1] ? 'Hazır' : 'Kontrol') . '</span>';
            $html .= '<strong>' . $this->esc($item[0]) . '</strong><small>' . $this->esc($item[2]) . '</small></div>';
        }
        $html .= '</div>';
        $html .= '<div class="ntrc-muted-line">Son cron çalışma zamanı: <strong>' . $this->esc($this->lastCronAt()) . '</strong></div>';
        return $html . $this->blockEnd();
    }

    protected function renderApiConnectionsBlock()
    {
        $html = $this->blockStart('API Bağlantıları', 'Sağlayıcı bilgilerini girin. Bağlantı testi sadece ilgili butona bastığınızda provider API çağrısı yapar.');
        $html .= '<div class="ntrc-card-grid ntrc-two-col">';
        $html .= '<div class="ntrc-admin-card"><h4>ResellerClub</h4>';
        $html .= $this->renderSwitch(self::CFG_RC_ENABLED, 'Durum', Configuration::get(self::CFG_RC_ENABLED));
        $html .= $this->renderModeSelect('nt_rc_mode', 'Mod', Configuration::get(self::CFG_RC_LIVE_MODE) ? 'live' : 'sandbox');
        $html .= $this->renderTextInput(self::CFG_RC_RESELLER_ID, 'Reseller ID', Configuration::get(self::CFG_RC_RESELLER_ID));
        $html .= $this->renderTextInput(self::CFG_RC_AUTH_USERID, 'Auth User ID', Configuration::get(self::CFG_RC_AUTH_USERID));
        $html .= $this->renderSecretInput(self::CFG_RC_API_KEY, 'API Key', Configuration::get(self::CFG_RC_API_KEY));
        $html .= '<button type="submit" name="testNtRcResellerClub" class="btn btn-default">ResellerClub bağlantısını test et</button>';
        $html .= $this->renderProviderStatus('resellerclub');
        $html .= '</div>';
        $html .= '<div class="ntrc-admin-card"><h4>DomainNameAPI</h4>';
        $html .= $this->renderSwitch(self::CFG_DNA_ENABLED, 'Durum', Configuration::get(self::CFG_DNA_ENABLED));
        $html .= $this->renderModeSelect('nt_dna_mode', 'Mod', Configuration::get(self::CFG_DNA_TEST_MODE) ? 'sandbox' : 'live');
        $html .= $this->renderTextInput(self::CFG_DNA_USERNAME, 'Kullanıcı adı', Configuration::get(self::CFG_DNA_USERNAME));
        $html .= $this->renderSecretInput(self::CFG_DNA_PASSWORD, 'Şifre / API credential', Configuration::get(self::CFG_DNA_PASSWORD));
        $html .= '<button type="submit" name="testNtRcDomainNameApi" class="btn btn-default">DomainNameAPI bağlantısını test et</button>';
        $html .= $this->renderProviderStatus('domainnameapi');
        $html .= '</div></div>';
        return $html . $this->blockEnd();
    }

    protected function renderDomainSalesBlock()
    {
        $domainProduct = (int)Configuration::get(self::CFG_DOMAIN_PRODUCT_ID);
        $trProduct = (int)Configuration::get(self::CFG_TR_DOMAIN_PRODUCT_ID);
        $html = $this->blockStart('Domain Satış Ayarları', 'Arama sonucundan sepete domain eklemek için PrestaShop ürün eşleşmeleri gereklidir.');
        $html .= '<div class="ntrc-card-grid ntrc-two-col">';
        $html .= '<div class="ntrc-admin-card">' . $this->renderTextInput(self::CFG_DOMAIN_PRODUCT_ID, 'Global Domain PrestaShop Product ID', $domainProduct) . '<p>' . $this->badge($this->productStatusBadge($domainProduct), $this->productStatusText($domainProduct)) . '</p></div>';
        $html .= '<div class="ntrc-admin-card">' . $this->renderTextInput(self::CFG_TR_DOMAIN_PRODUCT_ID, 'TR Domain PrestaShop Product ID', $trProduct) . '<p>' . $this->badge($this->productStatusBadge($trProduct), $this->productStatusText($trProduct)) . '</p></div>';
        $html .= '</div><div class="ntrc-quick-actions">';
        $html .= '<a class="btn btn-default" href="' . $this->esc($this->context->link->getModuleLink('ntresellerclub', 'domainsearch')) . '" target="_blank">Domain arama sayfası</a>';
        $html .= '<a class="btn btn-default" href="' . $this->esc($this->context->link->getModuleLink('ntresellerclub', 'services')) . '" target="_blank">Müşteri hizmetlerim sayfası</a>';
        $html .= '</div>';
        return $html . $this->blockEnd();
    }

    protected function renderPriceAndCurrencyBlock()
    {
        $usdTry = NtRcManualExchangeRate::getRate('USD', 'TRY');
        $html = $this->blockStart('Fiyat ve Kur', 'TR domain satış fiyatları manuel kur ve fiyat tablosuna göre hesaplanır.');
        $html .= '<div class="ntrc-admin-card ntrc-inline-fields">';
        $html .= '<label>USD → TRY manuel kur</label><input class="form-control" name="nt_rate_value" value="' . $this->esc($usdTry) . '">';
        $html .= '<input type="hidden" name="nt_rate_from" value="USD"><input type="hidden" name="nt_rate_to" value="TRY">';
        $html .= '<button type="submit" name="submitNtRcSeedTrPrices" class="btn btn-default">DomainNameAPI TR fiyatlarını oluştur</button>';
        $html .= '</div>';
        $html .= $this->renderTrPriceTable();
        return $html . $this->blockEnd();
    }

    protected function renderSystemBlock()
    {
        $cronUrl = $this->cronUrl();
        $html = $this->blockStart('Sistem', 'Paylaşımlı hostinglerde işlemleri kontrollü çalıştırmak için limitleri sade tutun.');
        $html .= '<div class="ntrc-card-grid ntrc-three-col">';
        $html .= '<div class="ntrc-admin-card">' . $this->renderTextInput(self::CFG_MEMORY_LIMIT, 'Memory limit', Configuration::get(self::CFG_MEMORY_LIMIT) ?: '512M') . '</div>';
        $html .= '<div class="ntrc-admin-card">' . $this->renderTextInput(self::CFG_TIME_LIMIT, 'Time limit saniye', Configuration::get(self::CFG_TIME_LIMIT) ?: 120) . '</div>';
        $html .= '<div class="ntrc-admin-card">' . $this->renderTextInput(self::CFG_CRON_BATCH_LIMIT, 'Cron batch limit', Configuration::get(self::CFG_CRON_BATCH_LIMIT) ?: 10) . '<small>En fazla 25 uygulanır.</small></div>';
        $html .= '</div>';
        $html .= '<div class="ntrc-copy-row"><label>Cron URL</label><input class="form-control" readonly value="' . $this->esc($cronUrl) . '"><button type="button" class="btn btn-default" onclick="if(navigator.clipboard){navigator.clipboard.writeText(this.previousElementSibling.value);}">Cron URL kopyala</button></div>';
        $html .= '<div class="panel-footer ntrc-save-footer"><button type="submit" name="submitNtRcApiSettings" class="btn btn-primary">Ayarları kaydet</button></div>';
        return $html . $this->blockEnd();
    }

    protected function renderAdvancedBlock()
    {
        $btk = NtRcFeature::isBtkCsvReportingActive() ? 'Aktif' : 'Premium özellik';
        $html = $this->blockStart('Gelişmiş Ayarlar', 'Ana satış akışında gerekmeyen bölümler burada kısa tutulur.');
        $html .= '<div class="ntrc-card-grid ntrc-two-col">';
        $html .= '<div class="ntrc-admin-card"><h4>SSL Mapping</h4><p>SSL ürün eşleşmeleri ayrı SSL ekranından yönetilir.</p><a class="btn btn-default" href="' . $this->esc($this->context->link->getAdminLink('AdminNtRcSsl')) . '">SSL ayarlarına git</a></div>';
        $html .= '<div class="ntrc-admin-card"><h4>BTK CSV</h4><p>' . $this->esc($btk) . '</p></div>';
        $html .= '</div>';
        return $html . $this->blockEnd();
    }

    protected function renderTrPriceTable()
    {
        $rows = NtRcTrPriceManager::all();
        $html = '<div class="table-responsive"><table class="table ntrc-table ntrc-simple-price-table"><thead><tr><th>Uzantı / işlem</th><th>Alış</th><th>Döviz</th><th>Satış</th><th>Kar modu</th><th>Yüzde</th><th>Sabit</th></tr></thead><tbody>';
        foreach ((array)$rows as $row) {
            $id = (int)$row['id_ntresellerclub_price'];
            $calc = NtRcTrPriceCalculator::calculate($row);
            $saleInfo = !empty($calc['success']) ? $calc['sale_price'] . ' ' . $calc['target_currency'] : '-';
            $html .= '<tr>';
            $html .= '<td>' . $this->esc($row['code']) . '<input type="hidden" name="tr_price[' . $id . '][code]" value="' . $this->esc($row['code']) . '"></td>';
            $html .= '<td>' . $this->esc($row['cost_price']) . '</td>';
            $html .= '<td>' . $this->esc($row['currency']) . '</td>';
            $html .= '<td><input class="form-control" name="tr_price[' . $id . '][sale_price]" value="' . $this->esc($row['sale_price']) . '"><small>' . $this->esc($saleInfo) . '</small></td>';
            $html .= '<td><select class="form-control" name="tr_price[' . $id . '][margin_mode]">' . $this->marginModeOptions($row['margin_mode']) . '</select></td>';
            $html .= '<td><input class="form-control" name="tr_price[' . $id . '][margin_percent]" value="' . $this->esc($row['margin_percent']) . '"></td>';
            $html .= '<td><input class="form-control" name="tr_price[' . $id . '][margin_fixed]" value="' . $this->esc($row['margin_fixed']) . '"></td>';
            $html .= '</tr>';
        }
        if (!$rows) {
            $html .= '<tr><td colspan="7">Henüz TR domain fiyat kaydı yok.</td></tr>';
        }
        return $html . '</tbody></table></div>';
    }

    protected function renderProviderStatus($providerCode)
    {
        $row = $this->latestHealth($providerCode);
        $status = isset($row['status']) ? $row['status'] : 'not_checked';
        $checkedAt = !empty($row['checked_at']) ? $row['checked_at'] : '-';
        $error = !empty($row['last_error']) ? $row['last_error'] : '-';
        return '<div class="ntrc-provider-result"><span>' . $this->badge($status === 'success' ? 'success' : ($status === 'failed' ? 'danger' : 'default'), $this->providerStatusLabel($status)) . '</span><small>Son kontrol: ' . $this->esc($checkedAt) . '</small><small>Hata: ' . $this->esc($error) . '</small></div>';
    }

    protected function renderSwitch($name, $label, $checked)
    {
        $html = '<div class="form-group ntrc-form-row"><label>' . $this->esc($label) . '</label><div class="ntrc-segmented">';
        $html .= '<label><input type="radio" name="' . $this->esc($name) . '" value="1"' . ((int)$checked ? ' checked="checked"' : '') . '> Aktif</label>';
        $html .= '<label><input type="radio" name="' . $this->esc($name) . '" value="0"' . (!(int)$checked ? ' checked="checked"' : '') . '> Pasif</label>';
        return $html . '</div></div>';
    }

    protected function renderModeSelect($name, $label, $selected)
    {
        $html = '<div class="form-group ntrc-form-row"><label>' . $this->esc($label) . '</label><select class="form-control" name="' . $this->esc($name) . '">';
        foreach (array('sandbox' => 'Test modu', 'live' => 'Canlı mod') as $value => $title) {
            $html .= '<option value="' . $this->esc($value) . '"' . ($selected === $value ? ' selected="selected"' : '') . '>' . $this->esc($title) . '</option>';
        }
        return $html . '</select></div>';
    }

    protected function renderTextInput($name, $label, $value)
    {
        return '<div class="form-group ntrc-form-row"><label>' . $this->esc($label) . '</label><input class="form-control" type="text" name="' . $this->esc($name) . '" value="' . $this->esc($value) . '"></div>';
    }

    protected function renderSecretInput($name, $label, $value)
    {
        $masked = $this->maskSecret($value);
        return '<div class="form-group ntrc-form-row"><label>' . $this->esc($label) . '</label><input class="form-control" type="password" name="' . $this->esc($name) . '" value="" placeholder="' . $this->esc($masked) . '"><small>Boş bırakırsanız mevcut değer korunur.</small></div>';
    }

    protected function blockStart($title, $description)
    {
        return '<section class="' . NtRcAdminThemeHelper::panelClass() . ' ntrc-settings-block"><div class="ntrc-block-head"><h3>' . $this->esc($title) . '</h3><p>' . $this->esc($description) . '</p></div>';
    }

    protected function blockEnd()
    {
        return '</section>';
    }

    protected function saveRuntimeSettings()
    {
        $memory = trim((string)Tools::getValue(self::CFG_MEMORY_LIMIT, '512M'));
        $time = (int)Tools::getValue(self::CFG_TIME_LIMIT, 120);
        $batch = (int)Tools::getValue(self::CFG_CRON_BATCH_LIMIT, 10);
        if ($memory === '') {
            $memory = '512M';
        }
        $time = min(300, max(30, $time));
        $batch = min(25, max(1, $batch));
        Configuration::updateValue(self::CFG_MEMORY_LIMIT, $memory);
        Configuration::updateValue(self::CFG_TIME_LIMIT, $time);
        Configuration::updateValue(self::CFG_CRON_BATCH_LIMIT, $batch);
    }

    protected function saveManualRateIfProvided()
    {
        $rate = trim((string)Tools::getValue('nt_rate_value'));
        if ($rate === '') {
            return;
        }
        NtRcManualExchangeRate::setRate('USD', 'TRY', $rate);
    }

    protected function saveTrPricesIfProvided()
    {
        $rows = Tools::getValue('tr_price', array());
        foreach ((array)$rows as $row) {
            if (empty($row['code'])) {
                continue;
            }
            $parts = explode(':', $row['code']);
            if (count($parts) !== 2) {
                continue;
            }
            NtRcTrPriceManager::setSalePrice(
                $parts[0],
                $parts[1],
                isset($row['sale_price']) ? $row['sale_price'] : 0,
                isset($row['margin_mode']) ? $row['margin_mode'] : 'manual',
                isset($row['margin_percent']) ? $row['margin_percent'] : 0,
                isset($row['margin_fixed']) ? $row['margin_fixed'] : 0
            );
        }
    }

    protected function recordProviderHealth($providerCode, array $result)
    {
        NtRcInstaller::ensureMonitoringSchema();
        $status = !empty($result['success']) ? 'success' : 'failed';
        return Db::getInstance()->insert('ntresellerclub_provider_health', array(
            'provider_code' => pSQL($providerCode),
            'status' => pSQL($status),
            'is_enabled' => $providerCode === 'resellerclub' ? (int)Configuration::get(self::CFG_RC_ENABLED) : (int)Configuration::get(self::CFG_DNA_ENABLED),
            'is_licensed' => $providerCode === 'resellerclub' ? (int)Configuration::get(self::CFG_RC_ENABLED) : (int)Configuration::get(self::CFG_DNA_ENABLED),
            'queue_pending' => 0,
            'queue_failed' => 0,
            'last_error' => !empty($result['success']) ? null : pSQL($this->safeText($result['message'])),
            'response_time_ms' => 0,
            'checked_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    protected function latestHealth($providerCode)
    {
        NtRcInstaller::ensureMonitoringSchema();
        $row = Db::getInstance()->getRow(
            'SELECT status, last_error, checked_at FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider_health` '
            . 'WHERE provider_code="' . pSQL($providerCode) . '" ORDER BY checked_at DESC, id_ntresellerclub_provider_health DESC'
        );
        if (!is_array($row)) {
            return array();
        }
        $row['last_error'] = isset($row['last_error']) ? $this->safeText($row['last_error']) : '';
        return $row;
    }

    protected function lastCronAt()
    {
        $value = Configuration::get('NTRC_LAST_CRON_AT');
        if ($value) {
            return $value;
        }
        NtRcInstaller::ensureMonitoringSchema();
        $row = Db::getInstance()->getRow('SELECT last_cron_at FROM `' . _DB_PREFIX_ . 'ntresellerclub_runtime_health` WHERE last_cron_at IS NOT NULL ORDER BY checked_at DESC, id_ntresellerclub_runtime_health DESC');
        return !empty($row['last_cron_at']) ? $row['last_cron_at'] : '-';
    }

    protected function productStatusText($idProduct)
    {
        if ((int)$idProduct <= 0) {
            return 'Eksik';
        }
        $product = new Product((int)$idProduct, false, $this->context && $this->context->language ? (int)$this->context->language->id : null);
        if (!Validate::isLoadedObject($product)) {
            return 'Eksik';
        }
        return (int)$product->active === 1 ? 'Ayarlı' : 'Pasif';
    }

    protected function productStatusBadge($idProduct)
    {
        $text = $this->productStatusText($idProduct);
        if ($text === 'Ayarlı') {
            return 'success';
        }
        return $text === 'Pasif' ? 'pending' : 'failed';
    }

    protected function marginModeOptions($selected)
    {
        $modes = array('manual' => 'Manuel', 'percent' => 'Yüzde', 'fixed' => 'Sabit', 'hybrid' => 'Hibrit');
        $html = '';
        foreach ($modes as $key => $label) {
            $html .= '<option value="' . $this->esc($key) . '"' . ($selected === $key ? ' selected="selected"' : '') . '>' . $this->esc($label) . '</option>';
        }
        return $html;
    }

    protected function providerStatusLabel($status)
    {
        if ($status === 'success') {
            return 'Başarılı';
        }
        if ($status === 'failed') {
            return 'Başarısız';
        }
        return 'Test edilmedi';
    }

    protected function cronUrl()
    {
        return $this->context->link->getModuleLink('ntresellerclub', 'cron', array('token' => Configuration::get(self::CFG_CRON_TOKEN)));
    }

    protected function boolValue($name)
    {
        return Tools::getValue($name) ? 1 : 0;
    }

    protected function modeValue($name)
    {
        $mode = strtolower(trim((string)Tools::getValue($name)));
        return in_array($mode, array('sandbox', 'live'), true) ? $mode : 'sandbox';
    }

    protected function cleanValue($name, $default = '')
    {
        $value = trim((string)Tools::getValue($name, $default));
        return $value === '' ? $default : $value;
    }

    protected function isMaskedValue($value)
    {
        return preg_match('/^\*+$/', trim((string)$value));
    }

    protected function maskSecret($value)
    {
        $value = (string)$value;
        if ($value === '') {
            return 'Henüz girilmedi';
        }
        return str_repeat('*', min(12, max(8, strlen($value))));
    }

    protected function responseError(array $response, $fallback)
    {
        foreach (array('message', 'error') as $key) {
            if (!empty($response[$key])) {
                return $this->safeText($response[$key]);
            }
        }
        return $fallback;
    }

    protected function safeText($text)
    {
        $text = (string)$text;
        $text = preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|secret)=([^&\s]+)/i', '$1=***', $text);
        return preg_replace('/("(?:api-key|api_key|auth-code|auth_code|passwd|password|token|credential|secret)"\s*:\s*)"[^"]*"/i', '$1"***"', $text);
    }

    protected function badge($status, $label)
    {
        if ($status === 'warning') {
            $status = 'pending';
        } elseif ($status === 'danger') {
            $status = 'failed';
        }
        return '<span class="badge ' . $this->esc(NtRcAdminThemeHelper::badgeClass($status)) . '">' . $this->esc($label) . '</span>';
    }

    protected function esc($value)
    {
        return NtRcAdminThemeHelper::esc($value);
    }
}
