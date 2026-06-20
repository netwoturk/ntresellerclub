<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcCustomerManager.php';
require_once __DIR__ . '/NtRcDomainManager.php';

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

        $customerManager = new NtRcCustomerManager($this->module);
        $customerResult = $customerManager->ensureCustomer((int)$order->id_customer);

        if (!$customerResult['success']) {
            return $customerResult;
        }

        $domainManager = new NtRcDomainManager($this->module);
        $results = array();

        foreach ($order->getProducts() as $product) {
            $results[] = $domainManager->maybeProvisionDomain($order, $product, $customerResult);
        }

        return array('success' => true, 'message' => 'Provisioning işlemi tamamlandı.', 'results' => $results);
    }
}
