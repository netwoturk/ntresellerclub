<?php
class NtresellerclubDomainsearchModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        $this->renderJson();
    }

    public function initContent()
    {
        parent::initContent();
        $this->renderJson();
    }

    protected function renderJson()
    {
        header('Content-Type: application/json');

        try {
            require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcDomainSearchService.php';

            $query = Tools::getValue('domain');
            if ($query === null || $query === '') {
                $query = Tools::getValue('q');
            }

            $service = new NtRcDomainSearchService();
            die(json_encode($service->search($query)));
        } catch (Exception $e) {
            die(json_encode(array(
                'success' => false,
                'query' => Tools::getValue('domain', Tools::getValue('q')),
                'normalized_domain' => null,
                'results' => array(),
                'cached' => false,
                'checked_at' => date('Y-m-d H:i:s'),
                'error' => $this->safeText($e->getMessage()),
            )));
        }
    }

    protected function safeText($text)
    {
        $text = (string)$text;
        $text = preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|secret)=([^&\s]+)/i', '$1=***', $text);
        return preg_replace('/([A-Za-z0-9_\-.]{24,})/', '***', $text);
    }
}
