<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/NtRcApiClient.php';
require_once __DIR__ . '/classes/NtRcLicense.php';
require_once __DIR__ . '/classes/NtRcProvisioning.php';

class Ntresellerclub extends Module
{
    const CFG_LIVE_MODE = 'NTRC_LIVE_MODE';
    const CFG_RESELLER_ID = 'NTRC_RESELLER_ID';
    const CFG_API_KEY = 'NTRC_API_KEY';
    const CFG_LANG_PREF = 'NTRC_LANG_PREF';
    const CFG_LICENSE_KEY = 'NTRC_LICENSE_KEY';
    const CFG_CRON_TOKEN = 'NTRC_CRON_TOKEN';

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
        $this->displayName = $this->l('NetwoTurk ResellerClub Panel');
        $this->description = $this->l('Provider automation module.');
    }

    public function install()
    {
        return parent::install()
            && Configuration::updateValue(self::CFG_LIVE_MODE, 1)
            && Configuration::updateValue(self::CFG_RESELLER_ID, '')
            && Configuration::updateValue(self::CFG_API_KEY, '')
            && Configuration::updateValue(self::CFG_LANG_PREF, 'en')
            && Configuration::updateValue(self::CFG_LICENSE_KEY, '')
            && Configuration::updateValue(self::CFG_CRON_TOKEN, Tools::passwdGen(32))
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('displayCustomerAccount');
    }

    public function uninstall()
    {
        Configuration::deleteByName(self::CFG_LIVE_MODE);
        Configuration::deleteByName(self::CFG_RESELLER_ID);
        Configuration::deleteByName(self::CFG_API_KEY);
        Configuration::deleteByName(self::CFG_LANG_PREF);
        Configuration::deleteByName(self::CFG_LICENSE_KEY);
        Configuration::deleteByName(self::CFG_CRON_TOKEN);
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submitNtRcSettings')) {
            Configuration::updateValue(self::CFG_LIVE_MODE, (int)Tools::getValue(self::CFG_LIVE_MODE));
            Configuration::updateValue(self::CFG_RESELLER_ID, trim(Tools::getValue(self::CFG_RESELLER_ID)));
            Configuration::updateValue(self::CFG_API_KEY, trim(Tools::getValue(self::CFG_API_KEY)));
            Configuration::updateValue(self::CFG_LANG_PREF, trim(Tools::getValue(self::CFG_LANG_PREF)) ?: 'en');
            Configuration::updateValue(self::CFG_LICENSE_KEY, trim(Tools::getValue(self::CFG_LICENSE_KEY)));
            $output .= $this->displayConfirmation($this->l('Ayarlar kaydedildi.'));
        }
        if (Tools::isSubmit('testNtRcApi')) {
            $output .= $this->renderApiTest();
        }
        return $output . $this->renderForm();
    }

    protected function renderApiTest()
    {
        $client = new NtRcApiClient(
            (bool)Configuration::get(self::CFG_LIVE_MODE),
            Configuration::get(self::CFG_RESELLER_ID),
            Configuration::get(self::CFG_API_KEY),
            Configuration::get(self::CFG_LANG_PREF) ?: 'en'
        );
        $response = $client->domainAvailability('netwoturk', array('com'));
        if ($response['success']) {
            return $this->displayConfirmation($this->l('API testi başarılı.')) . '<pre>' . Tools::safeOutput(print_r($response['data'], true)) . '</pre>';
        }
        return $this->displayError($this->l('API testi başarısız: ') . Tools::safeOutput($response['error']) . ' HTTP: ' . (int)$response['http_code']) . '<pre>' . Tools::safeOutput($response['raw']) . '</pre>';
    }

    protected function renderForm()
    {
        $cronUrl = $this->context->link->getModuleLink($this->name, 'cron', array('token' => Configuration::get(self::CFG_CRON_TOKEN)));
        $fields = array('form' => array(
            'legend' => array('title' => $this->l('ResellerClub API Ayarları')),
            'input' => array(
                array('type' => 'switch', 'label' => $this->l('Live Modu'), 'name' => self::CFG_LIVE_MODE, 'is_bool' => true, 'values' => array(
                    array('id' => 'live_on', 'value' => 1, 'label' => $this->l('Canlı')),
                    array('id' => 'live_off', 'value' => 0, 'label' => $this->l('Test')),
                )),
                array('type' => 'text', 'label' => $this->l('Reseller ID'), 'name' => self::CFG_RESELLER_ID, 'required' => true),
                array('type' => 'password', 'label' => $this->l('API Key'), 'name' => self::CFG_API_KEY, 'required' => true),
                array('type' => 'text', 'label' => $this->l('Dil'), 'name' => self::CFG_LANG_PREF),
                array('type' => 'text', 'label' => $this->l('Yıllık Lisans Anahtarı'), 'name' => self::CFG_LICENSE_KEY),
                array('type' => 'text', 'label' => $this->l('Cron URL'), 'name' => 'NTRC_CRON_URL', 'readonly' => true),
            ),
            'submit' => array('title' => $this->l('Kaydet'), 'name' => 'submitNtRcSettings'),
            'buttons' => array(array('title' => $this->l('API Test Et'), 'name' => 'testNtRcApi', 'type' => 'submit', 'class' => 'btn btn-default pull-right')),
        ));
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submitNtRcSettings';
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->fields_value = array(
            self::CFG_LIVE_MODE => Configuration::get(self::CFG_LIVE_MODE),
            self::CFG_RESELLER_ID => Configuration::get(self::CFG_RESELLER_ID),
            self::CFG_API_KEY => Configuration::get(self::CFG_API_KEY),
            self::CFG_LANG_PREF => Configuration::get(self::CFG_LANG_PREF),
            self::CFG_LICENSE_KEY => Configuration::get(self::CFG_LICENSE_KEY),
            'NTRC_CRON_URL' => $cronUrl,
        );
        return $helper->generateForm(array($fields));
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
}
