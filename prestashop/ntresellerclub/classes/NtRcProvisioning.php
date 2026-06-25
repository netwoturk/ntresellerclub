<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcCustomerManager.php';
require_once __DIR__ . '/NtRcDomainManager.php';
require_once __DIR__ . '/NtRcCartDomain.php';
require_once __DIR__ . '/NtRcProviderCustomerManager.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcHostingManager.php';
require_once __DIR__ . '/NtRcLog.php';
require_once __DIR__ . '/providers/NtRcTldRouteManager.php';

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
        $processedDomains = array();
        $domainManager = new NtRcDomainManager($this->module);
        $hostingManager = new NtRcHostingManager();
        $cartDomains = NtRcCartDomain::getDomainsByCart((int)$order->id_cart);
        $limit = NtRcRuntimeGuard::cronBatchLimit(10);
        $processed = 0;

        foreach ((array)$cartDomains as $cartDomain) {
            if ($processed >= $limit) {
                $results[] = array('success' => false, 'message' => 'Batch limit nedeniyle kalan islemler sonraki cron turuna birakildi.');
                break;
            }

            try {
                $domainName = !empty($cartDomain['domain_name']) ? $cartDomain['domain_name'] : null;
                if (!$domainName) {
                    $results[] = array('success' => false, 'message' => 'Sepet domain bilgisi bulunamadi.');
                    continue;
                }

                $providerCode = !empty($cartDomain['provider_code']) ? $cartDomain['provider_code'] : NtRcTldRouteManager::resolveDomain($domainName);
                $providerCustomer = NtRcProviderCustomerManager::ensure((int)$order->id_customer, $providerCode, $domainName);
                if (!$providerCustomer['success']) {
                    $results[] = $providerCustomer;
                    continue;
                }

                $result = $domainManager->provisionCartDomain($order, $cartDomain, $providerCustomer);
                $results[] = $result;
                if (!empty($result['success'])) {
                    $processedDomains[strtolower($domainName)] = true;
                    $processed++;
                }
            } catch (Exception $e) {
                NtRcLog::add('error', 'order_provisioning', 'Cart domain error order=' . (int)$idOrder . ' ' . $this->safeText($e->getMessage()));
                $results[] = array('success' => false, 'message' => 'Domain provision hatasi.', 'error' => $this->safeText($e->getMessage()));
            }
        }

        foreach ($order->getProducts() as $product) {
            if ($processed >= $limit) {
                break;
            }

            $domainName = $this->extractDomainName($product);
            if ($domainName && isset($processedDomains[strtolower($domainName)])) {
                continue;
            }

            try {
                $hostingResult = $hostingManager->maybeProvisionHosting($order, $product);
                if (empty($hostingResult['skipped'])) {
                    $results[] = $hostingResult;
                    $processed++;
                    continue;
                }

                $result = $domainManager->maybeProvisionDomain($order, $product, array('success' => true));
                $results[] = $result;
                if (empty($result['skipped'])) {
                    $processed++;
                }
            } catch (Exception $e) {
                NtRcLog::add('error', 'order_provisioning', 'Product provision error order=' . (int)$idOrder . ' ' . $this->safeText($e->getMessage()));
                $results[] = array('success' => false, 'message' => 'Urun provision hatasi.', 'error' => $this->safeText($e->getMessage()));
            }
        }

        return array('success' => true, 'message' => 'Provisioning islemi tamamlandi.', 'processed' => $processed, 'results' => $results);
    }

    protected function extractDomainName(array $product)
    {
        if (!empty($product['product_reference']) && strpos($product['product_reference'], 'DOMAIN:') === 0) {
            return substr($product['product_reference'], 7);
        }
        return null;
    }

    protected function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
