<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcCustomerManager
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function ensureCustomer($idCustomer)
    {
        $customer = new Customer((int)$idCustomer);
        if (!Validate::isLoadedObject($customer)) {
            return array('success' => false, 'message' => 'PrestaShop müşterisi bulunamadı.');
        }

        $existing = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_customer` WHERE id_customer=' . (int)$idCustomer
        );

        if ($existing && !empty($existing['resellerclub_customer_id'])) {
            return array('success' => true, 'customer_id' => (int)$existing['resellerclub_customer_id'], 'source' => 'existing');
        }

        return array(
            'success' => true,
            'customer_id' => 0,
            'source' => 'pending',
            'message' => 'ResellerClub customer create V2 aşamasında aktif edilecek.'
        );
    }
}
