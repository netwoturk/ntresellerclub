<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/providers/NtRcProviderFactory.php';
require_once __DIR__ . '/NtRcDomainOperationQueue.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcDomainActionManager
{
    public function getAvailableActions(array $service)
    {
        if ($service['service_type'] !== 'domain') {
            return array();
        }

        return array(
            'details' => 'Detaylari Yenile',
            'nameserver' => 'Nameserver Yonet',
            'dns' => 'DNS Yonet',
            'authcode' => 'Transfer Kodu',
            'lock' => 'Kilit Yonetimi',
            'renew' => 'Yenile',
        );
    }

    public function execute(array $service, $action)
    {
        $allowed = array_keys($this->getAvailableActions($service));
        if (!in_array($action, $allowed)) {
            return array('success' => false, 'message' => 'Gecersiz islem.');
        }

        $provider = NtRcProviderFactory::make($service['provider_code']);
        if (!$provider) {
            return array('success' => false, 'message' => 'Provider aktif veya lisansli degil.');
        }

        if ($action === 'details') {
            return $provider->getDetails($service['domain_name']);
        }

        NtRcDomainOperationQueue::add(
            (int)$service['id_ntresellerclub_service'],
            $service['provider_code'],
            $service['domain_name'],
            $action,
            array('source' => 'customer_panel')
        );

        NtRcLog::add('info', 'domain_action', 'Action queued: ' . $action . ' for ' . $service['domain_name']);
        return array('success' => true, 'message' => 'Islem kuyruga alindi.', 'action' => $action);
    }
}
