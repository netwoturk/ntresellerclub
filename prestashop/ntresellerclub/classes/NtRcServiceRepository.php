<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcServiceRepository
{
    public static function getCustomerService($idService, $idCustomer)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE id_ntresellerclub_service=' . (int)$idService . ' AND id_customer=' . (int)$idCustomer
        );
    }

    public static function getService($idService)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE id_ntresellerclub_service=' . (int)$idService
        );
    }

    public static function updateStatus($idService, $status)
    {
        return Db::getInstance()->update('ntresellerclub_service', array(
            'status' => pSQL($status),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_service=' . (int)$idService);
    }

    public static function updateSync($idService, array $fields)
    {
        $fields['last_sync'] = date('Y-m-d H:i:s');
        $fields['updated_at'] = date('Y-m-d H:i:s');
        foreach ($fields as $key => $value) {
            if ($value !== null) {
                $fields[$key] = pSQL($value);
            }
        }
        return Db::getInstance()->update('ntresellerclub_service', $fields, 'id_ntresellerclub_service=' . (int)$idService);
    }
}
