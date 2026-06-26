<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/admin/NtRcAdminBaseController.php';

class AdminNtRcQueueController extends NtRcAdminBaseController
{
    protected $ntRcSection = 'queue';
}
