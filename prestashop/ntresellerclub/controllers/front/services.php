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

        foreach ((array)$items as &$item) {
            $item['manage_url'] = $this->context->link->getModuleLink('ntresellerclub', 'manage', array(
                'id_service' => (int)$item['id_ntresellerclub_service']
            ));
        }

        $this->context->smarty->assign(array(
            'nt_services' => is_array($items) ? $items : array(),
        ));

        $this->setTemplate('module:ntresellerclub/views/templates/front/services.tpl');
    }
}
