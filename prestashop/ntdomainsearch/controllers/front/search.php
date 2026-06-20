<?php
class NtdomainsearchSearchModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        header('Content-Type: application/json');

        $domain = Tools::strtolower(trim(Tools::getValue('domain')));
        $tlds = Tools::getValue('tlds', array('com'));

        if (!$domain || !Validate::isGenericName($domain)) {
            die(json_encode(array('success' => false, 'message' => 'Geçerli bir alan adı yazınız.')));
        }

        $clientFile = _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcApiClient.php';
        if (!file_exists($clientFile)) {
            die(json_encode(array('success' => false, 'message' => 'Ana ntresellerclub modülü bulunamadı.')));
        }

        require_once $clientFile;

        $client = new NtRcApiClient(
            (bool)Configuration::get('NTRC_LIVE_MODE'),
            Configuration::get('NTRC_RESELLER_ID'),
            Configuration::get('NTRC_API_KEY'),
            Configuration::get('NTRC_LANG_PREF') ?: 'en'
        );

        die(json_encode($client->domainAvailability($domain, (array)$tlds)));
    }
}
