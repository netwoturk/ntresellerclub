<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcProvisioningMode
{
    const CFG_MODE = 'NTRC_PROVISIONING_MODE';

    public static function get()
    {
        $mode = Configuration::get(self::CFG_MODE);
        return $mode ? $mode : 'safe';
    }

    public static function isSafe()
    {
        return self::get() !== 'live';
    }

    public static function isLive()
    {
        return self::get() === 'live';
    }

    public static function set($mode)
    {
        $mode = $mode === 'live' ? 'live' : 'safe';
        return Configuration::updateValue(self::CFG_MODE, $mode);
    }
}
