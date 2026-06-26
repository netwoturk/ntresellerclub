<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/admin/NtRcAdminBaseController.php';

class AdminNtRcTrDomainsController extends NtRcAdminBaseController
{
    protected $ntRcSection = 'tr_domains';
}
