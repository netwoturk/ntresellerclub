<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcMailTemplateManager.php';
require_once __DIR__ . '/NtRcNotificationQueueManager.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcNotificationEngine
{
    protected $expiryDays = array(30, 15, 7, 1);

    public function run($limit = 10, array $monitoringResult = array())
    {
        NtRcRuntimeGuard::beforeHeavyProcess('notification_engine');
        NtRcInstaller::ensureNotificationSchema();
        NtRcMailTemplateManager::seedDefaultTemplates();

        $created = array(
            'monitoring' => $this->enqueueMonitoringNotifications($monitoringResult),
            'expiry' => $this->enqueueExpiryNotifications(),
        );

        $processed = NtRcNotificationQueueManager::processPending($limit);

        return array(
            'success' => true,
            'created' => $created,
            'processed' => $processed,
        );
    }

    public function enqueueServiceNotification($templateKey, $idService, array $variables = array(), $recipientType = 'customer', $priority = 3, $dedupeKey = null)
    {
        $service = $this->getService((int)$idService);
        if (!$service) {
            return array('success' => false, 'message' => 'Notification icin servis bulunamadi.');
        }

        if ($recipientType === 'customer') {
            $recipient = $this->customerRecipient((int)$service['id_customer']);
        } else {
            $recipient = $this->adminRecipient($recipientType);
        }

        if (empty($recipient['email'])) {
            return array('success' => false, 'message' => 'Notification alicisi bulunamadi.');
        }

        $variables = array_merge($this->serviceVariables($service), $variables);
        $langIso = isset($recipient['lang_iso']) ? $recipient['lang_iso'] : $this->defaultLangIso();

        return NtRcNotificationQueueManager::enqueue(
            $templateKey,
            $recipientType,
            $recipient,
            $variables,
            $langIso,
            isset($service['id_customer']) ? (int)$service['id_customer'] : null,
            (int)$service['id_ntresellerclub_service'],
            $priority,
            $dedupeKey
        );
    }

    public function enqueueExpiryNotification($idService, $days)
    {
        $days = (int)$days;
        if (!in_array($days, $this->expiryDays, true)) {
            return array('success' => false, 'message' => 'Desteklenmeyen expiry notification gunu.');
        }

        $service = $this->getService((int)$idService);
        if (!$service) {
            return array('success' => false, 'message' => 'Expiry notification icin servis bulunamadi.');
        }

        $serviceType = isset($service['service_type']) ? (string)$service['service_type'] : '';
        if (!in_array($serviceType, array('domain', 'tr_domain', 'ssl'), true)) {
            return array('success' => true, 'source' => 'skipped', 'message' => 'Expiry notification sadece domain ve SSL servisleri icin hazir.');
        }

        $expiryDate = !empty($service['expiry_date']) ? $service['expiry_date'] : date('Y-m-d', strtotime('+' . $days . ' day'));
        $templateKey = $serviceType === 'ssl' ? 'ssl_expired' : 'domain_expiring_' . $days;

        return $this->enqueueServiceNotification(
            $templateKey,
            (int)$idService,
            array(
                'days_before' => $days,
                'checked_at' => date('Y-m-d H:i:s'),
            ),
            'customer',
            3,
            'service_expiry:' . $templateKey . ':' . (int)$idService . ':' . $expiryDate
        );
    }

    public function enqueueAdminNotification($templateKey, array $variables = array(), $recipientType = 'admin', $dedupeKey = null, $priority = 2)
    {
        $recipient = $this->adminRecipient($recipientType);
        if (empty($recipient['email'])) {
            return array('success' => false, 'message' => 'Admin notification alicisi bulunamadi.');
        }

        return NtRcNotificationQueueManager::enqueue(
            $templateKey,
            $recipientType,
            $recipient,
            $variables,
            isset($recipient['lang_iso']) ? $recipient['lang_iso'] : $this->defaultLangIso(),
            null,
            null,
            $priority,
            $dedupeKey
        );
    }

    public function enqueueMonitoringNotifications(array $monitoringResult = array())
    {
        $created = array();
        $failed = $this->failedQueueSummary();

        if ((int)$failed['failed_count'] > 0) {
            $created[] = $this->enqueueAdminNotification(
                'queue_failed_admin',
                array(
                    'queue_id' => $failed['latest_queue_id'],
                    'provider_code' => $failed['provider_code'],
                    'error_message' => $failed['last_error'],
                    'checked_at' => date('Y-m-d H:i:s'),
                ),
                'admin',
                'queue_failed_admin:' . date('Y-m-d') . ':' . (int)$failed['failed_count'],
                1
            );
        }

        foreach ($this->providerHealthRows($monitoringResult) as $row) {
            if (!$this->isProviderDownStatus($row)) {
                continue;
            }

            $created[] = $this->enqueueAdminNotification(
                'provider_down_admin',
                array(
                    'provider_code' => isset($row['provider_code']) ? $row['provider_code'] : '',
                    'provider_status' => isset($row['status']) ? $row['status'] : '',
                    'error_message' => isset($row['last_error']) ? $row['last_error'] : '',
                    'checked_at' => isset($row['checked_at']) ? $row['checked_at'] : date('Y-m-d H:i:s'),
                ),
                'technical_admin',
                'provider_down_admin:' . date('Y-m-d') . ':' . (isset($row['provider_code']) ? $row['provider_code'] : '') . ':' . (isset($row['status']) ? $row['status'] : ''),
                1
            );
        }

        return $created;
    }

    public function enqueueExpiryNotifications()
    {
        $created = array();

        foreach ($this->expiryDays as $days) {
            $targetDate = date('Y-m-d', strtotime('+' . (int)$days . ' day'));
            $rows = Db::getInstance()->executeS(
                'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` '
                . 'WHERE service_type IN ("domain", "tr_domain", "ssl") '
                . 'AND status IN ("active", "ready") '
                . 'AND expiry_date="' . pSQL($targetDate) . '" '
                . 'ORDER BY id_ntresellerclub_service ASC LIMIT 25'
            );

            foreach ((array)$rows as $service) {
                $created[] = $this->enqueueExpiryNotification((int)$service['id_ntresellerclub_service'], (int)$days);
            }
        }

        return $created;
    }

    protected function providerHealthRows(array $monitoringResult)
    {
        if (isset($monitoringResult['health']['providers']) && is_array($monitoringResult['health']['providers'])) {
            return $monitoringResult['health']['providers'];
        }

        $rows = Db::getInstance()->executeS(
            'SELECT h.* FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider_health` h '
            . 'INNER JOIN ('
            . 'SELECT provider_code, MAX(checked_at) AS checked_at FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider_health` GROUP BY provider_code'
            . ') latest ON latest.provider_code=h.provider_code AND latest.checked_at=h.checked_at'
        );

        return is_array($rows) ? $rows : array();
    }

    protected function isProviderDownStatus(array $row)
    {
        $status = isset($row['status']) ? strtolower((string)$row['status']) : '';
        return in_array($status, array('down', 'warning', 'error'), true);
    }

    protected function failedQueueSummary()
    {
        $row = Db::getInstance()->getRow(
            'SELECT id_ntresellerclub_operation_queue, provider_code, last_error FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` '
            . 'WHERE status="failed" ORDER BY updated_at DESC, id_ntresellerclub_operation_queue DESC'
        );

        return array(
            'failed_count' => (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` WHERE status="failed"'),
            'latest_queue_id' => $row ? (int)$row['id_ntresellerclub_operation_queue'] : 0,
            'provider_code' => $row && !empty($row['provider_code']) ? $row['provider_code'] : '',
            'last_error' => $row && !empty($row['last_error']) ? $this->safeText($row['last_error']) : '',
        );
    }

    protected function getService($idService)
    {
        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE id_ntresellerclub_service=' . (int)$idService
        );
    }

    protected function customerRecipient($idCustomer)
    {
        $row = Db::getInstance()->getRow(
            'SELECT id_customer, firstname, lastname, email, id_lang FROM `' . _DB_PREFIX_ . 'customer` WHERE id_customer=' . (int)$idCustomer
        );
        if (!$row || empty($row['email'])) {
            return array();
        }

        return array(
            'email' => $row['email'],
            'name' => trim($row['firstname'] . ' ' . $row['lastname']),
            'lang_iso' => $this->langIsoById(isset($row['id_lang']) ? (int)$row['id_lang'] : 0),
        );
    }

    protected function adminRecipient($recipientType)
    {
        $email = $recipientType === 'technical_admin'
            ? Configuration::get('NTRC_TECHNICAL_ADMIN_EMAIL')
            : Configuration::get('PS_SHOP_EMAIL');

        if (!$email) {
            $email = Configuration::get('PS_SHOP_EMAIL');
        }

        return array(
            'email' => $email,
            'name' => Configuration::get('PS_SHOP_NAME') ?: 'NetwoTurk',
            'lang_iso' => $this->defaultLangIso(),
        );
    }

    protected function serviceVariables(array $service)
    {
        $domain = isset($service['domain_name']) ? $service['domain_name'] : '';
        return array(
            'id_service' => isset($service['id_ntresellerclub_service']) ? (int)$service['id_ntresellerclub_service'] : 0,
            'service_name' => $domain !== '' ? $domain : (isset($service['service_type']) ? $service['service_type'] : ''),
            'service_type' => isset($service['service_type']) ? $service['service_type'] : '',
            'service_status' => isset($service['status']) ? $service['status'] : '',
            'domain_name' => $domain,
            'expiry_date' => isset($service['expiry_date']) ? $service['expiry_date'] : '',
            'provider_code' => isset($service['provider_code']) ? $service['provider_code'] : '',
            'provider_order_id' => isset($service['provider_order_id']) ? $service['provider_order_id'] : '',
            'provider_service_id' => isset($service['provider_service_id']) ? $service['provider_service_id'] : '',
            'ssl_certificate_number' => isset($service['ssl_certificate_number']) ? $service['ssl_certificate_number'] : '',
            'checked_at' => date('Y-m-d H:i:s'),
        );
    }

    protected function langIsoById($idLang)
    {
        if ($idLang > 0 && class_exists('Language')) {
            $language = new Language((int)$idLang);
            if (Validate::isLoadedObject($language) && !empty($language->iso_code)) {
                return strtolower(substr($language->iso_code, 0, 2));
            }
        }
        return $this->defaultLangIso();
    }

    protected function defaultLangIso()
    {
        if (class_exists('Language')) {
            $idLang = (int)Configuration::get('PS_LANG_DEFAULT');
            if ($idLang > 0) {
                $language = new Language($idLang);
                if (Validate::isLoadedObject($language) && !empty($language->iso_code)) {
                    return strtolower(substr($language->iso_code, 0, 2));
                }
            }
        }
        return 'en';
    }

    protected function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|csr|private_key|private-key|certificate|certificate_raw|cert_raw)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
