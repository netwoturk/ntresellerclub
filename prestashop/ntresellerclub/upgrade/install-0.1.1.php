<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_1_1($module)
{
    require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcInstaller.php';

    $hooks = array(
        'actionValidateOrder',
        'actionOrderStatusPostUpdate',
        'displayCustomerAccount',
        'displayHeader',
        'displayBackOfficeHeader',
    );

    foreach ($hooks as $hook) {
        if (!$module->isRegisteredInHook($hook) && !$module->registerHook($hook)) {
            return false;
        }
    }

    return NtRcInstaller::installSql()
        && NtRcInstaller::installAdminTabs()
        && NtRcInstaller::ensureConfigurationDefaults();
}
