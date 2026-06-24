<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcCustomerManager.php';
require_once __DIR__ . '/NtRcDomainManager.php';
require_once __DIR__ . '/NtRcCartDomain.php';
require_once __DIR__ . '/NtRcProviderCustomerManager.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcProvisioning
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function processOrder($idOrder)
    {
        NtRcRuntimeGuard::beforeHeavyProcess('order_provisioning');

        $order = new Order((int)$idOrder);
        if (!Validate::isLoadedObject($order)) {
            return array('success' => false, 'message' => 'Siparis bulunamadi.');
        }

        $results = array();
        $domainManager = new NtRcDomainManager($this->module);
        $cartDomains = NtRcCartDomain::getDomainsByCart((int)$order->id_cart);
        $limit = NtRcRuntimeGuard::cronBatchLimit(10);
        $processed = 0;

        foreach ((array)$cartDomains as $cartDomain) {
            if ($processed >= $limit) {
                $results[] = array('success' => false, 'message' => 'Batch limit nedeniyle kalan islemler sonraki cron turuna birakildi.');
                break;
            }

            try {
                $providerCode = !empty($cartDomain['provider_code']) ? $cartDomain['provider_code'] : 'resellerclub';
                $domainName = !empty($cartDomain['domain_name']) ? $cartDomain['domain_name'] : null;
                $providerCustomer = NtRcProviderCustomerManager::ensure((int)$order->id_customer, $providerCode, $domainName);
                if (!$providerCustomer['success']) {
                    $results[] = $providerCustomer;
                    continue;
                }

                $results[] = $domainManager->provisionCartDomain($order, $cartDomain, $providerCustomer);
                $processed++;
            } catch (Exception $e) {
                NtRcLog::add('error', 'order_provisioning', 'Cart domain error order=' . (int)$idOrder . ' ' . $e->getMessage());
                $results[] = array('success' => false, 'message' => 'Domain provision hatasi.', 'error' => $e->getMessage());
            }
        }

        foreach ($order->getProducts() as $product) {
            if ($processed >= $limit) {
                break;
            }
            try {
                $results[] = $domainManager->maybeProvisionDomain($order, $product, array('success' => true));
                $processed++;
            } catch (Exception $e) {
                NtRcLog::add('error', 'order_provisioning', 'Product provision error order=' . (int)$idOrder . ' ' . $e->getMessage());
                $results[] = array('success' => false, 'message' => 'Urun provision hatasi.', 'error' => $e->getMessage());
            }
        }

        return array('success' => true, 'message' => 'Provisioning islemi tamamlandi.', 'processed' => $processed, 'results' => $results);
    }
}
