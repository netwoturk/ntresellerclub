<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcDomainOperationQueue.php';
require_once __DIR__ . '/providers/NtRcProviderFactory.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcOperationProcessor
{
    public function process($limit = 10)
    {
        $items = NtRcDomainOperationQueue::pending($limit);
        $results = array();
        foreach ((array)$items as $item) {
            $results[] = $this->processOne($item);
        }
        return $results;
    }

    protected function processOne(array $item)
    {
        $provider = NtRcProviderFactory::make($item['provider_code']);
        if (!$provider) {
            NtRcDomainOperationQueue::markError((int)$item['id_ntresellerclub_operation'], array('message' => 'Provider unavailable'));
            return array('success' => false, 'operation_id' => (int)$item['id_ntresellerclub_operation']);
        }

        $action = $item['action'];
        $domain = $item['domain_name'];
        $response = array('success' => true, 'message' => 'Queued operation accepted', 'action' => $action, 'domain' => $domain);

        if ($action === 'details') {
            $response = $provider->getDetails($domain);
        }

        if ($action === 'renew') {
            $response = $provider->renewDomain($domain, 1);
        }

        if (isset($response['success']) && $response['success']) {
            NtRcDomainOperationQueue::markDone((int)$item['id_ntresellerclub_operation'], $response);
            NtRcLog::add('info', 'operation_processor', 'Operation done');
            return array('success' => true, 'operation_id' => (int)$item['id_ntresellerclub_operation'], 'action' => $action);
        }

        NtRcDomainOperationQueue::markError((int)$item['id_ntresellerclub_operation'], $response);
        NtRcLog::add('error', 'operation_processor', 'Operation error');
        return array('success' => false, 'operation_id' => (int)$item['id_ntresellerclub_operation'], 'action' => $action);
    }
}
