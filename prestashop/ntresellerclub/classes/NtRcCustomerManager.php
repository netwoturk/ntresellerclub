<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcProviderCustomerManager.php';

class NtRcCustomerManager
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function ensureCustomer($idCustomer, $providerCode = 'resellerclub')
    {
        $result = NtRcProviderCustomerManager::ensure((int)$idCustomer, $providerCode);
        if (empty($result['success'])) {
            return $result;
        }

        return array(
            'success' => true,
            'customer_id' => isset($result['provider_customer_id']) ? $result['provider_customer_id'] : null,
            'provider_code' => $providerCode,
            'queue_id' => isset($result['queue_id']) ? (int)$result['queue_id'] : 0,
            'source' => isset($result['source']) ? $result['source'] : 'pending',
        );
    }
}
