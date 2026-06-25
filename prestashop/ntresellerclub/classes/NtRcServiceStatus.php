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
            'provisioning' => 'Kurulumda',
            'ready' => 'Hazir',
            'register_waiting' => 'Kayit Bekliyor',
            'active' => 'Aktif',
            'renewal_due' => 'Yenileme Bekliyor',
            'payment_required' => 'Odeme Gerekli',
            'provider_credit_required' => 'Provider Kredi Gerekli',
            'suspended' => 'Askida',
            'expired' => 'Suresi Doldu',
            'error' => 'Hata',
            'cancelled' => 'Iptal',
        );

        return isset($map[$status]) ? $map[$status] : $status;
    }

    public static function isRenewable($status)
    {
        return in_array($status, array('active', 'expired', 'ready', 'renewal_due', 'payment_required'));
    }

    public static function isManageable($status)
    {
        return in_array($status, array('active', 'ready', 'register_waiting', 'renewal_due', 'payment_required', 'provider_credit_required', 'suspended'));
    }
}
