<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcContactManager
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function ensureContact($idCustomer)
    {
        $existing = Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_contact` WHERE id_customer=' . (int)$idCustomer . ' ORDER BY id_ntresellerclub_contact DESC'
        );

        if ($existing && !empty($existing['provider_contact_id'])) {
            return array('success' => true, 'contact_id' => (int)$existing['provider_contact_id'], 'source' => 'existing');
        }

        $customer = new Customer((int)$idCustomer);
        if (!Validate::isLoadedObject($customer)) {
            return array('success' => false, 'message' => 'Müşteri bulunamadı.');
        }

        Db::getInstance()->insert('ntresellerclub_contact', array(
            'id_customer' => (int)$idCustomer,
            'provider_contact_id' => 0,
            'contact_type' => pSQL('domain'),
            'firstname' => pSQL($customer->firstname),
            'lastname' => pSQL($customer->lastname),
            'email' => pSQL($customer->email),
            'created_at' => date('Y-m-d H:i:s'),
        ));

        return array('success' => true, 'contact_id' => 0, 'source' => 'pending');
    }
}
