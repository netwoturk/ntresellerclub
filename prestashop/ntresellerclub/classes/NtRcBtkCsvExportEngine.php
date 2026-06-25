<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcFeature.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';

class NtRcBtkCsvExportEngine
{
    const TYPE_HOSTED = 'hosted';
    const TYPE_REGISTERED_ONLY = 'registered_only';
    const CSV_DELIMITER = ',';
    const PLACEHOLDER = '*';

    public function exportHostedDomainsCsv($offset = 0, $limit = null)
    {
        return $this->export(self::TYPE_HOSTED, $offset, $limit);
    }

    public function exportRegisteredOnlyDomainsCsv($offset = 0, $limit = null)
    {
        return $this->export(self::TYPE_REGISTERED_ONLY, $offset, $limit);
    }

    public function buildCsvRow($row)
    {
        $row = is_array($row) ? $row : array();
        $owner = $this->ownerName($row);

        $columns = array(
            $this->sanitizeCsvValue($this->value($row, 'domain_name')),
            $this->sanitizeCsvValue($owner),
            $this->sanitizeCsvValue($this->value($row, 'phone')),
            $this->sanitizeCsvValue($this->firstValue($row, array('contact_email', 'provider_email', 'customer_email'))),
            $this->sanitizeCsvValue($this->formatBtkDate($this->value($row, 'register_date'))),
            $this->sanitizeCsvValue($this->formatBtkDate($this->value($row, 'expiry_date'))),
        );

        if (!$this->validateSixColumns($columns)) {
            $columns = array_pad(array_slice($columns, 0, 6), 6, self::PLACEHOLDER);
        }

        return $columns;
    }

    public function sanitizeCsvValue($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return self::PLACEHOLDER;
        }

        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value = str_replace(array(',', ';', "\r", "\n", "\t"), array('-', '-', ' ', ' ', ' '), $value);
        $value = preg_replace('/\s+/u', ' ', $value);
        $value = trim($value);

        return $value === '' ? self::PLACEHOLDER : $value;
    }

    public function formatBtkDate($value)
    {
        $value = trim((string)$value);
        if ($value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return self::PLACEHOLDER;
        }

        if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $value)) {
            return $value;
        }

        $time = strtotime($value);
        if (!$time) {
            return self::PLACEHOLDER;
        }

        return date('d.m.Y', $time);
    }

    public function validateSixColumns($columns)
    {
        return is_array($columns) && count($columns) === 6;
    }

    protected function export($type, $offset = 0, $limit = null)
    {
        if (!NtRcFeature::isBtkCsvReportingActive()) {
            return '';
        }

        NtRcRuntimeGuard::beforeHeavyProcess('btk_csv_export');

        if ($limit !== null) {
            return $this->rowsToCsv($this->fetchRows($type, (int)$offset, (int)$limit));
        }

        $csv = '';
        $batchLimit = $this->batchLimit();
        $currentOffset = max(0, (int)$offset);

        do {
            $rows = $this->fetchRows($type, $currentOffset, $batchLimit);
            $csv .= $this->rowsToCsv($rows);
            $count = is_array($rows) ? count($rows) : 0;
            $currentOffset += $batchLimit;
        } while ($count === $batchLimit);

        return $csv;
    }

    protected function rowsToCsv($rows)
    {
        $csv = '';
        foreach ((array)$rows as $row) {
            $columns = $this->buildCsvRow($row);
            if (!$this->validateSixColumns($columns)) {
                continue;
            }
            $csv .= implode(self::CSV_DELIMITER, $columns) . "\n";
        }

        return $csv;
    }

    protected function fetchRows($type, $offset, $limit)
    {
        if ($type === self::TYPE_REGISTERED_ONLY) {
            return $this->fetchRegisteredOnlyRows($offset, $limit);
        }

        return $this->fetchHostedRows($offset, $limit);
    }

    protected function fetchHostedRows($offset, $limit)
    {
        $serviceTable = _DB_PREFIX_ . 'ntresellerclub_service';
        $profileTable = _DB_PREFIX_ . 'ntresellerclub_contact_profile';
        $providerCustomerTable = _DB_PREFIX_ . 'ntresellerclub_provider_customer';
        $customerTable = _DB_PREFIX_ . 'customer';
        $statuses = $this->reportableStatusSql();

        $sql = 'SELECT h.domain_name, COALESCE(d.id_customer, h.id_customer) AS id_customer, '
            . 'COALESCE(d.register_date, h.register_date) AS register_date, '
            . 'COALESCE(d.expiry_date, h.expiry_date) AS expiry_date, '
            . 'cp.company_name, cp.first_name, cp.last_name, cp.phone, cp.email AS contact_email, '
            . 'pc.provider_username, pc.email AS provider_email, '
            . 'c.firstname AS customer_firstname, c.lastname AS customer_lastname, c.email AS customer_email '
            . 'FROM ('
            . 'SELECT domain_name, MIN(id_customer) AS id_customer, MIN(NULLIF(start_date, "0000-00-00")) AS register_date, '
            . 'MAX(NULLIF(expiry_date, "0000-00-00")) AS expiry_date '
            . 'FROM `' . pSQL($serviceTable) . '` '
            . 'WHERE service_type="hosting" AND domain_name IS NOT NULL AND domain_name<>"" AND status IN (' . $statuses . ') '
            . 'GROUP BY domain_name'
            . ') h '
            . 'LEFT JOIN ('
            . 'SELECT domain_name, MIN(id_customer) AS id_customer, MIN(NULLIF(start_date, "0000-00-00")) AS register_date, '
            . 'MAX(NULLIF(expiry_date, "0000-00-00")) AS expiry_date '
            . 'FROM `' . pSQL($serviceTable) . '` '
            . 'WHERE service_type IN ("domain", "tr_domain") AND domain_name IS NOT NULL AND domain_name<>"" AND status IN (' . $statuses . ') '
            . 'GROUP BY domain_name'
            . ') d ON d.domain_name=h.domain_name '
            . 'LEFT JOIN `' . pSQL($profileTable) . '` cp ON cp.id_customer=COALESCE(d.id_customer, h.id_customer) AND cp.is_default=1 '
            . 'LEFT JOIN ('
            . 'SELECT id_customer, MAX(provider_username) AS provider_username, MAX(email) AS email '
            . 'FROM `' . pSQL($providerCustomerTable) . '` GROUP BY id_customer'
            . ') pc ON pc.id_customer=COALESCE(d.id_customer, h.id_customer) '
            . 'LEFT JOIN `' . pSQL($customerTable) . '` c ON c.id_customer=COALESCE(d.id_customer, h.id_customer) '
            . 'ORDER BY h.domain_name ASC '
            . 'LIMIT ' . (int)$offset . ', ' . (int)$limit;

        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    protected function fetchRegisteredOnlyRows($offset, $limit)
    {
        $serviceTable = _DB_PREFIX_ . 'ntresellerclub_service';
        $profileTable = _DB_PREFIX_ . 'ntresellerclub_contact_profile';
        $providerCustomerTable = _DB_PREFIX_ . 'ntresellerclub_provider_customer';
        $customerTable = _DB_PREFIX_ . 'customer';
        $statuses = $this->reportableStatusSql();

        $sql = 'SELECT d.domain_name, d.id_customer, d.register_date, d.expiry_date, '
            . 'cp.company_name, cp.first_name, cp.last_name, cp.phone, cp.email AS contact_email, '
            . 'pc.provider_username, pc.email AS provider_email, '
            . 'c.firstname AS customer_firstname, c.lastname AS customer_lastname, c.email AS customer_email '
            . 'FROM ('
            . 'SELECT domain_name, MIN(id_customer) AS id_customer, MIN(NULLIF(start_date, "0000-00-00")) AS register_date, '
            . 'MAX(NULLIF(expiry_date, "0000-00-00")) AS expiry_date '
            . 'FROM `' . pSQL($serviceTable) . '` '
            . 'WHERE service_type IN ("domain", "tr_domain") AND domain_name IS NOT NULL AND domain_name<>"" AND status IN (' . $statuses . ') '
            . 'GROUP BY domain_name'
            . ') d '
            . 'LEFT JOIN ('
            . 'SELECT domain_name FROM `' . pSQL($serviceTable) . '` '
            . 'WHERE service_type="hosting" AND domain_name IS NOT NULL AND domain_name<>"" AND status IN (' . $statuses . ') '
            . 'GROUP BY domain_name'
            . ') h ON h.domain_name=d.domain_name '
            . 'LEFT JOIN `' . pSQL($profileTable) . '` cp ON cp.id_customer=d.id_customer AND cp.is_default=1 '
            . 'LEFT JOIN ('
            . 'SELECT id_customer, MAX(provider_username) AS provider_username, MAX(email) AS email '
            . 'FROM `' . pSQL($providerCustomerTable) . '` GROUP BY id_customer'
            . ') pc ON pc.id_customer=d.id_customer '
            . 'LEFT JOIN `' . pSQL($customerTable) . '` c ON c.id_customer=d.id_customer '
            . 'WHERE h.domain_name IS NULL '
            . 'ORDER BY d.domain_name ASC '
            . 'LIMIT ' . (int)$offset . ', ' . (int)$limit;

        $rows = Db::getInstance()->executeS($sql);
        return is_array($rows) ? $rows : array();
    }

    protected function ownerName(array $row)
    {
        $company = $this->value($row, 'company_name');
        if ($company !== '') {
            return $company;
        }

        $contactName = trim($this->value($row, 'first_name') . ' ' . $this->value($row, 'last_name'));
        if ($contactName !== '') {
            return $contactName;
        }

        $customerName = trim($this->value($row, 'customer_firstname') . ' ' . $this->value($row, 'customer_lastname'));
        if ($customerName !== '') {
            return $customerName;
        }

        return $this->value($row, 'provider_username');
    }

    protected function firstValue(array $row, array $fields)
    {
        foreach ($fields as $field) {
            $value = $this->value($row, $field);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function value(array $row, $field)
    {
        return isset($row[$field]) ? trim((string)$row[$field]) : '';
    }

    protected function batchLimit()
    {
        $limit = (int)Configuration::get('NTRC_BTK_EXPORT_BATCH_LIMIT');
        if ($limit <= 0) {
            $limit = 500;
        }
        if ($limit > 1000) {
            $limit = 1000;
        }

        return $limit;
    }

    protected function reportableStatusSql()
    {
        return '"active", "ready", "suspended"';
    }
}
