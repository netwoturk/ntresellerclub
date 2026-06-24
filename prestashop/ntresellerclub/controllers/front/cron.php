<?php
class NtresellerclubCronModuleFrontController extends ModuleFrontController
{
    public $ssl = true;

    public function initContent()
    {
        parent::initContent();
        header('Content-Type: application/json');

        try {
            require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcRuntimeGuard.php';
            NtRcRuntimeGuard::beforeHeavyProcess('front_cron');

            $token = Tools::getValue('token');
            if (!$token || $token !== Configuration::get('NTRC_CRON_TOKEN')) {
                die(json_encode(array('success' => false, 'message' => 'Invalid token')));
            }

            $limit = NtRcRuntimeGuard::cronBatchLimit(10);

            require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcRenewalManager.php';
            require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcPendingProvisioning.php';
            require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcOperationQueueProcessor.php';

            $renewalManager = new NtRcRenewalManager();
            $renewalResult = $renewalManager->scan();

            $pendingManager = new NtRcPendingProvisioning();
            $pendingResult = $pendingManager->process($limit);

            $operationManager = new NtRcOperationQueueProcessor();
            $operationResult = $operationManager->process($limit);

            $dnaPriceSync = array('success' => false, 'message' => 'DomainNameAPI pasif.');
            if ((int)Configuration::get('NTRC_FEATURE_DOMAINNAMEAPI') === 1) {
                require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcDomainNameApiPriceSync.php';
                $sync = new NtRcDomainNameApiPriceSync();
                $dnaPriceSync = $sync->sync();
            }

            die(json_encode(array(
                'success' => true,
                'limit' => $limit,
                'renewals' => $renewalResult,
                'pending_provisioning' => $pendingResult,
                'operations' => $operationResult,
                'dna_price_sync' => $dnaPriceSync,
            )));
        } catch (Exception $e) {
            die(json_encode(array('success' => false, 'message' => 'Cron hata olustu.', 'error' => $e->getMessage())));
        }
    }
}
