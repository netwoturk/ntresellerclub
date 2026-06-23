<?php
class NtresellerclubAdddomainModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function postProcess()
    {
        header('Content-Type: application/json');

        if (!$this->context->cart || !(int)$this->context->cart->id) {
            $this->context->cart = new Cart();
            $this->context->cart->id_lang = (int)$this->context->language->id;
            $this->context->cart->id_currency = (int)$this->context->currency->id;
            $this->context->cart->id_guest = (int)$this->context->cookie->id_guest;
            $this->context->cart->id_shop_group = (int)$this->context->shop->id_shop_group;
            $this->context->cart->id_shop = (int)$this->context->shop->id;
            if ($this->context->customer && (int)$this->context->customer->id) {
                $this->context->cart->id_customer = (int)$this->context->customer->id;
            }
            $this->context->cart->add();
            $this->context->cookie->id_cart = (int)$this->context->cart->id;
        }

        require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcDomainCartBuilder.php';

        $builder = new NtRcDomainCartBuilder();
        $result = $builder->addDomainToCart(
            (int)$this->context->cart->id,
            Tools::getValue('domain'),
            (int)Tools::getValue('years', 1)
        );

        die(json_encode($result));
    }
}
