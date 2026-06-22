<?php
class NtresellerclubManageModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcServiceRepository.php';
        require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcDomainActionManager.php';

        $idService = (int)Tools::getValue('id_service');
        $service = NtRcServiceRepository::getCustomerService($idService, (int)$this->context->customer->id);

        if (!$service) {
            $this->errors[] = $this->module->l('Hizmet bulunamadı.');
            $this->redirectWithNotifications($this->context->link->getModuleLink('ntresellerclub', 'services'));
        }

        $manager = new NtRcDomainActionManager();
        $actions = $manager->getAvailableActions($service);
        $actionResult = null;

        if (Tools::isSubmit('nt_service_action')) {
            $action = Tools::getValue('nt_service_action');
            $actionResult = $manager->execute($service, $action);
        }

        $this->context->smarty->assign(array(
            'nt_service' => $service,
            'nt_actions' => $actions,
            'nt_action_result' => $actionResult,
            'nt_back_url' => $this->context->link->getModuleLink('ntresellerclub', 'services'),
        ));

        $this->setTemplate('module:ntresellerclub/views/templates/front/manage.tpl');
    }
}
