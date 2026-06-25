<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';

class NtRcServiceRepository
{
    public static function getCustomerService($idService, $idCustomer)
    {
        self::ensureSchema();

        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE id_ntresellerclub_service=' . (int)$idService . ' AND id_customer=' . (int)$idCustomer
        );
    }

    public static function getService($idService)
    {
        self::ensureSchema();

        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE id_ntresellerclub_service=' . (int)$idService
        );
    }

    public static function updateStatus($idService, $status)
    {
        self::ensureSchema();

        return Db::getInstance()->update('ntresellerclub_service', array(
            'status' => pSQL($status),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_service=' . (int)$idService);
    }

    public static function updateSync($idService, array $fields)
    {
        self::ensureSchema();

        $fields['last_sync'] = date('Y-m-d H:i:s');
        $fields['updated_at'] = date('Y-m-d H:i:s');
        foreach ($fields as $key => $value) {
            if ($value !== null) {
                $fields[$key] = pSQL($value);
            }
        }
        return Db::getInstance()->update('ntresellerclub_service', $fields, 'id_ntresellerclub_service=' . (int)$idService);
    }

    public static function markProvisioned($idService, array $fields = array())
    {
        $data = array('status' => isset($fields['status']) ? $fields['status'] : 'active');

        foreach (array('provider_service_id', 'provider_order_id', 'provider_customer_id', 'provider_contact_id', 'expiry_date') as $key) {
            if (array_key_exists($key, $fields) && $fields[$key] !== null && $fields[$key] !== '') {
                $data[$key] = $fields[$key];
            }
        }

        return self::updateSync($idService, $data);
    }

    protected static function ensureSchema()
    {
        return NtRcInstaller::ensureServiceSchema();
    }
}
