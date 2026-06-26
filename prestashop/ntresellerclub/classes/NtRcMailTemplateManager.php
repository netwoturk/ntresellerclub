<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';

class NtRcMailTemplateManager
{
    const DEFAULT_LANG = 'en';

    public static function languages()
    {
        return array('tr', 'en', 'de', 'fr', 'es', 'it');
    }

    public static function templateKeys()
    {
        return array(
            'domain_registered',
            'domain_transfer_started',
            'domain_renewed',
            'domain_expiring_30',
            'domain_expiring_15',
            'domain_expiring_7',
            'domain_expiring_1',
            'hosting_created',
            'hosting_renewed',
            'ssl_created',
            'ssl_renewed',
            'ssl_expired',
            'ssl_reissue_required',
            'queue_failed_admin',
            'provider_credit_required',
            'provider_down_admin',
            'payment_required',
            'service_suspended',
            'service_expired',
        );
    }

    public static function seedDefaultTemplates()
    {
        NtRcInstaller::ensureNotificationSchema();

        $inserted = 0;
        foreach (self::templateKeys() as $templateKey) {
            $recipientType = self::defaultRecipientType($templateKey);
            foreach (self::languages() as $langIso) {
                if (self::templateExists($templateKey, $langIso, $recipientType)) {
                    continue;
                }
                $template = self::buildDefaultTemplate($templateKey, $langIso, $recipientType);
                if (self::insertTemplate($template)) {
                    $inserted++;
                }
            }
        }

        return array('success' => true, 'inserted' => $inserted);
    }

    public static function getTemplate($templateKey, $langIso = self::DEFAULT_LANG, $recipientType = null)
    {
        NtRcInstaller::ensureNotificationSchema();

        $templateKey = trim((string)$templateKey);
        $langIso = self::normalizeLang($langIso);
        $recipientType = $recipientType ?: self::defaultRecipientType($templateKey);

        foreach (array($langIso, self::DEFAULT_LANG, 'tr') as $candidateLang) {
            $row = Db::getInstance()->getRow(
                'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_template` '
                . 'WHERE template_key="' . pSQL($templateKey) . '" '
                . 'AND lang_iso="' . pSQL($candidateLang) . '" '
                . 'AND recipient_type="' . pSQL($recipientType) . '" '
                . 'AND is_active=1'
            );
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    public static function render($templateKey, array $variables = array(), $langIso = self::DEFAULT_LANG, $recipientType = null)
    {
        self::seedDefaultTemplates();

        $recipientType = $recipientType ?: self::defaultRecipientType($templateKey);
        $template = self::getTemplate($templateKey, $langIso, $recipientType);
        if (!$template) {
            return array('success' => false, 'message' => 'Notification template bulunamadi.');
        }

        $variables = self::defaultVariables($variables);
        $subject = self::replaceVariables($template['subject'], $variables, false);
        $bodyHtml = self::replaceVariables($template['body_html'], $variables, true);
        $bodyText = self::replaceVariables($template['body_text'], $variables, false);

        return array(
            'success' => true,
            'template_id' => (int)$template['id_ntresellerclub_notification_template'],
            'template_key' => $templateKey,
            'lang_iso' => self::normalizeLang($langIso),
            'recipient_type' => $recipientType,
            'subject' => self::safeText($subject),
            'body_html' => self::safeText($bodyHtml),
            'body_text' => self::safeText($bodyText),
        );
    }

    public static function defaultRecipientType($templateKey)
    {
        return in_array($templateKey, array('queue_failed_admin', 'provider_credit_required', 'provider_down_admin'), true) ? 'admin' : 'customer';
    }

    protected static function templateExists($templateKey, $langIso, $recipientType)
    {
        return (bool)Db::getInstance()->getValue(
            'SELECT id_ntresellerclub_notification_template FROM `' . _DB_PREFIX_ . 'ntresellerclub_notification_template` '
            . 'WHERE template_key="' . pSQL($templateKey) . '" '
            . 'AND lang_iso="' . pSQL($langIso) . '" '
            . 'AND recipient_type="' . pSQL($recipientType) . '"'
        );
    }

    protected static function insertTemplate(array $template)
    {
        return Db::getInstance()->insert('ntresellerclub_notification_template', array(
            'template_key' => pSQL($template['template_key']),
            'lang_iso' => pSQL($template['lang_iso']),
            'recipient_type' => pSQL($template['recipient_type']),
            'subject' => pSQL($template['subject']),
            'body_html' => pSQL($template['body_html'], true),
            'body_text' => pSQL($template['body_text'], true),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));
    }

    protected static function buildDefaultTemplate($templateKey, $langIso, $recipientType)
    {
        $label = self::eventLabel($templateKey, $langIso);
        $subject = $recipientType === 'admin'
            ? '[NetwoTurk] ' . $label
            : $label . ' - {{service_name}}';

        if ($recipientType === 'admin') {
            $bodyHtml = '<p>' . self::word($langIso, 'admin_hello') . '</p>'
                . '<p><strong>' . $label . '</strong></p>'
                . '<p>Provider: {{provider_code}}<br>Queue: {{queue_id}}<br>Status: {{provider_status}}<br>Error: {{error_message}}</p>'
                . '<p>Checked at: {{checked_at}}</p>';
            $bodyText = self::word($langIso, 'admin_hello') . "\n\n" . $label
                . "\nProvider: {{provider_code}}\nQueue: {{queue_id}}\nStatus: {{provider_status}}\nError: {{error_message}}\nChecked at: {{checked_at}}";
        } else {
            $bodyHtml = '<p>' . self::word($langIso, 'hello') . ' {{customer_name}},</p>'
                . '<p>' . $label . '</p>'
                . '<p>Service: {{service_name}}<br>Domain: {{domain_name}}<br>Expiry: {{expiry_date}}</p>'
                . '<p>{{action_url}}</p>'
                . '<p>NetwoTurk</p>';
            $bodyText = self::word($langIso, 'hello') . ' {{customer_name}},'
                . "\n\n" . $label
                . "\nService: {{service_name}}\nDomain: {{domain_name}}\nExpiry: {{expiry_date}}\n{{action_url}}\n\nNetwoTurk";
        }

        return array(
            'template_key' => $templateKey,
            'lang_iso' => $langIso,
            'recipient_type' => $recipientType,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        );
    }

    protected static function eventLabel($templateKey, $langIso)
    {
        $labels = array(
            'domain_registered' => array('tr' => 'Alan adiniz kaydedildi', 'en' => 'Your domain has been registered', 'de' => 'Ihre Domain wurde registriert', 'fr' => 'Votre domaine a ete enregistre', 'es' => 'Su dominio ha sido registrado', 'it' => 'Il tuo dominio e stato registrato'),
            'domain_transfer_started' => array('tr' => 'Alan adi transferiniz basladi', 'en' => 'Your domain transfer has started', 'de' => 'Ihr Domaintransfer wurde gestartet', 'fr' => 'Votre transfert de domaine a commence', 'es' => 'La transferencia de su dominio ha comenzado', 'it' => 'Il trasferimento del dominio e iniziato'),
            'domain_renewed' => array('tr' => 'Alan adiniz yenilendi', 'en' => 'Your domain has been renewed', 'de' => 'Ihre Domain wurde verlaengert', 'fr' => 'Votre domaine a ete renouvele', 'es' => 'Su dominio ha sido renovado', 'it' => 'Il tuo dominio e stato rinnovato'),
            'domain_expiring_30' => array('tr' => 'Alan adiniz 30 gun icinde sona erecek', 'en' => 'Your domain expires in 30 days', 'de' => 'Ihre Domain laeuft in 30 Tagen ab', 'fr' => 'Votre domaine expire dans 30 jours', 'es' => 'Su dominio caduca en 30 dias', 'it' => 'Il tuo dominio scade tra 30 giorni'),
            'domain_expiring_15' => array('tr' => 'Alan adiniz 15 gun icinde sona erecek', 'en' => 'Your domain expires in 15 days', 'de' => 'Ihre Domain laeuft in 15 Tagen ab', 'fr' => 'Votre domaine expire dans 15 jours', 'es' => 'Su dominio caduca en 15 dias', 'it' => 'Il tuo dominio scade tra 15 giorni'),
            'domain_expiring_7' => array('tr' => 'Alan adiniz 7 gun icinde sona erecek', 'en' => 'Your domain expires in 7 days', 'de' => 'Ihre Domain laeuft in 7 Tagen ab', 'fr' => 'Votre domaine expire dans 7 jours', 'es' => 'Su dominio caduca en 7 dias', 'it' => 'Il tuo dominio scade tra 7 giorni'),
            'domain_expiring_1' => array('tr' => 'Alan adiniz 1 gun icinde sona erecek', 'en' => 'Your domain expires in 1 day', 'de' => 'Ihre Domain laeuft in 1 Tag ab', 'fr' => 'Votre domaine expire dans 1 jour', 'es' => 'Su dominio caduca en 1 dia', 'it' => 'Il tuo dominio scade tra 1 giorno'),
            'hosting_created' => array('tr' => 'Hosting hizmetiniz olusturuldu', 'en' => 'Your hosting service has been created', 'de' => 'Ihr Hosting wurde erstellt', 'fr' => 'Votre hebergement a ete cree', 'es' => 'Su hosting ha sido creado', 'it' => 'Il tuo hosting e stato creato'),
            'hosting_renewed' => array('tr' => 'Hosting hizmetiniz yenilendi', 'en' => 'Your hosting service has been renewed', 'de' => 'Ihr Hosting wurde verlaengert', 'fr' => 'Votre hebergement a ete renouvele', 'es' => 'Su hosting ha sido renovado', 'it' => 'Il tuo hosting e stato rinnovato'),
            'ssl_created' => array('tr' => 'SSL hizmetiniz olusturuldu', 'en' => 'Your SSL service has been created', 'de' => 'Ihr SSL-Dienst wurde erstellt', 'fr' => 'Votre service SSL a ete cree', 'es' => 'Su servicio SSL ha sido creado', 'it' => 'Il tuo servizio SSL e stato creato'),
            'ssl_renewed' => array('tr' => 'SSL hizmetiniz yenilendi', 'en' => 'Your SSL service has been renewed', 'de' => 'Ihr SSL-Dienst wurde verlaengert', 'fr' => 'Votre service SSL a ete renouvele', 'es' => 'Su servicio SSL ha sido renovado', 'it' => 'Il tuo servizio SSL e stato rinnovato'),
            'ssl_expired' => array('tr' => 'SSL hizmetiniz sona erdi', 'en' => 'Your SSL service has expired', 'de' => 'Ihr SSL-Dienst ist abgelaufen', 'fr' => 'Votre service SSL a expire', 'es' => 'Su servicio SSL ha caducado', 'it' => 'Il tuo servizio SSL e scaduto'),
            'ssl_reissue_required' => array('tr' => 'SSL yeniden duzenleme gerekiyor', 'en' => 'SSL reissue is required', 'de' => 'SSL-Neuausstellung erforderlich', 'fr' => 'Reemission SSL requise', 'es' => 'Se requiere reemision SSL', 'it' => 'Ri-emissione SSL richiesta'),
            'queue_failed_admin' => array('tr' => 'Queue failed bildirimi', 'en' => 'Queue failure notification', 'de' => 'Queue-Fehlerbenachrichtigung', 'fr' => 'Notification d echec de queue', 'es' => 'Notificacion de error de cola', 'it' => 'Notifica errore coda'),
            'provider_credit_required' => array('tr' => 'Provider kredi gerekiyor', 'en' => 'Provider credit required', 'de' => 'Provider-Guthaben erforderlich', 'fr' => 'Credit provider requis', 'es' => 'Credito del proveedor requerido', 'it' => 'Credito provider richiesto'),
            'provider_down_admin' => array('tr' => 'Provider saglik uyarisi', 'en' => 'Provider health warning', 'de' => 'Provider-Gesundheitswarnung', 'fr' => 'Alerte de sante provider', 'es' => 'Alerta de salud del proveedor', 'it' => 'Avviso salute provider'),
            'payment_required' => array('tr' => 'Odeme gerekiyor', 'en' => 'Payment required', 'de' => 'Zahlung erforderlich', 'fr' => 'Paiement requis', 'es' => 'Pago requerido', 'it' => 'Pagamento richiesto'),
            'service_suspended' => array('tr' => 'Hizmetiniz askida', 'en' => 'Your service is suspended', 'de' => 'Ihr Dienst ist gesperrt', 'fr' => 'Votre service est suspendu', 'es' => 'Su servicio esta suspendido', 'it' => 'Il tuo servizio e sospeso'),
            'service_expired' => array('tr' => 'Hizmetiniz sona erdi', 'en' => 'Your service has expired', 'de' => 'Ihr Dienst ist abgelaufen', 'fr' => 'Votre service a expire', 'es' => 'Su servicio ha caducado', 'it' => 'Il tuo servizio e scaduto'),
        );

        $langIso = self::normalizeLang($langIso);
        if (isset($labels[$templateKey][$langIso])) {
            return $labels[$templateKey][$langIso];
        }
        return isset($labels[$templateKey][self::DEFAULT_LANG]) ? $labels[$templateKey][self::DEFAULT_LANG] : $templateKey;
    }

    protected static function word($langIso, $key)
    {
        $words = array(
            'hello' => array('tr' => 'Merhaba', 'en' => 'Hello', 'de' => 'Hallo', 'fr' => 'Bonjour', 'es' => 'Hola', 'it' => 'Ciao'),
            'admin_hello' => array('tr' => 'Merhaba, admin bildirimi olustu.', 'en' => 'Hello, an admin notification was created.', 'de' => 'Hallo, eine Admin-Benachrichtigung wurde erstellt.', 'fr' => 'Bonjour, une notification admin a ete creee.', 'es' => 'Hola, se creo una notificacion de admin.', 'it' => 'Ciao, e stata creata una notifica admin.'),
        );
        $langIso = self::normalizeLang($langIso);
        return isset($words[$key][$langIso]) ? $words[$key][$langIso] : $words[$key][self::DEFAULT_LANG];
    }

    protected static function defaultVariables(array $variables)
    {
        $defaults = array(
            'customer_name' => '',
            'service_name' => '',
            'domain_name' => '',
            'expiry_date' => '',
            'action_url' => '',
            'provider_code' => '',
            'provider_status' => '',
            'ssl_certificate_number' => '',
            'queue_id' => '',
            'error_message' => '',
            'checked_at' => date('Y-m-d H:i:s'),
        );
        return array_merge($defaults, $variables);
    }

    protected static function replaceVariables($text, array $variables, $html)
    {
        foreach ($variables as $key => $value) {
            $value = self::safeValue($value, $html);
            $text = str_replace('{{' . $key . '}}', $value, $text);
        }
        return $text;
    }

    protected static function safeValue($value, $html)
    {
        $value = self::safeText(is_scalar($value) ? (string)$value : json_encode($value));
        if ($html) {
            return class_exists('Tools') ? Tools::safeOutput($value) : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
        return strip_tags($value);
    }

    protected static function normalizeLang($langIso)
    {
        $langIso = strtolower(substr(trim((string)$langIso), 0, 2));
        return in_array($langIso, self::languages(), true) ? $langIso : self::DEFAULT_LANG;
    }

    protected static function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|csr|private_key|private-key|certificate|certificate_raw|cert_raw)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
