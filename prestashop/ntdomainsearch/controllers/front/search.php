<?php
class NtdomainsearchSearchModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        header('Content-Type: application/json');

        $sld = Tools::strtolower(trim(Tools::getValue('domain')));
        $tlds = (array)Tools::getValue('tlds', array('com'));

        if (!$sld || !Validate::isGenericName(str_replace(array('-', '_'), '', $sld))) {
            die(json_encode(array('success' => false, 'message' => 'Geçerli bir alan adı yazınız.')));
        }

        $factoryFile = _PS_MODULE_DIR_ . 'ntresellerclub/classes/providers/NtRcProviderFactory.php';
        if (!file_exists($factoryFile)) {
            die(json_encode(array('success' => false, 'message' => 'Provider factory bulunamadı.')));
        }

        require_once $factoryFile;

        $final = array();
        $errors = array();

        foreach ($tlds as $tld) {
            $tld = Tools::strtolower(ltrim(trim($tld), '.'));
            if (!$tld) {
                continue;
            }

            $provider = NtRcProviderFactory::makeByTld($tld);
            if (!$provider) {
                $errors[$sld . '.' . $tld] = 'Bu uzantı için aktif/lisanslı provider bulunamadı.';
                continue;
            }

            $response = $provider->checkAvailability($sld, array($tld), 1);
            if (!$response['success']) {
                $errors[$sld . '.' . $tld] = isset($response['error']) ? $response['error'] : 'Provider sorgusu başarısız.';
                continue;
            }

            $final = array_merge($final, (array)$response['data']);
        }

        die(json_encode(array(
            'success' => count($final) > 0,
            'data' => $final,
            'errors' => $errors,
            'message' => count($final) > 0 ? null : 'Uygun provider sonucu alınamadı.'
        )));
    }
}
