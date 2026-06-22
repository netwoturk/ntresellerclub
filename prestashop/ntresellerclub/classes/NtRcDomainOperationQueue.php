<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcDomainOperationQueue
{
    public static function add($idService, $providerCode, $domainName, $action, array $payload = array())
    {
        return Db::getInstance()->insert('ntresellerclub_operation', array(
            'id_service' => (int)$idService,
            'provider_code' => pSQL($providerCode),
            'domain_name' => pSQL($domainName),
            'action' => pSQL($action),
            'payload_json' => pSQL(json_encode($payload)),
            'status' => pSQL('pending'),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    public static function pending($limit = 10)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation` WHERE status="pending" ORDER BY id_ntresellerclub_operation ASC LIMIT ' . (int)$limit
        );
    }

    public static function markDone($idOperation, $response = null)
    {
        return Db::getInstance()->update('ntresellerclub_operation', array(
            'status' => pSQL('done'),
            'response_json' => $response ? pSQL(json_encode($response)) : null,
            'processed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_operation=' . (int)$idOperation);
    }

    public static function markError($idOperation, $response = null)
    {
        return Db::getInstance()->update('ntresellerclub_operation', array(
            'status' => pSQL('error'),
            'response_json' => $response ? pSQL(json_encode($response)) : null,
            'processed_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_operation=' . (int)$idOperation);
    }
}
