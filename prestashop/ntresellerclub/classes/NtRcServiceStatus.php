<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcServiceStatus
{
    public static function label($status)
    {
        $map = array(
            'pending' => 'Beklemede',
            'ready' => 'Hazir',
            'register_waiting' => 'Kayit Bekliyor',
            'active' => 'Aktif',
            'suspended' => 'Askida',
            'expired' => 'Suresi Doldu',
            'error' => 'Hata',
            'cancelled' => 'Iptal',
        );

        return isset($map[$status]) ? $map[$status] : $status;
    }

    public static function isRenewable($status)
    {
        return in_array($status, array('active', 'expired', 'ready'));
    }

    public static function isManageable($status)
    {
        return in_array($status, array('active', 'ready', 'register_waiting'));
    }
}
