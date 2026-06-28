<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/admin/NtRcAdminBaseController.php';
require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcApiClient.php';
require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcInstaller.php';
require_once _PS_MODULE_DIR_ . 'ntresellerclub/classes/providers/NtRcProviderFactory.php';

class AdminNtRcSettingsController extends NtRcAdminBaseController
{
    protected $ntRcSection = 'settings';

    const CFG_RC_ENABLED = 'NTRC_FEATURE_RESELLERCLUB';
    const CFG_RC_LIVE_MODE = 'NTRC_LIVE_MODE';
    const CFG_RC_AUTH_USERID = 'NTRC_RESELLER_ID';
    const CFG_RC_API_KEY = 'NTRC_API_KEY';
    const CFG_RC_RESELLER_ID = 'NTRC_RC_RESELLER_ID';
    const CFG_RC_LANG_PREF = 'NTRC_LANG_PREF';
    const CFG_DNA_ENABLED = 'NTRC_FEATURE_DOMAINNAMEAPI';
    const CFG_DNA_USERNAME = 'NTRC_DNA_USERNAME';
    const CFG_DNA_PASSWORD = 'NTRC_DNA_PASSWORD';
    const CFG_DNA_TEST_MODE = 'NTRC_DNA_TEST_MODE';

    public function postProcess()
    {
        parent::postProcess();

        if (Tools::isSubmit('submitNtRcApiSettings')) {
            $this->handleSaveSettings();
        }

        if (Tools::isSubmit('testNtRcResellerClub')) {
            $this->handleConnectionTest('resellerclub');
        }

        if (Tools::isSubmit('testNtRcDomainNameApi')) {
            $this->handleConnectionTest('domainnameapi');
        }
    }

    protected function renderSectionContent()
    {
        if (!$this->hasPermission('view')) {
            return NtRcAdminWidget::alert('danger', 'You do not have permission to view this page.');
        }

        $html = '<section class="' . NtRcAdminThemeHelper::panelClass() . '">';
        $html .= '<h3>API Settings</h3>';
        $html .= NtRcAdminWidget::alert('info', 'API credentials are stored in PrestaShop Configuration. Provider APIs are called only when a connection test button is submitted.');
        $html .= $this->renderSettingsForm();
        $html .= '</section>';
        $html .= $this->renderTestStatusPanel();

        return $html;
    }

    protected function handleSaveSettings()
    {
        if (!$this->hasPermission('edit') || !$this->isValidCsrfToken()) {
            $this->flash('error', 'Invalid token or permission denied.');
            return;
        }

        Configuration::updateValue(self::CFG_RC_ENABLED, $this->boolValue(self::CFG_RC_ENABLED));
        Configuration::updateValue(self::CFG_RC_LIVE_MODE, $this->modeValue('nt_rc_mode') === 'live' ? 1 : 0);
        Configuration::updateValue(self::CFG_RC_AUTH_USERID, $this->cleanValue(self::CFG_RC_AUTH_USERID));
        Configuration::updateValue(self::CFG_RC_RESELLER_ID, $this->cleanValue(self::CFG_RC_RESELLER_ID));
        Configuration::updateValue(self::CFG_RC_LANG_PREF, $this->cleanValue(self::CFG_RC_LANG_PREF, 'en'));

        $apiKey = $this->cleanValue(self::CFG_RC_API_KEY);
        if ($apiKey !== '' && !$this->isMaskedValue($apiKey)) {
            Configuration::updateValue(self::CFG_RC_API_KEY, $apiKey);
        }

        Configuration::updateValue(self::CFG_DNA_ENABLED, $this->boolValue(self::CFG_DNA_ENABLED));
        Configuration::updateValue(self::CFG_DNA_TEST_MODE, $this->modeValue('nt_dna_mode') === 'sandbox' ? 1 : 0);
        Configuration::updateValue(self::CFG_DNA_USERNAME, $this->cleanValue(self::CFG_DNA_USERNAME));

        $password = $this->cleanValue(self::CFG_DNA_PASSWORD);
        if ($password !== '' && !$this->isMaskedValue($password)) {
            Configuration::updateValue(self::CFG_DNA_PASSWORD, $password);
        }

        $this->flash('success', 'API settings saved.');
    }

    protected function handleConnectionTest($providerCode)
    {
        if (!$this->hasPermission('edit') || !$this->isValidCsrfToken()) {
            $this->flash('error', 'Invalid token or permission denied.');
            return;
        }

        $result = $providerCode === 'resellerclub'
            ? $this->testResellerClub()
            : $this->testDomainNameApi();

        $this->recordProviderHealth($providerCode, $result);

        if (!empty($result['success'])) {
            $this->flash('success', $result['message']);
            return;
        }

        $this->flash('error', $result['message']);
    }

    protected function testResellerClub()
    {
        $authUserId = trim((string)Configuration::get(self::CFG_RC_AUTH_USERID));
        $apiKey = trim((string)Configuration::get(self::CFG_RC_API_KEY));

        if ($authUserId === '' || $apiKey === '') {
            return array('success' => false, 'message' => 'ResellerClub credentials are incomplete.');
        }

        $provider = NtRcProviderFactory::make('resellerclub', false);
        if (!$provider) {
            return array('success' => false, 'message' => 'ResellerClub provider could not be created.');
        }

        $response = $provider->searchCustomer('ntresellerclub-connection-test@example.invalid');
        if (!empty($response['success'])) {
            return array('success' => true, 'message' => 'ResellerClub connection test succeeded.');
        }

        return array('success' => false, 'message' => $this->responseError($response, 'ResellerClub connection test failed.'));
    }

    protected function testDomainNameApi()
    {
        $username = trim((string)Configuration::get(self::CFG_DNA_USERNAME));
        $password = trim((string)Configuration::get(self::CFG_DNA_PASSWORD));

        if ($username === '' || $password === '') {
            return array('success' => false, 'message' => 'DomainNameAPI credentials are incomplete.');
        }

        $provider = NtRcProviderFactory::make('domainnameapi', false);
        if (!$provider) {
            return array('success' => false, 'message' => 'DomainNameAPI provider could not be created.');
        }

        $response = $provider->getTrPrices();
        if (!empty($response['success'])) {
            return array('success' => true, 'message' => 'DomainNameAPI connection test succeeded.');
        }

        return array('success' => false, 'message' => $this->responseError($response, 'DomainNameAPI connection test failed.'));
    }

    protected function recordProviderHealth($providerCode, array $result)
    {
        NtRcInstaller::ensureMonitoringSchema();

        $status = !empty($result['success']) ? 'success' : 'failed';
        return Db::getInstance()->insert('ntresellerclub_provider_health', array(
            'provider_code' => pSQL($providerCode),
            'status' => pSQL($status),
            'is_enabled' => $providerCode === 'resellerclub' ? (int)Configuration::get(self::CFG_RC_ENABLED) : (int)Configuration::get(self::CFG_DNA_ENABLED),
            'is_licensed' => $providerCode === 'resellerclub' ? (int)Configuration::get(self::CFG_RC_ENABLED) : (int)Configuration::get(self::CFG_DNA_ENABLED),
            'queue_pending' => 0,
            'queue_failed' => 0,
            'last_error' => !empty($result['success']) ? null : pSQL($this->safeText($result['message'])),
            'response_time_ms' => 0,
            'checked_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }

    protected function renderSettingsForm()
    {
        $action = NtRcAdminThemeHelper::esc($this->context->link->getAdminLink($this->controller_name));
        $html = '<form method="post" action="' . $action . '" class="form-horizontal ntrc-settings-form">';
        $html .= '<input type="hidden" name="token" value="' . NtRcAdminThemeHelper::esc($this->currentAdminToken()) . '">';
        $html .= '<div class="row">';
        $html .= '<div class="col-lg-6">' . $this->renderResellerClubFields() . '</div>';
        $html .= '<div class="col-lg-6">' . $this->renderDomainNameApiFields() . '</div>';
        $html .= '</div>';
        $html .= '<div class="panel-footer">';
        $html .= '<button type="submit" name="submitNtRcApiSettings" class="btn btn-primary">Save API Settings</button> ';
        $html .= '<button type="submit" name="testNtRcResellerClub" class="btn btn-default">ResellerClub Baglantiyi Test Et</button> ';
        $html .= '<button type="submit" name="testNtRcDomainNameApi" class="btn btn-default">DomainNameAPI Baglantiyi Test Et</button>';
        $html .= '</div></form>';

        return $html;
    }

    protected function renderResellerClubFields()
    {
        $mode = Configuration::get(self::CFG_RC_LIVE_MODE) ? 'live' : 'sandbox';
        $html = '<fieldset><legend>ResellerClub</legend>';
        $html .= $this->renderSwitch(self::CFG_RC_ENABLED, 'Enabled', Configuration::get(self::CFG_RC_ENABLED));
        $html .= $this->renderModeSelect('nt_rc_mode', 'Mode', $mode);
        $html .= $this->renderTextInput(self::CFG_RC_AUTH_USERID, 'Auth User ID', Configuration::get(self::CFG_RC_AUTH_USERID));
        $html .= $this->renderSecretInput(self::CFG_RC_API_KEY, 'API Key', Configuration::get(self::CFG_RC_API_KEY));
        $html .= $this->renderTextInput(self::CFG_RC_RESELLER_ID, 'Reseller ID (optional)', Configuration::get(self::CFG_RC_RESELLER_ID));
        $html .= $this->renderTextInput(self::CFG_RC_LANG_PREF, 'Language', Configuration::get(self::CFG_RC_LANG_PREF) ?: 'en');
        return $html . '</fieldset>';
    }

    protected function renderDomainNameApiFields()
    {
        $mode = Configuration::get(self::CFG_DNA_TEST_MODE) ? 'sandbox' : 'live';
        $html = '<fieldset><legend>DomainNameAPI</legend>';
        $html .= $this->renderSwitch(self::CFG_DNA_ENABLED, 'Enabled', Configuration::get(self::CFG_DNA_ENABLED));
        $html .= $this->renderModeSelect('nt_dna_mode', 'Mode', $mode);
        $html .= $this->renderTextInput(self::CFG_DNA_USERNAME, 'Username', Configuration::get(self::CFG_DNA_USERNAME));
        $html .= $this->renderSecretInput(self::CFG_DNA_PASSWORD, 'Password / API Credential', Configuration::get(self::CFG_DNA_PASSWORD));
        return $html . '</fieldset>';
    }

    protected function renderTestStatusPanel()
    {
        $rows = array();
        foreach (array('resellerclub' => 'ResellerClub', 'domainnameapi' => 'DomainNameAPI') as $code => $label) {
            $row = $this->latestHealth($code);
            $rows[] = array(
                $label,
                isset($row['status']) ? $row['status'] : 'not_checked',
                !empty($row['last_error']) ? $row['last_error'] : '-',
                !empty($row['checked_at']) ? $row['checked_at'] : '-',
            );
        }

        return '<section class="' . NtRcAdminThemeHelper::panelClass() . '"><h3>Connection Test Results</h3>'
            . NtRcAdminWidget::table(array('Provider', 'Result', 'Last error', 'Checked at'), $rows)
            . '</section>';
    }

    protected function latestHealth($providerCode)
    {
        NtRcInstaller::ensureMonitoringSchema();

        $row = Db::getInstance()->getRow(
            'SELECT status, last_error, checked_at FROM `' . _DB_PREFIX_ . 'ntresellerclub_provider_health` '
            . 'WHERE provider_code="' . pSQL($providerCode) . '" '
            . 'ORDER BY checked_at DESC, id_ntresellerclub_provider_health DESC'
        );

        if (!is_array($row)) {
            return array();
        }

        $row['last_error'] = isset($row['last_error']) ? $this->safeText($row['last_error']) : '';
        return $row;
    }

    protected function renderSwitch($name, $label, $checked)
    {
        $html = '<div class="form-group"><label class="control-label col-lg-4">' . NtRcAdminThemeHelper::esc($label) . '</label><div class="col-lg-8">';
        $html .= '<label class="radio-inline"><input type="radio" name="' . NtRcAdminThemeHelper::esc($name) . '" value="1"' . ((int)$checked ? ' checked="checked"' : '') . '> Active</label> ';
        $html .= '<label class="radio-inline"><input type="radio" name="' . NtRcAdminThemeHelper::esc($name) . '" value="0"' . (!(int)$checked ? ' checked="checked"' : '') . '> Passive</label>';
        return $html . '</div></div>';
    }

    protected function renderModeSelect($name, $label, $selected)
    {
        $html = '<div class="form-group"><label class="control-label col-lg-4">' . NtRcAdminThemeHelper::esc($label) . '</label><div class="col-lg-8">';
        $html .= '<select class="form-control" name="' . NtRcAdminThemeHelper::esc($name) . '">';
        foreach (array('sandbox' => 'Sandbox', 'live' => 'Live') as $value => $title) {
            $html .= '<option value="' . NtRcAdminThemeHelper::esc($value) . '"' . ($selected === $value ? ' selected="selected"' : '') . '>' . NtRcAdminThemeHelper::esc($title) . '</option>';
        }
        return $html . '</select></div></div>';
    }

    protected function renderTextInput($name, $label, $value)
    {
        return '<div class="form-group"><label class="control-label col-lg-4">' . NtRcAdminThemeHelper::esc($label) . '</label>'
            . '<div class="col-lg-8"><input class="form-control" type="text" name="' . NtRcAdminThemeHelper::esc($name) . '" value="' . NtRcAdminThemeHelper::esc($value) . '"></div></div>';
    }

    protected function renderSecretInput($name, $label, $value)
    {
        $masked = $this->maskSecret($value);
        return '<div class="form-group"><label class="control-label col-lg-4">' . NtRcAdminThemeHelper::esc($label) . '</label>'
            . '<div class="col-lg-8"><input class="form-control" type="password" name="' . NtRcAdminThemeHelper::esc($name) . '" value="" placeholder="' . NtRcAdminThemeHelper::esc($masked) . '">'
            . '<p class="help-block">Leave blank to keep the current masked value.</p></div></div>';
    }

    protected function boolValue($name)
    {
        return Tools::getValue($name) ? 1 : 0;
    }

    protected function modeValue($name)
    {
        $mode = strtolower(trim((string)Tools::getValue($name)));
        return in_array($mode, array('sandbox', 'live'), true) ? $mode : 'sandbox';
    }

    protected function cleanValue($name, $default = '')
    {
        $value = trim((string)Tools::getValue($name, $default));
        return $value === '' ? $default : $value;
    }

    protected function isMaskedValue($value)
    {
        return preg_match('/^\*+$/', trim((string)$value));
    }

    protected function maskSecret($value)
    {
        $value = (string)$value;
        if ($value === '') {
            return '';
        }

        return str_repeat('*', min(12, max(8, strlen($value))));
    }

    protected function responseError(array $response, $fallback)
    {
        foreach (array('message', 'error') as $key) {
            if (!empty($response[$key])) {
                return $this->safeText($response[$key]);
            }
        }

        return $fallback;
    }

    protected function safeText($text)
    {
        $text = (string)$text;
        $text = preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|secret)=([^&\s]+)/i', '$1=***', $text);
        return preg_replace('/("(?:api-key|api_key|auth-code|auth_code|passwd|password|token|credential|secret)"\s*:\s*)"[^"]*"/i', '$1"***"', $text);
    }
}
