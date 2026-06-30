<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/admin/NtRcAdminBaseController.php';
require_once _PS_MODULE_DIR_ . 'ntresellerclub/controllers/admin/AdminNtRcDomainsController.php';

class AdminNtRcTrDomainsController extends AdminNtRcDomainsController
{
    protected $ntRcSection = 'tr_domains';

    protected function renderSectionContent()
    {
        return $this->renderDomainServiceList('tr_domain');
    }
}
