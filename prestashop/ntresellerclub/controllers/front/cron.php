<?php
class NtresellerclubCronModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json');

        $token = Tools::getValue('token');
        if ($token !== Configuration::get('NTRC_CRON_TOKEN')) {
            die(json_encode(array('success' => false, 'message' => 'Invalid token')));
        }

        require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcRenewalManager.php';

        $manager = new NtRcRenewalManager();
        $result = $manager->scan();

        die(json_encode(array('success' => true, 'result' => $result)));
    }
}
