<?php
class NtdomainsearchSearchModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        header('Content-Type: application/json');

        $query = Tools::strtolower(trim(Tools::getValue('domain')));
        $tlds = (array)Tools::getValue('tlds', array());

        $engineFile = _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcDomainSearchEngine.php';
        if (!file_exists($engineFile)) {
            die(json_encode(array('success' => false, 'message' => 'Domain search engine bulunamadi.')));
        }

        require_once $engineFile;

        $engine = new NtRcDomainSearchEngine();
        $result = $engine->search($query, $tlds);

        die(json_encode(array(
            'success' => $result['success'],
            'query' => $result['query'],
            'sld' => $result['sld'],
            'data' => $result['items'],
            'errors' => $result['errors'],
            'message' => $result['success'] ? null : 'Uygun provider sonucu alinamadi.'
        )));
    }
}
