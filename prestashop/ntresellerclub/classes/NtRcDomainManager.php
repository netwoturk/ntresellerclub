<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcDomainManager
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function maybeProvisionDomain(Order $order, array $product, array $customerResult)
    {
        $domainName = $this->extractDomainName($product);

        if (!$domainName) {
            return array('success' => true, 'skipped' => true, 'reason' => 'Domain bilgisi bulunamadı.');
        }

        Db::getInstance()->insert('ntresellerclub_service', array(
            'id_customer' => (int)$order->id_customer,
            'id_order' => (int)$order->id,
            'service_type' => pSQL('domain'),
            'domain_name' => pSQL($domainName),
            'provider_order_id' => 0,
            'start_date' => date('Y-m-d'),
            'expiry_date' => date('Y-m-d', strtotime('+1 year')),
            'status' => pSQL('pending'),
            'created_at' => date('Y-m-d H:i:s'),
        ));

        return array('success' => true, 'domain' => $domainName, 'status' => 'pending');
    }

    protected function extractDomainName(array $product)
    {
        if (!empty($product['product_reference']) && strpos($product['product_reference'], 'DOMAIN:') === 0) {
            return substr($product['product_reference'], 7);
        }
        return null;
    }
}
