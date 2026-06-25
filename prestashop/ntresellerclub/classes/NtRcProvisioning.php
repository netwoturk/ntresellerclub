<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcOrderOrchestrator.php';

class NtRcProvisioning
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function processOrder($idOrder)
    {
        $orchestrator = new NtRcOrderOrchestrator($this->module);
        return $orchestrator->processOrder((int)$idOrder);
    }
}
