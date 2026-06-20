<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcRenewalManager
{
    protected $noticeDays = array(30, 15, 7, 3, 1);

    public function scan()
    {
        $today = new DateTime(date('Y-m-d'));
        $query = 'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` WHERE expiry_date IS NOT NULL';
        $services = Db::getInstance()->executeS($query);
        $results = array();

        foreach ($services as $service) {
            $expiry = new DateTime($service['expiry_date']);
            $days = (int)$today->diff($expiry)->format('%r%a');
            if (in_array($days, $this->noticeDays)) {
                $results[] = $this->markNotice($service, $days);
            }
        }

        return $results;
    }

    protected function markNotice(array $service, $days)
    {
        $idService = (int)$service['id_ntresellerclub_service'];
        $exists = Db::getInstance()->getValue(
            'SELECT id_ntresellerclub_notice FROM `' . _DB_PREFIX_ . 'ntresellerclub_notice` WHERE id_service=' . $idService . ' AND days_before=' . (int)$days
        );

        if ($exists) {
            return array('service_id' => $idService, 'days' => $days, 'status' => 'already_marked');
        }

        Db::getInstance()->insert('ntresellerclub_notice', array(
            'id_service' => $idService,
            'notice_type' => pSQL('renewal'),
            'days_before' => (int)$days,
            'sent_at' => date('Y-m-d H:i:s'),
        ));

        return array('service_id' => $idService, 'days' => $days, 'status' => 'marked');
    }
}
