<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcNotificationEngine.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcRenewalManager
{
    protected $noticeDays = array(30, 15, 7, 1);

    public function scan($limit = null)
    {
        NtRcRuntimeGuard::beforeHeavyProcess('renewal_scan');
        $limit = $limit === null ? NtRcRuntimeGuard::cronBatchLimit(10) : min((int)$limit, NtRcRuntimeGuard::cronBatchLimit(10));
        if ($limit <= 0) {
            $limit = 10;
        }

        $today = new DateTime(date('Y-m-d'));
        $query = 'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` '
            . 'WHERE service_type IN ("domain", "tr_domain") '
            . 'AND status IN ("active", "ready") '
            . 'AND expiry_date IS NOT NULL '
            . 'ORDER BY expiry_date ASC LIMIT ' . (int)$limit;
        $services = Db::getInstance()->executeS($query);
        $results = array();

        foreach ((array)$services as $service) {
            try {
                $expiry = new DateTime($service['expiry_date']);
                $days = (int)$today->diff($expiry)->format('%r%a');
                if (in_array($days, $this->noticeDays, true)) {
                    $results[] = $this->enqueueNotice($service, $days);
                }
            } catch (Exception $e) {
                $idService = isset($service['id_ntresellerclub_service']) ? (int)$service['id_ntresellerclub_service'] : 0;
                $error = $this->safeText($e->getMessage());
                NtRcLog::add('error', 'renewal_scan', 'Exception service=' . $idService . ' ' . $error);
                $results[] = array('service_id' => $idService, 'success' => false, 'error' => $error);
            }
        }

        return array('success' => true, 'limit' => $limit, 'count' => count($results), 'items' => $results);
    }

    protected function enqueueNotice(array $service, $days)
    {
        $idService = isset($service['id_ntresellerclub_service']) ? (int)$service['id_ntresellerclub_service'] : 0;
        if ($idService <= 0) {
            return array('service_id' => 0, 'days' => (int)$days, 'success' => false, 'message' => 'Servis ID bulunamadi.');
        }

        $engine = new NtRcNotificationEngine();
        $result = $engine->enqueueExpiryNotification($idService, (int)$days);
        $status = !empty($result['source']) && $result['source'] === 'dedupe' ? 'already_queued' : 'queued';

        if (!empty($result['success'])) {
            NtRcLog::add('info', 'renewal_scan', 'Renewal notification queued service=' . $idService . ' days=' . (int)$days . ' status=' . $status);
            return array(
                'service_id' => $idService,
                'days' => (int)$days,
                'success' => true,
                'status' => $status,
                'queue_id' => isset($result['queue_id']) ? (int)$result['queue_id'] : null,
                'dedupe_key' => isset($result['dedupe_key']) ? $result['dedupe_key'] : null,
            );
        }

        $message = isset($result['message']) ? $this->safeText($result['message']) : 'Renewal notification queue kaydi olusturulamadi.';
        NtRcLog::add('warning', 'renewal_scan', 'Renewal notification enqueue failed service=' . $idService . ' days=' . (int)$days . ' ' . $message);
        return array('service_id' => $idService, 'days' => (int)$days, 'success' => false, 'message' => $message);
    }

    protected function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
