<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcCartDomain
{
    public static function rememberDomain($idCart, $domainName, $years = 1)
    {
        if (!$idCart || !$domainName) {
            return false;
        }

        return Db::getInstance()->insert('ntresellerclub_cart_domain', array(
            'id_cart' => (int)$idCart,
            'domain_name' => pSQL($domainName),
            'years' => (int)$years,
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    public static function getDomainsByCart($idCart)
    {
        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_cart_domain` WHERE id_cart=' . (int)$idCart
        );
    }
}
