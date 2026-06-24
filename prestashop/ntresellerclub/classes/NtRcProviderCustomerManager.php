<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';
require_once __DIR__ . '/NtRcApiContractGuard.php';
require_once __DIR__ . '/NtRcContactProfileManager.php';
require_once __DIR__ . '/NtRcOperationQueueManager.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcProviderCustomerManager
{
    public static function ensure($idCustomer, $providerCode, $domainName = null)
    {
        self::ensureSchema();

        $providerCode = strtolower(trim((string)$providerCode));
        $customer = new Customer((int)$idCustomer);
        if (!Validate::isLoadedObject($customer)) {
            return array('success' => false, 'message' => 'PrestaShop musterisi bulunamadi.');
        }

        $contract = NtRcApiContractGuard::validate($providerCode, 'customer', 'create', array('id_customer' => (int)$idCustomer));
        if (empty($contract['success'])) {
            return $contract;
        }

        $row = self::getMapping($idCustomer, $providerCode);
        if ($row && !empty($row['provider_customer_id'])) {
            return array('success' => true, 'provider_code' => $providerCode, 'provider_customer_id' => $row['provider_customer_id'], 'source' => 'existing');
        }

        $profileResult = NtRcContactProfileManager::ensureDefault((int)$idCustomer);
        if (empty($profileResult['success'])) {
            return $profileResult;
        }

        $profile = $profileResult['profile'];
        if ($providerCode === 'domainnameapi' && $domainName && NtRcApiContractGuard::isDomainNameApiTrDomain($domainName)) {
            $validation = NtRcContactProfileManager::validateForTrDomain($profile);
            if (empty($validation['success'])) {
                return $validation;
            }
        }

        if (!$row) {
            $inserted = Db::getInstance()->insert('ntresellerclub_provider_customer', array(
                'id_customer' => (int)$idCustomer,
                'provider_code' => pSQL($providerCode),
                'provider_customer_id' => null,
                'provider_username' => null,
                'email' => pSQL($customer->email),
                'status' => pSQL('pending'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ));
            if (!$inserted) {
                NtRcLog::add('error', 'provider_customer', 'Mapping insert failed customer=' . (int)$idCustomer . ' provider=' . $providerCode);
                return array('success' => false, 'message' => 'Provider customer mapping olusturulamadi.');
            }
            $row = self::getMapping($idCustomer, $providerCode);
        }

        $queueResult = self::enqueueCustomerCreate($customer, $providerCode, $profile, $domainName);
        if (empty($queueResult['success'])) {
            Db::getInstance()->update('ntresellerclub_provider_customer', array(
                'status' => pSQL('error'),
                'updated_at' => date('Y-m-d H:i:s'),
            ), 'id_ntresellerclub_provider_customer=' . (int)$row['id_ntresellerclub_provider_customer']);
            return $queueResult;
        }

        Db::getInstance()->update('ntresellerclub_provider_customer', array(
            'email' => pSQL($customer->email),
            'status' => pSQL('pending'),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_provider_customer=' . (int)$row['id_ntresellerclub_provider_customer']);

        return array(
            'success' => true,
            'provider_code' => $providerCode,
            'provider_customer_id' => null,
            'source' => 'pending',
            'queue_id' => isset($queueResult['queue_id']) ? (int)$queueResult['queue_id'] : 0,
        );
    }

    public static function getMapping($idCustomer, $providerCode)
    {
        self::ensureSchema();

        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider_customer` WHERE id_customer=' . (int)$idCustomer . ' AND provider_code="' . pSQL($providerCode) . '"'
        );
    }

    public static function markActive($idCustomer, $providerCode, $providerCustomerId, array $rawData = array())
    {
        self::ensureSchema();

        return Db::getInstance()->update('ntresellerclub_provider_customer', array(
            'provider_customer_id' => pSQL($providerCustomerId),
            'status' => pSQL('active'),
            'raw_data' => pSQL(json_encode($rawData)),
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_customer=' . (int)$idCustomer . ' AND provider_code="' . pSQL($providerCode) . '"');
    }

    protected static function enqueueCustomerCreate(Customer $customer, $providerCode, array $profile, $domainName = null)
    {
        if (self::hasOpenCustomerCreateQueue((int)$customer->id, $providerCode)) {
            return array('success' => true, 'queue_id' => 0, 'source' => 'existing_queue');
        }

        $payload = array(
            'id_customer' => (int)$customer->id,
            'email' => $customer->email,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'domain_name' => $domainName,
            'contact_profile' => NtRcContactProfileManager::toProviderPayload($profile),
        );

        return NtRcOperationQueueManager::enqueue(
            $providerCode,
            'customer',
            'create',
            $payload,
            null,
            (int)$customer->id,
            null,
            3,
            2
        );
    }

    protected static function hasOpenCustomerCreateQueue($idCustomer, $providerCode)
    {
        $payloadLike = '%"id_customer":' . (int)$idCustomer . '%';
        $value = Db::getInstance()->getValue(
            'SELECT id_ntresellerclub_operation_queue FROM `' . _DB_PREFIX_ . 'ntresellerclub_operation_queue` '
            . 'WHERE id_customer=' . (int)$idCustomer . ' '
            . 'AND provider_code="' . pSQL($providerCode) . '" '
            . 'AND service_type="customer" '
            . 'AND action="create" '
            . 'AND status IN ("pending", "processing") '
            . 'AND payload_json LIKE "' . pSQL($payloadLike) . '"'
        );

        return (bool)$value;
    }

    protected static function ensureSchema()
    {
        NtRcInstaller::ensureProviderCustomerSchema();
        self::addColumnIfMissing('provider_username', 'VARCHAR(255) DEFAULT NULL AFTER `provider_customer_id`');
        self::addColumnIfMissing('raw_data', 'MEDIUMTEXT DEFAULT NULL AFTER `status`');
    }

    protected static function addColumnIfMissing($column, $definition)
    {
        $table = _DB_PREFIX_ . 'ntresellerclub_provider_customer';
        $exists = Db::getInstance()->getValue('SHOW COLUMNS FROM `' . pSQL($table) . '` LIKE "' . pSQL($column) . '"');
        if ($exists) {
            return true;
        }

        return Db::getInstance()->execute('ALTER TABLE `' . pSQL($table) . '` ADD `' . pSQL($column) . '` ' . $definition);
    }
}
