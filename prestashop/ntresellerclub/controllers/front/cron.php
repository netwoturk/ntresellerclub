<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

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

            require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcMonitoringEngine.php';
            $monitoring = new NtRcMonitoringEngine();
            $monitoringResult = $monitoring->run('cron');

            require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcNotificationEngine.php';
            $notification = new NtRcNotificationEngine();
            $notificationResult = $notification->run($limit, $monitoringResult);

            die(json_encode(array(
                'success' => true,
                'limit' => $limit,
                'renewals' => $renewalResult,
                'pending_provisioning' => $pendingResult,
                'operations' => $operationResult,
                'dna_price_sync' => $dnaPriceSync,
                'monitoring' => $monitoringResult,
                'notifications' => $notificationResult,
            )));
        } catch (Exception $e) {
            die(json_encode(array('success' => false, 'message' => 'Cron hata olustu.', 'error' => $this->safeText($e->getMessage()))));
        }
    }

    protected function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
