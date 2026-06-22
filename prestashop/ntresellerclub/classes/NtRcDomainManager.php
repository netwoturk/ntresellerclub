<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/providers/NtRcTldRouteManager.php';

class NtRcDomainManager
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function provisionCartDomain(Order $order, array $cartDomain, array $providerCustomer)
    {
        $domainName = isset($cartDomain['domain_name']) ? $cartDomain['domain_name'] : null;
        $providerCode = isset($cartDomain['provider_code']) ? $cartDomain['provider_code'] : null;
        $years = isset($cartDomain['years']) ? (int)$cartDomain['years'] : 1;

        if (!$domainName) {
            return array('success' => false, 'message' => 'Sepet domain bilgisi bulunamadı.');
        }

        if (!$providerCode) {
            $providerCode = NtRcTldRouteManager::resolveDomain($domainName);
        }

        Db::getInstance()->insert('ntresellerclub_service', array(
            'id_customer' => (int)$order->id_customer,
            'id_order' => (int)$order->id,
            'id_product' => 0,
            'provider_code' => pSQL($providerCode),
            'service_type' => pSQL('domain'),
            'domain_name' => pSQL($domainName),
            'provider_order_id' => null,
            'provider_customer_id' => isset($providerCustomer['provider_customer_id']) ? pSQL($providerCustomer['provider_customer_id']) : null,
            'provider_contact_id' => null,
            'start_date' => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+' . max(1, $years) . ' year')),
            'status' => pSQL('pending'),
            'created_at' => date('Y-m-d H:i:s'),
        ));

        return array(
            'success' => true,
            'domain' => $domainName,
            'provider' => $providerCode,
            'years' => $years,
            'status' => 'pending'
        );
    }

    public function maybeProvisionDomain(Order $order, array $product, array $customerResult)
    {
        $domainName = $this->extractDomainName($product);

        if (!$domainName) {
            return array('success' => true, 'skipped' => true, 'reason' => 'Domain bilgisi bulunamadı.');
        }

        $providerCode = NtRcTldRouteManager::resolveDomain($domainName);

        Db::getInstance()->insert('ntresellerclub_service', array(
            'id_customer' => (int)$order->id_customer,
            'id_order' => (int)$order->id,
            'id_product' => isset($product['id_product']) ? (int)$product['id_product'] : 0,
            'provider_code' => pSQL($providerCode),
            'service_type' => pSQL('domain'),
            'domain_name' => pSQL($domainName),
            'provider_order_id' => null,
            'start_date' => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+1 year')),
            'status' => pSQL('pending'),
            'created_at' => date('Y-m-d H:i:s'),
        ));

        return array('success' => true, 'domain' => $domainName, 'provider' => $providerCode, 'status' => 'pending');
    }

    protected function extractDomainName(array $product)
    {
        if (!empty($product['product_reference']) && strpos($product['product_reference'], 'DOMAIN:') === 0) {
            return substr($product['product_reference'], 7);
        }
        return null;
    }
}
