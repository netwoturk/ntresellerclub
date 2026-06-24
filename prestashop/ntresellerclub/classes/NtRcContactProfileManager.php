<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcInstaller.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcContactProfileManager
{
    public static function ensureDefault($idCustomer, array $data = array())
    {
        self::ensureSchema();

        $idCustomer = (int)$idCustomer;
        if ($idCustomer <= 0) {
            return array('success' => false, 'message' => 'Musteri ID zorunludur.');
        }

        $existing = self::getDefault($idCustomer);
        if ($existing) {
            if (!empty($data)) {
                return self::update((int)$existing['id_ntresellerclub_contact_profile'], $data);
            }
            return array('success' => true, 'profile' => $existing, 'source' => 'existing');
        }

        if (empty($data)) {
            $data = self::buildFromCustomer($idCustomer);
        }

        return self::create($idCustomer, $data, true);
    }

    public static function create($idCustomer, array $data, $isDefault = false)
    {
        self::ensureSchema();

        $idCustomer = (int)$idCustomer;
        $row = self::normalizeData($data);
        $row['id_customer'] = $idCustomer;
        $row['is_default'] = $isDefault ? 1 : 0;
        $row['created_at'] = date('Y-m-d H:i:s');
        $row['updated_at'] = date('Y-m-d H:i:s');

        if ($row['is_default']) {
            self::clearDefault($idCustomer);
        }

        $ok = Db::getInstance()->insert('ntresellerclub_contact_profile', self::sqlData($row));
        if (!$ok) {
            NtRcLog::add('error', 'contact_profile', 'Contact profile insert failed customer=' . $idCustomer);
            return array('success' => false, 'message' => 'Contact profile olusturulamadi.');
        }

        $idProfile = (int)Db::getInstance()->Insert_ID();
        return array('success' => true, 'profile_id' => $idProfile, 'profile' => self::get($idProfile), 'source' => 'created');
    }

    public static function update($idProfile, array $data)
    {
        self::ensureSchema();

        $idProfile = (int)$idProfile;
        $existing = self::get($idProfile);
        if (!$existing) {
            return array('success' => false, 'message' => 'Contact profile bulunamadi.');
        }

        $row = self::normalizeData(array_merge($existing, $data));
        $row['updated_at'] = date('Y-m-d H:i:s');

        $ok = Db::getInstance()->update('ntresellerclub_contact_profile', self::sqlData($row), 'id_ntresellerclub_contact_profile=' . $idProfile);
        return array('success' => (bool)$ok, 'profile_id' => $idProfile, 'profile' => self::get($idProfile));
    }

    public static function get($idProfile)
    {
        self::ensureSchema();

        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_contact_profile` WHERE id_ntresellerclub_contact_profile=' . (int)$idProfile
        );
    }

    public static function getDefault($idCustomer)
    {
        self::ensureSchema();

        return Db::getInstance()->getRow(
            'SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_contact_profile` WHERE id_customer=' . (int)$idCustomer . ' ORDER BY is_default DESC, id_ntresellerclub_contact_profile DESC'
        );
    }

    public static function setDefault($idProfile)
    {
        self::ensureSchema();

        $profile = self::get($idProfile);
        if (!$profile) {
            return array('success' => false, 'message' => 'Contact profile bulunamadi.');
        }

        self::clearDefault((int)$profile['id_customer']);
        $ok = Db::getInstance()->update('ntresellerclub_contact_profile', array(
            'is_default' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_ntresellerclub_contact_profile=' . (int)$idProfile);

        return array('success' => (bool)$ok, 'profile_id' => (int)$idProfile);
    }

    public static function validate($profile)
    {
        $profile = is_array($profile) ? $profile : array();
        $type = self::normalizeProfileType(isset($profile['profile_type']) ? $profile['profile_type'] : 'personal');
        $missing = array();

        foreach (array('first_name', 'last_name', 'email', 'phone') as $field) {
            if (self::emptyField($profile, $field)) {
                $missing[] = $field;
            }
        }

        if ($type === 'company') {
            foreach (array('company_name', 'tax_number', 'tax_office') as $field) {
                if (self::emptyField($profile, $field)) {
                    $missing[] = $field;
                }
            }
        }

        if ($type === 'personal' && self::emptyField($profile, 'tc_number')) {
            $missing[] = 'tc_number';
        }

        if (!empty($missing)) {
            return array('success' => false, 'message' => 'Contact profile eksik alanlar var.', 'missing' => $missing);
        }

        return array('success' => true);
    }

    public static function validateForTrDomain($profile)
    {
        $profile = is_array($profile) ? $profile : array();
        $validation = self::validate($profile);
        $missing = empty($validation['missing']) ? array() : $validation['missing'];

        foreach (array('address', 'city', 'country_iso', 'postcode') as $field) {
            if (self::emptyField($profile, $field)) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            return array(
                'success' => false,
                'message' => 'TR domain icin zorunlu contact alanlari eksik.',
                'missing' => array_values(array_unique($missing)),
            );
        }

        return array('success' => true);
    }

    public static function toProviderPayload(array $profile)
    {
        $profile = self::normalizeData($profile);
        return array(
            'profile_type' => $profile['profile_type'],
            'company_name' => $profile['company_name'],
            'first_name' => $profile['first_name'],
            'last_name' => $profile['last_name'],
            'tax_number' => $profile['tax_number'],
            'tax_office' => $profile['tax_office'],
            'tc_number' => $profile['tc_number'],
            'address' => $profile['address'],
            'city' => $profile['city'],
            'state' => $profile['state'],
            'country_iso' => $profile['country_iso'],
            'postcode' => $profile['postcode'],
            'phone' => $profile['phone'],
            'email' => $profile['email'],
        );
    }

    public static function buildFromCustomer($idCustomer)
    {
        $customer = new Customer((int)$idCustomer);
        if (!Validate::isLoadedObject($customer)) {
            return array();
        }

        $address = null;
        if (class_exists('Address') && method_exists('Address', 'getFirstCustomerAddressId')) {
            $idAddress = (int)Address::getFirstCustomerAddressId((int)$idCustomer);
            if ($idAddress > 0) {
                $address = new Address($idAddress);
            }
        }

        $countryIso = '';
        if ($address && Validate::isLoadedObject($address) && !empty($address->id_country)) {
            $countryIso = Country::getIsoById((int)$address->id_country);
        }

        return array(
            'profile_type' => !empty($address->company) ? 'company' : 'personal',
            'company_name' => $address && Validate::isLoadedObject($address) ? $address->company : '',
            'first_name' => $address && Validate::isLoadedObject($address) ? $address->firstname : $customer->firstname,
            'last_name' => $address && Validate::isLoadedObject($address) ? $address->lastname : $customer->lastname,
            'address' => $address && Validate::isLoadedObject($address) ? trim($address->address1 . ' ' . $address->address2) : '',
            'city' => $address && Validate::isLoadedObject($address) ? $address->city : '',
            'state' => '',
            'country_iso' => $countryIso,
            'postcode' => $address && Validate::isLoadedObject($address) ? $address->postcode : '',
            'phone' => $address && Validate::isLoadedObject($address) ? ($address->phone_mobile ? $address->phone_mobile : $address->phone) : '',
            'email' => $customer->email,
        );
    }

    protected static function clearDefault($idCustomer)
    {
        return Db::getInstance()->update('ntresellerclub_contact_profile', array(
            'is_default' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ), 'id_customer=' . (int)$idCustomer);
    }

    protected static function normalizeData(array $data)
    {
        $fields = array(
            'profile_type', 'company_name', 'first_name', 'last_name', 'tax_number', 'tax_office', 'tc_number',
            'address', 'city', 'state', 'country_iso', 'postcode', 'phone', 'email'
        );
        $row = array();
        foreach ($fields as $field) {
            $row[$field] = isset($data[$field]) ? trim((string)$data[$field]) : '';
        }
        $row['profile_type'] = self::normalizeProfileType($row['profile_type']);
        $row['country_iso'] = strtoupper(substr($row['country_iso'], 0, 5));
        return $row;
    }

    protected static function normalizeProfileType($profileType)
    {
        $profileType = strtolower(trim((string)$profileType));
        return $profileType === 'company' ? 'company' : 'personal';
    }

    protected static function sqlData(array $row)
    {
        $data = array();
        foreach ($row as $field => $value) {
            if (in_array($field, array('id_customer', 'is_default'))) {
                $data[$field] = (int)$value;
                continue;
            }
            $data[$field] = $value === '' ? null : pSQL($value);
        }
        return $data;
    }

    protected static function emptyField(array $profile, $field)
    {
        return !isset($profile[$field]) || trim((string)$profile[$field]) === '';
    }

    protected static function ensureSchema()
    {
        return NtRcInstaller::ensureContactProfileSchema();
    }
}
