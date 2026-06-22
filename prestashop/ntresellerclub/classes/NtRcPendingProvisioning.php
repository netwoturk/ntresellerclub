<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/providers/NtRcProviderFactory.php';
require_once __DIR__ . '/NtRcLog.php';
require_once __DIR__ . '/NtRcProvisioningMode.php';

class NtRcPendingProvisioning
{
    public function process($limit = 10)
    {
        $services = Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE status="pending" AND service_type="domain" ORDER BY id_ntresellerclub_service ASC LIMIT ' . (int)$limit
        );

        $results = array();
        foreach ((array)$services as $service) {
            $results[] = $this->processDomain($service);
        }

        return $results;
    }

    protected function processDomain(array $service)
    {
        $idService = (int)$service['id_ntresellerclub_service'];
        $providerCode = $service['provider_code'];
        $domainName = $service['domain_name'];

        if (!$providerCode || !$domainName) {
            $this->markError($idService, 'Provider veya domain bilgisi eksik.');
            return array('service_id' => $idService, 'success' => false);
        }

        $provider = NtRcProviderFactory::make($providerCode);
        if (!$provider) {
            $this->markError($idService, 'Aktif provider bulunamadı.');
            return array('service_id' => $idService, 'success' => false);
        }

        if (NtRcProvisioningMode::isSafe()) {
            Db::getInstance()->update('ntresellerclub_service', array(
                'status' => pSQL('ready'),
                'last_sync' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ), 'id_ntresellerclub_service=' . $idService);

            NtRcLog::add('info', 'pending_provisioning', 'Safe mode ready: ' . $domainName . ' via ' . $providerCode);
            return array('service_id' => $idService, 'success' => true, 'domain' => $domainName, 'provider' => $providerCode, 'status' => 'ready');
        }

        Db::getInstance()->update('ntresellerclub_service', array(
            'status' => pSQL('register_waiting'),
            'last_sync' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_service=' . $idService);

        NtRcLog::add('info', 'pending_provisioning', 'Live mode register waiting: ' . $domainName . ' via ' . $providerCode);
        return array('service_id' => $idService, 'success' => true, 'domain' => $domainName, 'provider' => $providerCode, 'status' => 'register_waiting');
    }

    protected function markError($idService, $message)
    {
        Db::getInstance()->update('ntresellerclub_service', array(
            'status' => pSQL('error'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_service=' . (int)$idService);
        NtRcLog::add('error', 'pending_provisioning', $message);
    }
}
