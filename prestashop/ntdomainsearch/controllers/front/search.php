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
        $formatterFile = _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcDomainSearchResultFormatter.php';
        if (!file_exists($engineFile) || !file_exists($formatterFile)) {
            die(json_encode(array('success' => false, 'message' => 'Domain search altyapisi yok.')));
        }

        require_once $engineFile;
        require_once $formatterFile;

        $engine = new NtRcDomainSearchEngine();
        $result = $engine->search($query, $tlds);
        $formatted = NtRcDomainSearchResultFormatter::toJson($result);

        die(json_encode($formatted));
    }
}
