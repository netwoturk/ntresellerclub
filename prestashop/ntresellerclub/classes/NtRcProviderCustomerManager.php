<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcProviderCustomerManager
{
    public static function ensure($idCustomer, $providerCode)
    {
        $customer = new Customer((int)$idCustomer);
        if (!Validate::isLoadedObject($customer)) {
            return array('success' => false, 'message' => 'PrestaShop müşterisi bulunamadı.');
        }

        $row = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider_customer` WHERE id_customer=' . (int)$idCustomer . ' AND provider_code="' . pSQL($providerCode) . '"'
        );

        if ($row && !empty($row['provider_customer_id'])) {
            return array('success' => true, 'provider_code' => $providerCode, 'provider_customer_id' => $row['provider_customer_id'], 'source' => 'existing');
        }

        if (!$row) {
            Db::getInstance()->insert('ntresellerclub_provider_customer', array(
                'id_customer' => (int)$idCustomer,
                'provider_code' => pSQL($providerCode),
                'provider_customer_id' => null,
                'provider_username' => null,
                'email' => pSQL($customer->email),
                'status' => pSQL('pending'),
                'created_at' => date('Y-m-d H:i:s'),
            ));
        }

        return array('success' => true, 'provider_code' => $providerCode, 'provider_customer_id' => null, 'source' => 'pending');
    }
}
