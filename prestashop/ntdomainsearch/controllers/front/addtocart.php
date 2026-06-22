<?php
class NtdomainsearchAddtocartModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        header('Content-Type: application/json');

        $domain = Tools::strtolower(trim(Tools::getValue('domain')));
        $years = (int)Tools::getValue('years', 1);

        if (!$domain || !Validate::isGenericName(str_replace(array('.', '-'), '', $domain))) {
            die(json_encode(array('success' => false, 'message' => 'Geçerli domain bulunamadı.')));
        }

        $helperFile = _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcCartDomain.php';
        $routeFile = _PS_MODULE_DIR_ . 'ntresellerclub/classes/providers/NtRcTldRouteManager.php';
        $registryFile = _PS_MODULE_DIR_ . 'ntresellerclub/classes/providers/NtRcProviderRegistry.php';

        if (!file_exists($helperFile) || !file_exists($routeFile) || !file_exists($registryFile)) {
            die(json_encode(array('success' => false, 'message' => 'Provider altyapısı bulunamadı.')));
        }

        require_once $helperFile;
        require_once $routeFile;
        require_once $registryFile;

        $providerCode = NtRcTldRouteManager::resolveDomain($domain);
        if (!$providerCode || !NtRcProviderRegistry::isUsable($providerCode)) {
            die(json_encode(array('success' => false, 'message' => 'Bu domain için aktif/lisanslı provider bulunamadı.')));
        }

        if (!$this->context->cart || !$this->context->cart->id) {
            $this->context->cart = new Cart();
            $this->context->cart->id_lang = (int)$this->context->language->id;
            $this->context->cart->id_currency = (int)$this->context->currency->id;
            $this->context->cart->id_shop = (int)$this->context->shop->id;
            $this->context->cart->add();
            $this->context->cookie->id_cart = (int)$this->context->cart->id;
        }

        NtRcCartDomain::rememberDomain((int)$this->context->cart->id, $domain, $years ?: 1, $providerCode);

        die(json_encode(array(
            'success' => true,
            'message' => 'Domain sepete hazırlık kaydına eklendi.',
            'domain' => $domain,
            'provider' => $providerCode,
            'cart_id' => (int)$this->context->cart->id
        )));
    }
}
