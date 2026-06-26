<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcAdminThemeHelper
{
    public static function esc($value)
    {
        return class_exists('Tools') ? Tools::safeOutput((string)$value) : htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    public static function panelClass()
    {
        return 'panel ntrc-admin-panel';
    }

    public static function badgeClass($status)
    {
        $status = strtolower(trim((string)$status));
        if (in_array($status, array('active', 'done', 'success', 'ready'), true)) {
            return 'badge-success';
        }
        if (in_array($status, array('pending', 'processing', 'provisioning', 'renewal_due'), true)) {
            return 'badge-warning';
        }
        if (in_array($status, array('failed', 'error', 'expired', 'cancelled', 'provider_credit_required'), true)) {
            return 'badge-danger';
        }
        return 'badge-default';
    }

    public static function cssUrl()
    {
        return _MODULE_DIR_ . 'ntresellerclub/views/css/admin-framework.css';
    }
}
