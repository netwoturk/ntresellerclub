<?php
class NtresellerclubDomaincartModuleFrontController extends ModuleFrontController
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
            $action = strtolower(trim((string)Tools::getValue('action', 'add')));
            if ($action !== 'add') {
                die(json_encode(array('success' => false, 'message' => 'Gecersiz domain cart action.')));
            }

            $cart = $this->ensureCart();
            if (!$cart || !(int)$cart->id) {
                die(json_encode(array('success' => false, 'message' => 'Sepet olusturulamadi.')));
            }

            require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcDomainCartBuilder.php';

            $builder = new NtRcDomainCartBuilder();
            $result = $builder->addDomainToCart(
                (int)$cart->id,
                Tools::getValue('domain'),
                (int)Tools::getValue('years', 1),
                array(
                    'id_product' => (int)Tools::getValue('id_product', 0),
                    'cart_token' => Tools::getValue('cart_token'),
                )
            );

            die(json_encode($this->safeResponse($result)));
        } catch (Exception $e) {
            die(json_encode(array('success' => false, 'message' => 'Islem sirasinda hata olustu.', 'error' => $this->safeText($e->getMessage()))));
        }
    }

    protected function ensureCart()
    {
        if ($this->context->cart && (int)$this->context->cart->id) {
            return $this->context->cart;
        }

        $cart = new Cart();
        $cart->id_lang = (int)$this->context->language->id;
        $cart->id_currency = (int)$this->context->currency->id;
        $cart->id_guest = (int)$this->context->cookie->id_guest;
        $cart->id_shop_group = (int)$this->context->shop->id_shop_group;
        $cart->id_shop = (int)$this->context->shop->id;
        if ($this->context->customer && (int)$this->context->customer->id) {
            $cart->id_customer = (int)$this->context->customer->id;
        }

        if (!$cart->add()) {
            return null;
        }

        $this->context->cart = $cart;
        $this->context->cookie->id_cart = (int)$cart->id;
        return $cart;
    }

    protected function safeResponse(array $response)
    {
        unset($response['raw'], $response['payload'], $response['response']);
        foreach ($response as $key => $value) {
            if (is_string($value)) {
                $response[$key] = $this->safeText($value);
            }
        }
        return $response;
    }

    protected function safeText($text)
    {
        $text = (string)$text;
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|secret)=([^&\s]+)/i', '$1=***', $text);
    }
}
