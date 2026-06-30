<?php
class NtresellerclubDomainsearchpageModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function setMedia()
    {
        parent::setMedia();
        $this->registerStylesheet(
            'module-ntresellerclub-domain-search',
            'modules/' . $this->module->name . '/views/css/domain-search.css',
            array('media' => 'all', 'priority' => 150)
        );
        $this->registerJavascript(
            'module-ntresellerclub-domain-search',
            'modules/' . $this->module->name . '/views/js/domain-search.js',
            array('position' => 'bottom', 'priority' => 150)
        );
    }

    public function initContent()
    {
        parent::initContent();

        $this->context->smarty->assign(array(
            'ntrc_domain_search_url' => $this->context->link->getModuleLink('ntresellerclub', 'domainsearch'),
            'ntrc_domain_cart_url' => $this->context->link->getModuleLink('ntresellerclub', 'domaincart', array('action' => 'add')),
            'ntrc_cart_url' => $this->context->link->getPageLink('cart', true, null, array('action' => 'show')),
        ));

        $this->setTemplate('module:ntresellerclub/views/templates/front/domain_search.tpl');
    }
}
