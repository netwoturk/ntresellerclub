<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/providers/NtRcProviderFactory.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcDomainActionManager
{
    public function getAvailableActions(array $service)
    {
        if ($service['service_type'] !== 'domain') {
            return array();
        }

        return array(
            'details' => 'Detayları Yenile',
            'nameserver' => 'Nameserver Yönet',
            'dns' => 'DNS Yönet',
            'authcode' => 'Transfer Kodu',
            'lock' => 'Kilit Yönetimi',
            'renew' => 'Yenile',
        );
    }

    public function execute(array $service, $action)
    {
        $provider = NtRcProviderFactory::make($service['provider_code']);
        if (!$provider) {
            return array('success' => false, 'message' => 'Provider aktif veya lisanslı değil.');
        }

        if ($action === 'details') {
            return $provider->getDetails($service['domain_name']);
        }

        NtRcLog::add('info', 'domain_action', 'Action queued: ' . $action . ' for ' . $service['domain_name']);
        return array('success' => true, 'message' => 'İşlem kuyruğa alındı.', 'action' => $action);
    }
}
