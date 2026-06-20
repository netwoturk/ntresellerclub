<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Testmodule extends Module
{
    public function __construct()
    {
        $this->name = 'testmodule';
        parent::__construct();
    }
}
