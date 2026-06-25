<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';
require_once __DIR__ . '/NtRcNotificationEngine.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcServiceRepository
{
    public static function getCustomerService($idService, $idCustomer)
    {
        self::ensureSchema();

        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE id_ntresellerclub_service=' . (int)$idService . ' AND id_customer=' . (int)$idCustomer
        );
    }

    public static function getService($idService)
    {
        self::ensureSchema();

        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE id_ntresellerclub_service=' . (int)$idService
        );
    }

    public static function updateStatus($idService, $status)
    {
        self::ensureSchema();
        $idService = (int)$idService;
        $status = (string)$status;
        $previous = self::getService($idService);

        $ok = Db::getInstance()->update('ntresellerclub_service', array(
            'status' => pSQL($status),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_service=' . $idService);

        if ($ok && (!$previous || !isset($previous['status']) || $previous['status'] !== $status)) {
            self::enqueueStatusNotification($idService, $status);
        }

        return $ok;
    }

    public static function updateSync($idService, array $fields)
    {
        self::ensureSchema();

        $fields['last_sync'] = date('Y-m-d H:i:s');
        $fields['updated_at'] = date('Y-m-d H:i:s');
        foreach ($fields as $key => $value) {
            if ($value !== null) {
                $fields[$key] = pSQL($value);
            }
        }
        return Db::getInstance()->update('ntresellerclub_service', $fields, 'id_ntresellerclub_service=' . (int)$idService);
    }

    public static function markProvisioned($idService, array $fields = array())
    {
        $data = array('status' => isset($fields['status']) ? $fields['status'] : 'active');

        foreach (array('provider_service_id', 'provider_order_id', 'provider_customer_id', 'provider_contact_id', 'expiry_date') as $key) {
            if (array_key_exists($key, $fields) && $fields[$key] !== null && $fields[$key] !== '') {
                $data[$key] = $fields[$key];
            }
        }

        return self::updateSync($idService, $data);
    }

    protected static function enqueueStatusNotification($idService, $status)
    {
        $templateKey = self::statusTemplateKey($status);
        if (!$templateKey) {
            return;
        }

        try {
            $service = self::getService($idService);
            if (!$service) {
                return;
            }

            $anchor = $status === 'expired' && !empty($service['expiry_date']) ? $service['expiry_date'] : date('Y-m-d');
            $engine = new NtRcNotificationEngine();
            $result = $engine->enqueueServiceNotification(
                $templateKey,
                (int)$idService,
                array(
                    'status' => $status,
                    'status_changed_at' => date('Y-m-d H:i:s'),
                    'checked_at' => date('Y-m-d H:i:s'),
                ),
                'customer',
                2,
                'service_status:' . $templateKey . ':' . (int)$idService . ':' . $anchor
            );

            if (empty($result['success'])) {
                $message = isset($result['message']) ? self::safeText($result['message']) : 'Service lifecycle notification queue kaydi olusturulamadi.';
                NtRcLog::add('warning', 'service_repository', 'Lifecycle notification failed service=' . (int)$idService . ' status=' . pSQL($status) . ' ' . $message);
            }
        } catch (Exception $e) {
            NtRcLog::add('warning', 'service_repository', 'Lifecycle notification exception service=' . (int)$idService . ' status=' . pSQL($status) . ' ' . self::safeText($e->getMessage()));
        }
    }

    protected static function statusTemplateKey($status)
    {
        $map = array(
            'suspended' => 'service_suspended',
            'expired' => 'service_expired',
        );

        return isset($map[$status]) ? $map[$status] : null;
    }

    protected static function ensureSchema()
    {
        return NtRcInstaller::ensureServiceSchema();
    }

    protected static function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
