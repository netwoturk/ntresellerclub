<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcAdminThemeHelper.php';

class NtRcAdminNavigationBuilder
{
    const ROOT_CLASS = 'AdminNtRcRoot';

    public static function rootLabel()
    {
        return 'NetwoTurk Hosting';
    }

    public static function tabs()
    {
        return array(
            array('key' => 'dashboard', 'class_name' => 'AdminNtRcDashboard', 'label' => 'Dashboard', 'icon' => 'dashboard'),
            array('key' => 'domains', 'class_name' => 'AdminNtRcDomains', 'label' => 'Domains', 'icon' => 'language'),
            array('key' => 'tr_domains', 'class_name' => 'AdminNtRcTrDomains', 'label' => 'TR Domains', 'icon' => 'flag'),
            array('key' => 'hosting', 'class_name' => 'AdminNtRcHosting', 'label' => 'Hosting', 'icon' => 'dns'),
            array('key' => 'ssl', 'class_name' => 'AdminNtRcSsl', 'label' => 'SSL', 'icon' => 'lock'),
            array('key' => 'queue', 'class_name' => 'AdminNtRcQueue', 'label' => 'Queue', 'icon' => 'playlist_add_check'),
            array('key' => 'billing', 'class_name' => 'AdminNtRcBilling', 'label' => 'Billing', 'icon' => 'receipt'),
            array('key' => 'monitoring', 'class_name' => 'AdminNtRcMonitoring', 'label' => 'Monitoring', 'icon' => 'monitor_heart'),
            array('key' => 'notifications', 'class_name' => 'AdminNtRcNotifications', 'label' => 'Notifications', 'icon' => 'notifications'),
            array('key' => 'pricing', 'class_name' => 'AdminNtRcPricing', 'label' => 'Pricing', 'icon' => 'sell'),
            array('key' => 'btk_csv', 'class_name' => 'AdminNtRcBtkCsv', 'label' => 'BTK CSV', 'icon' => 'file_download'),
            array('key' => 'logs', 'class_name' => 'AdminNtRcLogs', 'label' => 'Logs', 'icon' => 'article'),
            array('key' => 'settings', 'class_name' => 'AdminNtRcSettings', 'label' => 'Settings', 'icon' => 'settings'),
            array('key' => 'license', 'class_name' => 'AdminNtRcLicense', 'label' => 'License', 'icon' => 'verified_user'),
        );
    }

    public static function section($key)
    {
        foreach (self::tabs() as $tab) {
            if ($tab['key'] === $key || $tab['class_name'] === $key) {
                return $tab;
            }
        }

        $tabs = self::tabs();
        return $tabs[0];
    }

    public static function sidebar($currentKey, Context $context)
    {
        $html = '<nav class="ntrc-admin-sidebar" aria-label="NetwoTurk admin navigation"><ul>';
        foreach (self::tabs() as $tab) {
            $active = $tab['key'] === $currentKey ? ' class="active"' : '';
            $url = $context->link->getAdminLink($tab['class_name']);
            $html .= '<li' . $active . '><a href="' . NtRcAdminThemeHelper::esc($url) . '">';
            $html .= '<span class="material-icons">' . NtRcAdminThemeHelper::esc($tab['icon']) . '</span>';
            $html .= '<span>' . NtRcAdminThemeHelper::esc($tab['label']) . '</span>';
            $html .= '</a></li>';
        }
        return $html . '</ul></nav>';
    }

    public static function breadcrumb($currentKey)
    {
        $section = self::section($currentKey);
        return array(self::rootLabel(), $section['label']);
    }
}
