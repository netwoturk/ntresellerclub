<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/providers/NtRcTldRouteManager.php';
require_once __DIR__ . '/NtRcTrPriceManager.php';
require_once __DIR__ . '/NtRcTrPriceCalculator.php';
require_once __DIR__ . '/NtRcDomainSearchService.php';
require_once __DIR__ . '/NtRcInstaller.php';

class NtRcDomainCartBuilder
{
    public function addDomainToCart($idCart, $domainName, $years = 1, array $options = array())
    {
        NtRcInstaller::ensureCartDomainSchema();

        $idCart = (int)$idCart;
        $years = max(1, (int)$years);
        $domainName = $this->normalizeDomain($domainName);
        if (!$domainName || !$this->isValidDomain($domainName)) {
            return array('success' => false, 'message' => 'Gecersiz alan adi.');
        }

        if ($idCart <= 0) {
            return array('success' => false, 'message' => 'Sepet bulunamadi.');
        }

        $search = new NtRcDomainSearchService();
        $availability = $search->search($domainName, $years);
        $result = isset($availability['results'][0]) ? $availability['results'][0] : null;

        if (!$result || empty($result['available'])) {
            return array(
                'success' => false,
                'message' => 'Domain musait degil veya availability dogrulanamadi.',
                'domain' => $domainName,
                'provider_code' => $result && isset($result['provider_code']) ? $result['provider_code'] : null,
            );
        }

        $tld = $result['tld'];
        $providerCode = $result['provider_code'];
        $serviceType = $result['service_type'];
        $finalSalePrice = isset($result['final_sale_price']) ? $result['final_sale_price'] : null;
        $currency = isset($result['currency']) ? $result['currency'] : null;

        if (!$this->isValidCartToken($search, $domainName, $providerCode, $years, $finalSalePrice, $options)) {
            return array('success' => false, 'message' => 'Domain cart token gecersiz.', 'domain' => $domainName, 'provider_code' => $providerCode);
        }

        $idProduct = $this->resolveProductId($providerCode, $serviceType, $options);

        if ($idProduct <= 0 || !$this->productIsUsable($idProduct)) {
            return array('success' => false, 'message' => 'Domain icin PrestaShop urun mapping bulunamadi.', 'domain' => $domainName, 'provider_code' => $providerCode);
        }

        $exists = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_cart_domain` '
            . 'WHERE id_cart=' . (int)$idCart . ' AND domain_name="' . pSQL($domainName) . '"'
        );

        if ($exists) {
            return array(
                'success' => false,
                'message' => 'Bu domain zaten sepette.',
                'cart_id' => (int)$idCart,
                'domain' => $domainName,
                'provider_code' => $providerCode,
                'final_sale_price' => $finalSalePrice,
            );
        }

        $cart = new Cart((int)$idCart);
        if (!Validate::isLoadedObject($cart)) {
            return array('success' => false, 'message' => 'Sepet yuklenemedi.');
        }

        if (!$cart->updateQty(1, (int)$idProduct)) {
            return array('success' => false, 'message' => 'Domain urunu sepete eklenemedi.', 'domain' => $domainName, 'provider_code' => $providerCode);
        }

        $data = array(
            'id_cart' => (int)$idCart,
            'id_product' => (int)$idProduct,
            'domain_name' => pSQL($domainName),
            'tld' => pSQL($tld),
            'provider_code' => pSQL($providerCode),
            'service_type' => pSQL($serviceType),
            'years' => (int)$years,
            'price_snapshot' => $finalSalePrice !== null ? (float)$finalSalePrice : null,
            'currency' => $currency ? pSQL($currency) : null,
            'options_json' => pSQL(json_encode($this->cartOptions($result, $options, $years))),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        if (!Db::getInstance()->insert('ntresellerclub_cart_domain', $data)) {
            $cart->updateQty(1, (int)$idProduct, null, false, 'down');
            return array('success' => false, 'message' => 'Sepet domain metadata kaydi yazilamadi.');
        }

        return array(
            'success' => true,
            'message' => 'Domain sepete eklendi.',
            'cart_id' => (int)$idCart,
            'id_product' => (int)$idProduct,
            'domain' => $domainName,
            'provider_code' => $providerCode,
            'service_type' => $serviceType,
            'years' => (int)$years,
            'final_sale_price' => $finalSalePrice,
            'currency' => $currency,
        );
    }

    public function getCartDomains($idCart)
    {
        return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_cart_domain` WHERE id_cart=' . (int)$idCart . ' ORDER BY id_ntresellerclub_cart_domain ASC');
    }

    public function removeDomainFromCart($idCart, $domainName)
    {
        return Db::getInstance()->delete('ntresellerclub_cart_domain', 'id_cart=' . (int)$idCart . ' AND domain_name="' . pSQL($this->normalizeDomain($domainName)) . '"');
    }

    protected function resolveProductId($providerCode, $serviceType, array $options)
    {
        if (!empty($options['id_product'])) {
            return (int)$options['id_product'];
        }

        if ($serviceType === 'tr_domain' || $providerCode === 'domainnameapi') {
            return (int)Configuration::get('NTRC_TR_DOMAIN_PRODUCT_ID');
        }

        return (int)Configuration::get('NTRC_DOMAIN_PRODUCT_ID');
    }

    protected function isValidCartToken(NtRcDomainSearchService $search, $domainName, $providerCode, $years, $finalSalePrice, array $options)
    {
        if (empty($options['cart_token'])) {
            return true;
        }

        $expected = $search->cartToken($domainName, $providerCode, $years, $finalSalePrice);
        if (function_exists('hash_equals')) {
            return hash_equals($expected, (string)$options['cart_token']);
        }

        return $expected === (string)$options['cart_token'];
    }

    protected function productIsUsable($idProduct)
    {
        $context = Context::getContext();
        $idLang = isset($context->language) ? (int)$context->language->id : null;
        $product = new Product((int)$idProduct, false, $idLang);
        return Validate::isLoadedObject($product) && (int)$product->active === 1;
    }

    protected function cartOptions(array $result, array $options, $years)
    {
        return array(
            'source' => 'domain_search',
            'domain' => $result['domain'],
            'tld' => $result['tld'],
            'provider_code' => $result['provider_code'],
            'service_type' => $result['service_type'],
            'years' => max(1, (int)$years),
            'final_sale_price' => $result['final_sale_price'],
            'currency' => $result['currency'],
            'cart_token' => isset($result['add_to_cart']['cart_token']) ? $result['add_to_cart']['cart_token'] : null,
        );
    }

    protected function normalizeDomain($domainName)
    {
        $domainName = strtolower(trim((string)$domainName));
        $domainName = str_replace(array('http://', 'https://', 'www.'), '', $domainName);
        return trim($domainName, '/ .');
    }

    protected function isValidDomain($domainName)
    {
        if (strpos($domainName, '.') === false) {
            return false;
        }
        if (strpos($domainName, ' ') !== false) {
            return false;
        }
        return true;
    }

    protected function extractTld($domainName)
    {
        $parts = explode('.', $domainName);
        if (count($parts) >= 3) {
            $lastTwo = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
            if (NtRcTrPriceManager::isAllowedTld($lastTwo)) {
                return $lastTwo;
            }
        }
        return end($parts);
    }
}
