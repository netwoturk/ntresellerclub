<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcAdminNavigationBuilder.php';
require_once __DIR__ . '/NtRcAdminThemeHelper.php';

class NtRcAdminLayout
{
    public static function render(ModuleAdminController $controller, array $options)
    {
        $section = isset($options['section']) ? $options['section'] : 'dashboard';
        $sectionRow = NtRcAdminNavigationBuilder::section($section);
        $title = isset($options['title']) ? $options['title'] : $sectionRow['label'];
        $content = isset($options['content']) ? $options['content'] : '';
        $breadcrumbs = NtRcAdminNavigationBuilder::breadcrumb($section);

        $html = '<div class="ntrc-admin-shell">';
        $html .= self::header($title, $breadcrumbs);
        $html .= '<div class="ntrc-admin-body">';
        $html .= NtRcAdminNavigationBuilder::sidebar($section, $controller->context);
        $html .= '<main class="ntrc-admin-content">';
        $html .= $content;
        $html .= '</main></div>';
        $html .= self::footer();
        return $html . '</div>';
    }

    protected static function header($title, array $breadcrumbs)
    {
        $html = '<header class="ntrc-admin-header">';
        $html .= '<div><h2>' . NtRcAdminThemeHelper::esc($title) . '</h2>';
        $html .= '<ol class="breadcrumb">';
        foreach ($breadcrumbs as $breadcrumb) {
            $html .= '<li>' . NtRcAdminThemeHelper::esc($breadcrumb) . '</li>';
        }
        $html .= '</ol></div>';
        return $html . '</header>';
    }

    protected static function footer()
    {
        return '<footer class="ntrc-admin-footer">NetwoTurk Hosting Admin Framework</footer>';
    }
}
