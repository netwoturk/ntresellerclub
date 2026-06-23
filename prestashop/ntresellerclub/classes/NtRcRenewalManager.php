<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcRenewalManager
{
    protected $noticeDays = array(30, 15, 7, 3, 1);

    public function scan($limit = null)
    {
        NtRcRuntimeGuard::beforeHeavyProcess('renewal_scan');
        $limit = $limit === null ? NtRcRuntimeGuard::cronBatchLimit(10) : min((int)$limit, NtRcRuntimeGuard::cronBatchLimit(10));

        $today = new DateTime(date('Y-m-d'));
        $query = 'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE expiry_date IS NOT NULL ORDER BY expiry_date ASC LIMIT ' . (int)$limit;
        $services = Db::getInstance()->executeS($query);
        $results = array();

        foreach ((array)$services as $service) {
            try {
                $expiry = new DateTime($service['expiry_date']);
                $days = (int)$today->diff($expiry)->format('%r%a');
                if (in_array($days, $this->noticeDays)) {
                    $results[] = $this->sendNotice($service, $days);
                }
            } catch (Exception $e) {
                $idService = isset($service['id_ntresellerclub_service']) ? (int)$service['id_ntresellerclub_service'] : 0;
                NtRcLog::add('error', 'renewal_scan', 'Exception service=' . $idService . ' ' . $e->getMessage());
                $results[] = array('service_id' => $idService, 'success' => false, 'error' => $e->getMessage());
            }
        }

        return array('success' => true, 'limit' => $limit, 'count' => count($results), 'items' => $results);
    }

    protected function sendNotice(array $service, $days)
    {
        $idService = (int)$service['id_ntresellerclub_service'];
        $exists = Db::getInstance()->getValue(
            'SELECT id_ntresellerclub_notice FROM `' . _DB_PREFIX_ . 'ntresellerclub_notice` WHERE id_service=' . $idService . ' AND days_before=' . (int)$days
        );

        if ($exists) {
            return array('service_id' => $idService, 'days' => $days, 'status' => 'already_sent');
        }

        $customer = new Customer((int)$service['id_customer']);
        if (!Validate::isLoadedObject($customer)) {
            NtRcLog::add('warning', 'renewal_scan', 'Customer not found service=' . $idService);
            return array('service_id' => $idService, 'days' => $days, 'status' => 'customer_not_found');
        }

        $idLang = (int)$customer->id_lang ?: (int)Configuration::get('PS_LANG_DEFAULT');
        $templateVars = array(
            '{firstname}' => $customer->firstname,
            '{lastname}' => $customer->lastname,
            '{domain_name}' => $service['domain_name'],
            '{days_before}' => (int)$days,
            '{expiry_date}' => $service['expiry_date'],
        );

        $sent = Mail::Send(
            $idLang,
            'renewal_reminder',
            'Hizmet yenileme hatirlatmasi',
            $templateVars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname,
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'ntresellerclub/mails/'
        );

        if (!$sent) {
            NtRcLog::add('error', 'renewal_scan', 'Mail failed service=' . $idService);
            return array('service_id' => $idService, 'days' => $days, 'status' => 'mail_failed');
        }

        Db::getInstance()->insert('ntresellerclub_notice', array(
            'id_service' => $idService,
            'notice_type' => pSQL('renewal'),
            'days_before' => (int)$days,
            'sent_at' => date('Y-m-d H:i:s'),
        ));

        NtRcLog::add('info', 'renewal_scan', 'Renewal notice sent service=' . $idService . ' days=' . (int)$days);
        return array('service_id' => $idService, 'days' => $days, 'status' => 'sent');
    }
}
