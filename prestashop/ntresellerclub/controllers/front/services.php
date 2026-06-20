<?php
class NtresellerclubServicesModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $items = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE id_customer=' . (int)$this->context->customer->id . ' ORDER BY expiry_date ASC'
        );

        $this->context->smarty->assign(array(
            'nt_services' => is_array($items) ? $items : array(),
        ));

        $this->setTemplate('module:ntresellerclub/views/templates/front/services.tpl');
    }
}
