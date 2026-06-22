<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ntdomainsearch extends Module
{
    public function __construct()
    {
        $this->name = 'ntdomainsearch';
        $this->tab = 'front_office_features';
        $this->version = '0.1.0';
        $this->author = 'NetwoTurk';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => _PS_VERSION_);
        parent::__construct();
        $this->displayName = $this->l('NetwoTurk Domain Search');
        $this->description = $this->l('ResellerClub API destekli domain arama kutusu.');
    }

    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHome')
            && $this->registerHook('displayHeader');
    }

    public function hookDisplayHeader()
    {
        $this->context->controller->registerStylesheet('ntdomainsearch-css', 'modules/' . $this->name . '/views/css/ntdomainsearch.css');
        $this->context->controller->registerJavascript('ntdomainsearch-js', 'modules/' . $this->name . '/views/js/ntdomainsearch.js', array('position' => 'bottom', 'priority' => 150));
        Media::addJsDef(array(
            'ntDomainSearchAjax' => $this->context->link->getModuleLink($this->name, 'search'),
            'ntDomainAddToCartAjax' => $this->context->link->getModuleLink($this->name, 'addtocart')
        ));
    }

    public function hookDisplayHome($params)
    {
        $this->context->smarty->assign(array('nt_tlds' => array('com', 'net', 'org', 'com.tr')));
        return $this->display(__FILE__, 'views/templates/hook/searchbox.tpl');
    }
}
