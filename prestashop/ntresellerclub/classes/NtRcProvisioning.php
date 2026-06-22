<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcCustomerManager.php';
require_once __DIR__ . '/NtRcDomainManager.php';
require_once __DIR__ . '/NtRcCartDomain.php';
require_once __DIR__ . '/NtRcProviderCustomerManager.php';

class NtRcProvisioning
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function processOrder($idOrder)
    {
        $order = new Order((int)$idOrder);
        if (!Validate::isLoadedObject($order)) {
            return array('success' => false, 'message' => 'Sipariş bulunamadı.');
        }

        $results = array();
        $domainManager = new NtRcDomainManager($this->module);
        $cartDomains = NtRcCartDomain::getDomainsByCart((int)$order->id_cart);

        foreach ((array)$cartDomains as $cartDomain) {
            $providerCode = !empty($cartDomain['provider_code']) ? $cartDomain['provider_code'] : null;
            if (!$providerCode) {
                $providerCode = 'resellerclub';
            }

            $providerCustomer = NtRcProviderCustomerManager::ensure((int)$order->id_customer, $providerCode);
            if (!$providerCustomer['success']) {
                $results[] = $providerCustomer;
                continue;
            }

            $results[] = $domainManager->provisionCartDomain($order, $cartDomain, $providerCustomer);
        }

        foreach ($order->getProducts() as $product) {
            $results[] = $domainManager->maybeProvisionDomain($order, $product, array('success' => true));
        }

        return array('success' => true, 'message' => 'Provisioning işlemi tamamlandı.', 'results' => $results);
    }
}
