<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcContactProfileManager.php';

class NtRcContactManager
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function ensureContact($idCustomer)
    {
        $profile = NtRcContactProfileManager::ensureDefault((int)$idCustomer);
        if (empty($profile['success'])) {
            return $profile;
        }

        return array(
            'success' => true,
            'contact_id' => isset($profile['profile_id']) ? (int)$profile['profile_id'] : (int)$profile['profile']['id_ntresellerclub_contact_profile'],
            'profile' => $profile['profile'],
            'source' => isset($profile['source']) ? $profile['source'] : 'profile',
        );
    }
}
