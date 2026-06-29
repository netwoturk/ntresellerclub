<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/NtRcApiClient.php';
require_once __DIR__ . '/classes/NtRcLicense.php';
require_once __DIR__ . '/classes/NtRcProvisioning.php';
require_once __DIR__ . '/classes/NtRcFeature.php';
require_once __DIR__ . '/classes/NtRcInstaller.php';
require_once __DIR__ . '/classes/NtRcManualExchangeRate.php';
require_once __DIR__ . '/classes/NtRcPricingManager.php';
require_once __DIR__ . '/classes/NtRcExchangeRateAdminRenderer.php';
require_once __DIR__ . '/classes/NtRcTrPriceAdminRenderer.php';
require_once __DIR__ . '/classes/NtRcTrPriceManager.php';
require_once __DIR__ . '/classes/NtRcRuntimeAdminRenderer.php';
require_once __DIR__ . '/classes/NtRcBtkCsvExportEngine.php';
require_once __DIR__ . '/classes/NtRcSslMappingAdminRenderer.php';
require_once __DIR__ . '/classes/NtRcSslProductMappingManager.php';
require_once __DIR__ . '/classes/NtRcProductionReadinessVerifier.php';
require_once __DIR__ . '/classes/admin/NtRcAdminThemeHelper.php';

class Ntresellerclub extends Module
{
    const CFG_LIVE_MODE = 'NTRC_LIVE_MODE';
    const CFG_RESELLER_ID = 'NTRC_RESELLER_ID';
    const CFG_API_KEY = 'NTRC_API_KEY';
    const CFG_LANG_PREF = 'NTRC_LANG_PREF';
    const CFG_LICENSE_KEY = 'NTRC_LICENSE_KEY';
    const CFG_CRON_TOKEN = 'NTRC_CRON_TOKEN';
    const CFG_DNA_USERNAME = 'NTRC_DNA_USERNAME';
    const CFG_DNA_PASSWORD = 'NTRC_DNA_PASSWORD';
    const CFG_DNA_TEST_MODE = 'NTRC_DNA_TEST_MODE';
    const CFG_FEATURE_CORE = 'NTRC_FEATURE_CORE';
    const CFG_FEATURE_RESELLERCLUB = 'NTRC_FEATURE_RESELLERCLUB';
    const CFG_FEATURE_DOMAINNAMEAPI = 'NTRC_FEATURE_DOMAINNAMEAPI';
    const CFG_FEATURE_HOSTING = 'NTRC_FEATURE_HOSTING';
    const CFG_FEATURE_BTK_CSV_REPORTING = 'NTRC_FEATURE_BTK_CSV_REPORTING';
    const CFG_MEMORY_LIMIT = 'NTRC_MEMORY_LIMIT';
    const CFG_TIME_LIMIT = 'NTRC_TIME_LIMIT';
    const CFG_CRON_BATCH_LIMIT = 'NTRC_CRON_BATCH_LIMIT';
    const CFG_DOMAIN_PRODUCT_ID = 'NTRC_DOMAIN_PRODUCT_ID';
    const CFG_TR_DOMAIN_PRODUCT_ID = 'NTRC_TR_DOMAIN_PRODUCT_ID';

    public function __construct()
    {
        $this->name = 'ntresellerclub';
        $this->tab = 'administration';
        $this->version = '0.1.0';
        $this->author = 'NetwoTurk';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
        parent::__construct();
        $this->displayName = $this->l('NetwoTurk Multi Provider Domain & Hosting Panel');
        $this->description = $this->l('ResellerClub ve DomainNameAPI destekli domain, hosting ve servis otomasyonu.');
    }

    public function install()
    {
        return parent::install()
            && NtRcInstaller::installSql()
            && Configuration::updateValue(self::CFG_LIVE_MODE, 1)
            && Configuration::updateValue(self::CFG_RESELLER_ID, '')
            && Configuration::updateValue(self::CFG_API_KEY, '')
            && Configuration::updateValue(self::CFG_LANG_PREF, 'en')
            && Configuration::updateValue(self::CFG_LICENSE_KEY, '')
            && Configuration::updateValue(self::CFG_CRON_TOKEN, Tools::passwdGen(32))
            && Configuration::updateValue(self::CFG_DNA_USERNAME, '')
            && Configuration::updateValue(self::CFG_DNA_PASSWORD, '')
            && Configuration::updateValue(self::CFG_DNA_TEST_MODE, 1)
            && Configuration::updateValue(self::CFG_FEATURE_CORE, 1)
            && Configuration::updateValue(self::CFG_FEATURE_RESELLERCLUB, 1)
            && Configuration::updateValue(self::CFG_FEATURE_DOMAINNAMEAPI, 0)
            && Configuration::updateValue(self::CFG_FEATURE_HOSTING, 1)
            && Configuration::updateValue(self::CFG_FEATURE_BTK_CSV_REPORTING, 0)
            && Configuration::updateValue(self::CFG_MEMORY_LIMIT, '512M')
            && Configuration::updateValue(self::CFG_TIME_LIMIT, 120)
            && Configuration::updateValue(self::CFG_CRON_BATCH_LIMIT, 10)
            && Configuration::updateValue(self::CFG_DOMAIN_PRODUCT_ID, 0)
            && Configuration::updateValue(self::CFG_TR_DOMAIN_PRODUCT_ID, 0)
            && NtRcManualExchangeRate::ensureDefaultRates()
            && NtRcPricingManager::seedResellerClubMappings('USD')
            && NtRcInstaller::installAdminTabs()
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('displayCustomerAccount')
            && $this->registerHook('displayBackOfficeHeader');
    }

    public function uninstall()
    {
        foreach (array(
            self::CFG_LIVE_MODE, self::CFG_RESELLER_ID, self::CFG_API_KEY, self::CFG_LANG_PREF,
            self::CFG_LICENSE_KEY, self::CFG_CRON_TOKEN, self::CFG_DNA_USERNAME, self::CFG_DNA_PASSWORD,
            self::CFG_DNA_TEST_MODE, self::CFG_FEATURE_CORE, self::CFG_FEATURE_RESELLERCLUB,
            self::CFG_FEATURE_DOMAINNAMEAPI, self::CFG_FEATURE_HOSTING,
            self::CFG_FEATURE_BTK_CSV_REPORTING, self::CFG_MEMORY_LIMIT, self::CFG_TIME_LIMIT,
            self::CFG_CRON_BATCH_LIMIT, self::CFG_DOMAIN_PRODUCT_ID, self::CFG_TR_DOMAIN_PRODUCT_ID
        ) as $key) {
            Configuration::deleteByName($key);
        }
        return NtRcInstaller::uninstallAdminTabs() && parent::uninstall();
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitNtRcBtkCsvExport')) {
            $download = $this->downloadBtkCsv(Tools::getValue('nt_btk_csv_type'));
            if ($download !== null) {
                $output .= $download;
            }
        }
        if (Tools::isSubmit('submitNtRcSettings')) {
            $this->saveSettings();
            $output .= $this->displayConfirmation($this->l('Ayarlar kaydedildi.'));
        }
        if (Tools::isSubmit('submitNtRcManualRate')) {
            $this->saveManualRate();
            $output .= $this->displayConfirmation($this->l('Kur kaydedildi.'));
        }
        if (Tools::isSubmit('submitNtRcRuntimeSettings')) {
            $this->saveRuntimeSettings();
            $output .= $this->displayConfirmation($this->l('Runtime ayarlari kaydedildi.'));
        }
        if (Tools::isSubmit('submitNtRcSeedTrPrices')) {
            $this->seedTrPrices();
            $output .= $this->displayConfirmation($this->l('TR fiyat satirlari olusturuldu.'));
        }
        if (Tools::isSubmit('submitNtRcSaveTrPrices')) {
            $this->saveTrPrices();
            $output .= $this->displayConfirmation($this->l('TR fiyat ayarlari kaydedildi.'));
        }
        if (Tools::isSubmit('submitNtRcSaveSslMapping')) {
            $result = $this->saveSslMapping();
            $output .= !empty($result['success'])
                ? $this->displayConfirmation($this->l('SSL mapping kaydedildi.'))
                : $this->displayError(isset($result['message']) ? $result['message'] : $this->l('SSL mapping kaydedilemedi.'));
        }
        if (Tools::isSubmit('submitNtRcToggleSslMapping')) {
            $result = $this->toggleSslMapping();
            $output .= !empty($result['success'])
                ? $this->displayConfirmation($this->l('SSL mapping durumu guncellendi.'))
                : $this->displayError(isset($result['message']) ? $result['message'] : $this->l('SSL mapping durumu guncellenemedi.'));
        }
        if (Tools::isSubmit('testNtRcApi')) {
            $output .= $this->renderApiTest();
        }
        return $output
            . $this->renderProviderStatus()
            . NtRcRuntimeAdminRenderer::render($this)
            . NtRcExchangeRateAdminRenderer::render($this)
            . NtRcTrPriceAdminRenderer::render($this)
            . NtRcSslMappingAdminRenderer::render($this)
            . $this->renderBtkCsvPanel()
            . $this->renderForm();
    }

    protected function saveManualRate()
    {
        NtRcManualExchangeRate::setRate(
            Tools::getValue('nt_rate_from'),
            Tools::getValue('nt_rate_to'),
            Tools::getValue('nt_rate_value')
        );
    }

    protected function saveRuntimeSettings()
    {
        $memory = trim((string)Tools::getValue(self::CFG_MEMORY_LIMIT, '512M'));
        $time = (int)Tools::getValue(self::CFG_TIME_LIMIT, 120);
        $batch = (int)Tools::getValue(self::CFG_CRON_BATCH_LIMIT, 10);

        if ($memory === '') {
            $memory = '512M';
        }
        if ($time < 30) {
            $time = 30;
        }
        if ($time > 300) {
            $time = 300;
        }
        if ($batch < 1) {
            $batch = 1;
        }
        if ($batch > 25) {
            $batch = 25;
        }

        Configuration::updateValue(self::CFG_MEMORY_LIMIT, $memory);
        Configuration::updateValue(self::CFG_TIME_LIMIT, $time);
        Configuration::updateValue(self::CFG_CRON_BATCH_LIMIT, $batch);
    }

    protected function seedTrPrices()
    {
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
    }

    protected function saveTrPrices()
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

    protected function saveSslMapping()
    {
        if (!$this->isValidSslMappingToken()) {
            return array('success' => false, 'message' => $this->l('Gecersiz SSL mapping token.'));
        }

        return NtRcSslProductMappingManager::upsert(array(
            'id_product' => (int)Tools::getValue('id_product'),
            'provider_product_id' => trim((string)Tools::getValue('provider_product_id')),
            'ssl_product_type' => trim((string)Tools::getValue('ssl_product_type')),
            'billing_cycle' => trim((string)Tools::getValue('billing_cycle')),
            'cost_price' => (float)Tools::getValue('cost_price'),
            'sale_price' => (float)Tools::getValue('sale_price'),
            'currency' => trim((string)Tools::getValue('currency')),
            'active' => (int)Tools::getValue('active', 1),
        ));
    }

    protected function toggleSslMapping()
    {
        if (!$this->isValidSslMappingToken()) {
            return array('success' => false, 'message' => $this->l('Gecersiz SSL mapping token.'));
        }

        return NtRcSslProductMappingManager::toggle((int)Tools::getValue('id_mapping'), (int)Tools::getValue('active'));
    }

    protected function isValidSslMappingToken()
    {
        return Tools::getValue('nt_ssl_mapping_token') === Tools::getAdminTokenLite('AdminModules');
    }

    protected function saveSettings()
    {
        Configuration::updateValue(self::CFG_LIVE_MODE, (int)Tools::getValue(self::CFG_LIVE_MODE));
        Configuration::updateValue(self::CFG_RESELLER_ID, trim(Tools::getValue(self::CFG_RESELLER_ID)));
        $apiKey = trim((string)Tools::getValue(self::CFG_API_KEY));
        if ($apiKey !== '' && !preg_match('/^\*+$/', $apiKey)) {
            Configuration::updateValue(self::CFG_API_KEY, $apiKey);
        }
        Configuration::updateValue(self::CFG_LANG_PREF, trim(Tools::getValue(self::CFG_LANG_PREF)) ?: 'en');
        Configuration::updateValue(self::CFG_LICENSE_KEY, trim(Tools::getValue(self::CFG_LICENSE_KEY)));
        Configuration::updateValue(self::CFG_DNA_USERNAME, trim(Tools::getValue(self::CFG_DNA_USERNAME)));
        $dnaPassword = trim((string)Tools::getValue(self::CFG_DNA_PASSWORD));
        if ($dnaPassword !== '' && !preg_match('/^\*+$/', $dnaPassword)) {
            Configuration::updateValue(self::CFG_DNA_PASSWORD, $dnaPassword);
        }
        Configuration::updateValue(self::CFG_DNA_TEST_MODE, (int)Tools::getValue(self::CFG_DNA_TEST_MODE));
        Configuration::updateValue(self::CFG_FEATURE_CORE, (int)Tools::getValue(self::CFG_FEATURE_CORE));
        Configuration::updateValue(self::CFG_FEATURE_RESELLERCLUB, (int)Tools::getValue(self::CFG_FEATURE_RESELLERCLUB));
        Configuration::updateValue(self::CFG_FEATURE_DOMAINNAMEAPI, (int)Tools::getValue(self::CFG_FEATURE_DOMAINNAMEAPI));
        Configuration::updateValue(self::CFG_FEATURE_HOSTING, (int)Tools::getValue(self::CFG_FEATURE_HOSTING));
        Configuration::updateValue(self::CFG_FEATURE_BTK_CSV_REPORTING, (int)Tools::getValue(self::CFG_FEATURE_BTK_CSV_REPORTING));
        Configuration::updateValue(self::CFG_DOMAIN_PRODUCT_ID, (int)Tools::getValue(self::CFG_DOMAIN_PRODUCT_ID));
        Configuration::updateValue(self::CFG_TR_DOMAIN_PRODUCT_ID, (int)Tools::getValue(self::CFG_TR_DOMAIN_PRODUCT_ID));
    }

    protected function renderProviderStatus()
    {
        $rows = array(
            array('Core Lisans', Configuration::get(self::CFG_FEATURE_CORE) ? 'Aktif' : 'Pasif'),
            array('ResellerClub Provider', Configuration::get(self::CFG_FEATURE_RESELLERCLUB) ? 'Aktif' : 'Pasif'),
            array('DomainNameAPI Provider', Configuration::get(self::CFG_FEATURE_DOMAINNAMEAPI) ? 'Aktif' : 'Pasif'),
            array('Hosting Manager', Configuration::get(self::CFG_FEATURE_HOSTING) ? 'Aktif' : 'Pasif'),
            array('BTK CSV Reporting', NtRcFeature::isBtkCsvReportingActive() ? 'Aktif' : 'Pasif'),
        );
        $html = '<div class="panel"><h3>' . $this->l('Provider ve Lisans Durumu') . '</h3><table class="table"><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr><td>' . Tools::safeOutput($row[0]) . '</td><td><strong>' . Tools::safeOutput($row[1]) . '</strong></td></tr>';
        }
        return $html . '</tbody></table></div>';
    }

    protected function renderApiTest()
    {
        $summary = NtRcProductionReadinessVerifier::summary();
        $message = 'Admin readiness checks: ' . (int)$summary['check_count'] . ', failed: ' . (int)$summary['failed_count'];
        if (!empty($summary['success'])) {
            return $this->displayConfirmation($this->l($message));
        }
        return $this->displayWarning($this->l($message));
    }

    protected function downloadBtkCsv($type)
    {
        if (!NtRcFeature::isBtkCsvReportingActive()) {
            return $this->displayWarning($this->l('BTK CSV Reporting premium özelliği aktif değil. CSV indirme kapalı.'));
        }

        $engine = new NtRcBtkCsvExportEngine();
        if ($type === NtRcBtkCsvExportEngine::TYPE_HOSTED) {
            $csv = $engine->exportHostedDomainsCsv();
            $filename = 'btk-barindirilan-alan-adlari-' . date('Ymd') . '.csv';
        } elseif ($type === NtRcBtkCsvExportEngine::TYPE_REGISTERED_ONLY) {
            $csv = $engine->exportRegisteredOnlyDomainsCsv();
            $filename = 'btk-tescil-edilen-alan-adlari-' . date('Ymd') . '.csv';
        } else {
            return $this->displayError($this->l('Geçersiz BTK CSV rapor tipi.'));
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        die($csv);
    }

    protected function renderBtkCsvPanel()
    {
        $html = '<div class="panel"><h3>' . $this->l('BTK CSV Reporting') . '</h3>';
        if (!NtRcFeature::isBtkCsvReportingActive()) {
            return $html . $this->displayWarning($this->l('BTK CSV Reporting premium özelliği aktif değil.')) . '</div>';
        }

        $action = AdminController::$currentIndex . '&configure=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules');
        $html .= '<p>' . $this->l('BTK formatında başlıksız, 6 kolonlu CSV çıktıları.') . '</p>';
        $html .= '<form method="post" action="' . Tools::safeOutput($action) . '" style="display:inline-block;margin-right:10px;">';
        $html .= '<input type="hidden" name="nt_btk_csv_type" value="' . NtRcBtkCsvExportEngine::TYPE_HOSTED . '">';
        $html .= '<button type="submit" name="submitNtRcBtkCsvExport" class="btn btn-default">' . $this->l('Barındırılan Alan Adları CSV') . '</button>';
        $html .= '</form>';
        $html .= '<form method="post" action="' . Tools::safeOutput($action) . '" style="display:inline-block;">';
        $html .= '<input type="hidden" name="nt_btk_csv_type" value="' . NtRcBtkCsvExportEngine::TYPE_REGISTERED_ONLY . '">';
        $html .= '<button type="submit" name="submitNtRcBtkCsvExport" class="btn btn-default">' . $this->l('Tescil Edilen Alan Adları CSV') . '</button>';
        $html .= '</form>';

        return $html . '</div>';
    }

    protected function renderForm()
    {
        $cronUrl = $this->context->link->getModuleLink($this->name, 'cron', array('token' => Configuration::get(self::CFG_CRON_TOKEN)));
        $fields = array('form' => array(
            'legend' => array('title' => $this->l('Multi Provider API ve Lisans Ayarları')),
            'input' => array(
                array('type' => 'text', 'label' => $this->l('Yıllık Lisans Anahtarı'), 'name' => self::CFG_LICENSE_KEY),
                array('type' => 'switch', 'label' => $this->l('Core Lisans Aktif'), 'name' => self::CFG_FEATURE_CORE, 'is_bool' => true, 'values' => $this->switchValues()),
                array('type' => 'switch', 'label' => $this->l('ResellerClub Provider'), 'name' => self::CFG_FEATURE_RESELLERCLUB, 'is_bool' => true, 'values' => $this->switchValues()),
                array('type' => 'switch', 'label' => $this->l('DomainNameAPI Provider'), 'name' => self::CFG_FEATURE_DOMAINNAMEAPI, 'is_bool' => true, 'values' => $this->switchValues()),
                array('type' => 'switch', 'label' => $this->l('Hosting Manager'), 'name' => self::CFG_FEATURE_HOSTING, 'is_bool' => true, 'values' => $this->switchValues()),
                array('type' => 'switch', 'label' => $this->l('BTK CSV Reporting Premium'), 'name' => self::CFG_FEATURE_BTK_CSV_REPORTING, 'is_bool' => true, 'values' => $this->switchValues()),
                array('type' => 'switch', 'label' => $this->l('ResellerClub Live Modu'), 'name' => self::CFG_LIVE_MODE, 'is_bool' => true, 'values' => $this->switchValues()),
                array('type' => 'text', 'label' => $this->l('ResellerClub Reseller ID'), 'name' => self::CFG_RESELLER_ID),
                array('type' => 'password', 'label' => $this->l('ResellerClub API Key'), 'name' => self::CFG_API_KEY),
                array('type' => 'text', 'label' => $this->l('ResellerClub Dil'), 'name' => self::CFG_LANG_PREF),
                array('type' => 'text', 'label' => $this->l('DomainNameAPI Kullanıcı Adı'), 'name' => self::CFG_DNA_USERNAME),
                array('type' => 'password', 'label' => $this->l('DomainNameAPI Şifre'), 'name' => self::CFG_DNA_PASSWORD),
                array('type' => 'switch', 'label' => $this->l('DomainNameAPI Test Modu'), 'name' => self::CFG_DNA_TEST_MODE, 'is_bool' => true, 'values' => $this->switchValues()),
                array('type' => 'text', 'label' => $this->l('Global Domain Product ID'), 'name' => self::CFG_DOMAIN_PRODUCT_ID),
                array('type' => 'text', 'label' => $this->l('TR Domain Product ID'), 'name' => self::CFG_TR_DOMAIN_PRODUCT_ID),
                array('type' => 'text', 'label' => $this->l('Cron URL'), 'name' => 'NTRC_CRON_URL', 'readonly' => true),
            ),
            'submit' => array('title' => $this->l('Kaydet'), 'name' => 'submitNtRcSettings'),
            'buttons' => array(array('title' => $this->l('Admin Readiness Kontrol Et'), 'name' => 'testNtRcApi', 'type' => 'submit', 'class' => 'btn btn-default pull-right')),
        ));
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submitNtRcSettings';
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value = $this->formValues($cronUrl);
        return $helper->generateForm(array($fields));
    }

    protected function switchValues()
    {
        return array(
            array('id' => 'on', 'value' => 1, 'label' => $this->l('Aktif')),
            array('id' => 'off', 'value' => 0, 'label' => $this->l('Pasif')),
        );
    }

    protected function formValues($cronUrl)
    {
        return array(
            self::CFG_LIVE_MODE => Configuration::get(self::CFG_LIVE_MODE),
            self::CFG_RESELLER_ID => Configuration::get(self::CFG_RESELLER_ID),
            self::CFG_API_KEY => '',
            self::CFG_LANG_PREF => Configuration::get(self::CFG_LANG_PREF),
            self::CFG_LICENSE_KEY => Configuration::get(self::CFG_LICENSE_KEY),
            self::CFG_DNA_USERNAME => Configuration::get(self::CFG_DNA_USERNAME),
            self::CFG_DNA_PASSWORD => '',
            self::CFG_DNA_TEST_MODE => Configuration::get(self::CFG_DNA_TEST_MODE),
            self::CFG_FEATURE_CORE => Configuration::get(self::CFG_FEATURE_CORE),
            self::CFG_FEATURE_RESELLERCLUB => Configuration::get(self::CFG_FEATURE_RESELLERCLUB),
            self::CFG_FEATURE_DOMAINNAMEAPI => Configuration::get(self::CFG_FEATURE_DOMAINNAMEAPI),
            self::CFG_FEATURE_HOSTING => Configuration::get(self::CFG_FEATURE_HOSTING),
            self::CFG_FEATURE_BTK_CSV_REPORTING => Configuration::get(self::CFG_FEATURE_BTK_CSV_REPORTING),
            self::CFG_DOMAIN_PRODUCT_ID => Configuration::get(self::CFG_DOMAIN_PRODUCT_ID),
            self::CFG_TR_DOMAIN_PRODUCT_ID => Configuration::get(self::CFG_TR_DOMAIN_PRODUCT_ID),
            'NTRC_CRON_URL' => $cronUrl,
        );
    }

    public function hookActionValidateOrder($params)
    {
        if (!isset($params['order']) || !Validate::isLoadedObject($params['order'])) {
            return;
        }
        if (!NtRcLicense::isActive()) {
            return;
        }
        $engine = new NtRcProvisioning($this);
        $engine->processOrder((int)$params['order']->id);
    }

    public function hookDisplayCustomerAccount($params)
    {
        $url = $this->context->link->getModuleLink($this->name, 'services');
        return '<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" href="' . $url . '"><span class="link-item"><i class="material-icons">dns</i>' . $this->l('Hizmetlerim') . '</span></a>';
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        $controller = Tools::getValue('controller');
        if (stripos((string)$controller, 'AdminNtRc') !== 0) {
            return;
        }

        $this->context->controller->addCSS(NtRcAdminThemeHelper::cssUrl());
    }
}
