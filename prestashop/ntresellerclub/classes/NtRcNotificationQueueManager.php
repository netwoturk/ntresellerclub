<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcMailTemplateManager.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcNotificationQueueManager
{
    public static function enqueue($templateKey, $recipientType, array $recipient, array $variables = array(), $langIso = 'en', $idCustomer = null, $idService = null, $priority = 3, $dedupeKey = null, $maxRetries = 3)
    {
        NtRcInstaller::ensureNotificationSchema();
        NtRcMailTemplateManager::seedDefaultTemplates();

        $templateKey = trim((string)$templateKey);
        $recipientType = self::normalizeRecipientType($recipientType);
        $toEmail = isset($recipient['email']) ? trim((string)$recipient['email']) : '';
        $toName = isset($recipient['name']) ? trim((string)$recipient['name']) : '';

        if ($templateKey === '' || $toEmail === '') {
            return array('success' => false, 'message' => 'Notification icin template ve alici e-posta zorunludur.');
        }

        if ($dedupeKey && self::hasDedupe($dedupeKey)) {
            return array('success' => true, 'source' => 'dedupe', 'dedupe_key' => $dedupeKey);
        }

        $rendered = NtRcMailTemplateManager::render($templateKey, $variables, $langIso, $recipientType);
        if (empty($rendered['success'])) {
            return $rendered;
        }

        $data = array(
            'template_key' => pSQL($templateKey),
            'lang_iso' => pSQL($rendered['lang_iso']),
            'recipient_type' => pSQL($recipientType),
            'id_customer' => $idCustomer ? (int)$idCustomer : null,
            'id_service' => $idService ? (int)$idService : null,
            'to_email' => pSQL($toEmail),
            'to_name' => $toName !== '' ? pSQL($toName) : null,
            'subject' => pSQL($rendered['subject']),
            'body_html' => pSQL($rendered['body_html'], true),
            'body_text' => pSQL($rendered['body_text'], true),
            'variables_json' => pSQL(json_encode(self::sanitizeArray($variables)), true),
            'dedupe_key' => $dedupeKey ? pSQL($dedupeKey) : null,
            'priority' => self::normalizePriority($priority),
            'status' => pSQL('pending'),
            'retry_count' => 0,
            'max_retries' => max(1, (int)$maxRetries),
            'available_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        );

        $ok = Db::getInstance()->insert('ntresellerclub_notification_queue', $data);
        if (!$ok) {
            return array('success' => false, 'message' => 'Notification queue kaydi olusturulamadi.');
        }

        return array('success' => true, 'queue_id' => (int)Db::getInstance()->Insert_ID());
    }

    public static function processPending($limit = 10)
    {
        NtRcRuntimeGuard::beforeHeavyProcess('notification_queue_processor');
        NtRcInstaller::ensureNotificationSchema();

        $limit = min((int)$limit, NtRcRuntimeGuard::cronBatchLimit(10));
        if ($limit <= 0) {
            $limit = 10;
        }

        $items = self::pending($limit);
        $results = array();

        foreach ((array)$items as $item) {
            $results[] = self::sendOne($item);
        }

        return array('success' => true, 'limit' => $limit, 'count' => count($results), 'items' => $results);
    }

    public static function pending($limit = 10)
    {
        NtRcInstaller::ensureNotificationSchema();
        $limit = self::normalizeLimit($limit);

        return Db::getInstance()->executeS(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_queue` '
            . 'WHERE status="pending" AND (available_at IS NULL OR available_at <= NOW()) '
            . 'ORDER BY priority ASC, id_ntresellerclub_notification_queue ASC LIMIT ' . (int)$limit
        );
    }

    public static function cancel($idNotificationQueue)
    {
        NtRcInstaller::ensureNotificationSchema();

        return Db::getInstance()->update('ntresellerclub_notification_queue', array(
            'status' => pSQL('cancelled'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_notification_queue=' . (int)$idNotificationQueue . ' AND status IN ("pending", "processing")');
    }

    protected static function sendOne(array $item)
    {
        $idQueue = (int)$item['id_ntresellerclub_notification_queue'];
        $lockToken = self::markProcessing($idQueue);
        if (!$lockToken) {
            return array('success' => false, 'queue_id' => $idQueue, 'message' => 'Notification queue kilitlenemedi.');
        }

        try {
            if (!class_exists('Mail')) {
                throw new Exception('PrestaShop Mail class bulunamadi.');
            }

            $idLang = self::resolveLanguageId($item['lang_iso']);
            $templatePath = _PS_MODULE_DIR_ . 'ntresellerclub/mails/';
            $templateVars = array(
                '{notification_subject}' => self::safeText($item['subject']),
                '{notification_body_html}' => self::safeText($item['body_html']),
                '{notification_body_text}' => self::safeText($item['body_text']),
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            );

            $ok = Mail::Send(
                $idLang,
                'notification',
                self::safeText($item['subject']),
                $templateVars,
                $item['to_email'],
                $item['to_name'],
                null,
                null,
                null,
                null,
                $templatePath,
                false,
                null
            );

            if (!$ok) {
                throw new Exception('Mail::Send false dondu.');
            }

            self::markSent($idQueue, $lockToken);
            self::log($idQueue, $item, 'sent', 'Mail gonderildi.');
            return array('success' => true, 'queue_id' => $idQueue, 'status' => 'sent');
        } catch (Exception $e) {
            $error = self::safeText($e->getMessage());
            self::markRetryOrFailed($item, $error, $lockToken);
            self::log($idQueue, $item, 'failed', $error);
            return array('success' => false, 'queue_id' => $idQueue, 'error' => $error);
        }
    }

    protected static function markProcessing($idQueue)
    {
        $token = sha1((int)$idQueue . '|' . uniqid('', true) . '|' . mt_rand());
        $now = date('Y-m-d H:i:s');
        $ok = Db::getInstance()->update('ntresellerclub_notification_queue', array(
            'status' => pSQL('processing'),
            'lock_token' => pSQL($token),
            'locked_at' => $now,
            'updated_at' => $now,
        ), 'id_ntresellerclub_notification_queue=' . (int)$idQueue . ' AND status="pending" AND (lock_token IS NULL OR lock_token="")');

        if (!$ok) {
            return false;
        }

        $stored = Db::getInstance()->getValue(
            'SELECT lock_token FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_queue` WHERE id_ntresellerclub_notification_queue=' . (int)$idQueue . ' AND status="processing"'
        );

        return $stored === $token ? $token : false;
    }

    protected static function markSent($idQueue, $lockToken)
    {
        return Db::getInstance()->update('ntresellerclub_notification_queue', array(
            'status' => pSQL('sent'),
            'lock_token' => null,
            'locked_at' => null,
            'sent_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), self::queueWhere($idQueue, $lockToken));
    }

    protected static function markRetryOrFailed(array $item, $error, $lockToken)
    {
        $retry = (int)$item['retry_count'] + 1;
        $max = (int)$item['max_retries'];
        $status = $retry >= $max ? 'failed' : 'pending';

        return Db::getInstance()->update('ntresellerclub_notification_queue', array(
            'status' => pSQL($status),
            'retry_count' => $retry,
            'last_error' => pSQL(self::safeText($error)),
            'lock_token' => null,
            'locked_at' => null,
            'updated_at' => date('Y-m-d H:i:s'),
        ), self::queueWhere((int)$item['id_ntresellerclub_notification_queue'], $lockToken));
    }

    protected static function log($idQueue, array $item, $status, $message)
    {
        return Db::getInstance()->insert('ntresellerclub_notification_log', array(
            'id_notification_queue' => (int)$idQueue,
            'template_key' => pSQL($item['template_key']),
            'recipient_type' => pSQL($item['recipient_type']),
            'to_email' => pSQL($item['to_email']),
            'status' => pSQL($status),
            'message' => pSQL(self::safeText($message)),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    protected static function hasDedupe($dedupeKey)
    {
        return (bool)Db::getInstance()->getValue(
            'SELECT id_ntresellerclub_notification_queue FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_queue` '
            . 'WHERE dedupe_key="' . pSQL($dedupeKey) . '" AND status IN ("pending", "processing", "sent")'
        );
    }

    protected static function queueWhere($idQueue, $lockToken)
    {
        return 'id_ntresellerclub_notification_queue=' . (int)$idQueue . ' AND lock_token="' . pSQL($lockToken) . '"';
    }

    protected static function resolveLanguageId($langIso)
    {
        $langIso = strtolower(substr((string)$langIso, 0, 2));
        if (class_exists('Language')) {
            $idLang = (int)Language::getIdByIso($langIso);
            if ($idLang > 0) {
                return $idLang;
            }
        }
        return (int)Configuration::get('PS_LANG_DEFAULT');
    }

    protected static function normalizeRecipientType($recipientType)
    {
        $recipientType = strtolower(trim((string)$recipientType));
        return in_array($recipientType, array('customer', 'admin', 'technical_admin'), true) ? $recipientType : 'customer';
    }

    protected static function normalizePriority($priority)
    {
        $priority = (int)$priority;
        if ($priority < 1) {
            return 1;
        }
        if ($priority > 4) {
            return 4;
        }
        return $priority;
    }

    protected static function normalizeLimit($limit)
    {
        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 10;
        }
        if ($limit > 25) {
            return 25;
        }
        return $limit;
    }

    protected static function sanitizeArray(array $data)
    {
        foreach ($data as $key => $value) {
            if (preg_match('/api-key|api_key|auth-code|auth_code|passwd|password|token|credential|csr|private_key|private-key|certificate|certificate_raw|cert_raw/i', (string)$key)) {
                $data[$key] = '***';
                continue;
            }
            if (is_array($value)) {
                $data[$key] = self::sanitizeArray($value);
            } elseif (is_string($value)) {
                $data[$key] = self::safeText($value);
            }
        }
        return $data;
    }

    protected static function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|csr|private_key|private-key|certificate|certificate_raw|cert_raw)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
