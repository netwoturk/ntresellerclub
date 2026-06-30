<?php
class NtresellerclubServicesModuleFrontController extends ModuleFrontController
{
    public $auth = true;
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();

        $items = Db::getInstance()->executeS(
            'SELECT s.*, q.status AS queue_status '
            . 'FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` s '
            . 'LEFT JOIN `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` q ON q.id_service = s.id_ntresellerclub_service '
            . 'AND q.id_ntresellerclub_operation_queue = ('
                . 'SELECT MAX(q2.id_ntresellerclub_operation_queue) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` q2 '
                . 'WHERE q2.id_service = s.id_ntresellerclub_service'
            . ') '
            . 'WHERE s.id_customer=' . (int)$this->context->customer->id . ' '
            . 'AND s.service_type IN ("domain", "tr_domain") '
            . 'ORDER BY s.created_at DESC'
        );

        foreach ((array)$items as &$item) {
            $item['manage_url'] = $this->context->link->getModuleLink('ntresellerclub', 'manage', array(
                'id_service' => (int)$item['id_ntresellerclub_service']
            ));
        }

        $this->context->smarty->assign(array('nt_services' => is_array($items) ? $items : array()));

        $this->setTemplate('module:ntresellerclub/views/templates/front/services.tpl');
    }
}
