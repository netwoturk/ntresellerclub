<?php
class NtdomainsearchAddtocartModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        header('Content-Type: application/json');

        $domain = Tools::strtolower(trim(Tools::getValue('domain')));
        $years = (int)Tools::getValue('years', 1);

        if (!$domain || !Validate::isGenericName(str_replace('.', '', $domain))) {
            die(json_encode(array('success' => false, 'message' => 'Geçerli domain bulunamadı.')));
        }

        $helperFile = _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcCartDomain.php';
        if (!file_exists($helperFile)) {
            die(json_encode(array('success' => false, 'message' => 'Domain sepet yardımcısı bulunamadı.')));
        }

        require_once $helperFile;

        if (!$this->context->cart || !$this->context->cart->id) {
            $this->context->cart = new Cart();
            $this->context->cart->id_lang = (int)$this->context->language->id;
            $this->context->cart->id_currency = (int)$this->context->currency->id;
            $this->context->cart->id_shop = (int)$this->context->shop->id;
            $this->context->cart->add();
            $this->context->cookie->id_cart = (int)$this->context->cart->id;
        }

        NtRcCartDomain::rememberDomain((int)$this->context->cart->id, $domain, $years ?: 1);

        die(json_encode(array(
            'success' => true,
            'message' => 'Domain sepete hazırlık kaydına eklendi.',
            'domain' => $domain,
            'cart_id' => (int)$this->context->cart->id
        )));
    }
}
