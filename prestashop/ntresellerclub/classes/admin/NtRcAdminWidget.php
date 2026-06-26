<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcAdminThemeHelper.php';

class NtRcAdminWidget
{
    public static function kpiCard($title, $value, $status = 'default', $description = '')
    {
        $html = '<div class="ntrc-widget ntrc-kpi-card">';
        $html .= '<div class="ntrc-widget-title">' . NtRcAdminThemeHelper::esc($title) . '</div>';
        $html .= '<div class="ntrc-widget-value">' . NtRcAdminThemeHelper::esc($value) . '</div>';
        if ($description !== '') {
            $html .= '<div class="ntrc-widget-desc">' . NtRcAdminThemeHelper::esc($description) . '</div>';
        }
        $html .= self::statusBadge($status, $status);
        return $html . '</div>';
    }

    public static function statisticTile($title, $value, $description = '')
    {
        $html = '<div class="ntrc-widget ntrc-stat-tile">';
        $html .= '<strong>' . NtRcAdminThemeHelper::esc($title) . '</strong>';
        $html .= '<span>' . NtRcAdminThemeHelper::esc($value) . '</span>';
        if ($description !== '') {
            $html .= '<small>' . NtRcAdminThemeHelper::esc($description) . '</small>';
        }
        return $html . '</div>';
    }

    public static function alert($type, $message)
    {
        $type = in_array($type, array('success', 'info', 'warning', 'danger'), true) ? $type : 'info';
        return '<div class="alert alert-' . $type . '">' . NtRcAdminThemeHelper::esc($message) . '</div>';
    }

    public static function statusBadge($status, $label = null)
    {
        $label = $label === null ? $status : $label;
        return '<span class="badge ' . NtRcAdminThemeHelper::esc(NtRcAdminThemeHelper::badgeClass($status)) . '">' . NtRcAdminThemeHelper::esc($label) . '</span>';
    }

    public static function table(array $headers, array $rows, $emptyMessage = 'No records.')
    {
        $html = '<div class="table-responsive"><table class="table ntrc-table"><thead><tr>';
        foreach ($headers as $header) {
            $html .= '<th>' . NtRcAdminThemeHelper::esc($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ((array)$row as $cell) {
                $html .= '<td>' . NtRcAdminThemeHelper::esc($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        if (!$rows) {
            $html .= '<tr><td colspan="' . max(1, count($headers)) . '">' . NtRcAdminThemeHelper::esc($emptyMessage) . '</td></tr>';
        }

        return $html . '</tbody></table></div>';
    }
}
