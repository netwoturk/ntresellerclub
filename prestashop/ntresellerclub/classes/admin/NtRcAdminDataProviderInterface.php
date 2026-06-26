<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

interface NtRcAdminDataProviderInterface
{
    public function getSummary();
}
