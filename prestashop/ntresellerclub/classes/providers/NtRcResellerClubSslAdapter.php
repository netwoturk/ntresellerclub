<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__DIR__) . '/NtRcApiClient.php';

class NtRcResellerClubSslAdapter
{
    public function createSsl(array $payload)
    {
        $params = array(
            'domain-name' => $this->pick($payload, array('domain-name', 'domain_name', 'domain')),
            'months' => $this->pick($payload, array('months'), $this->monthsFromBillingCycle($this->pick($payload, array('billing_cycle'), 'yearly'))),
            'customer-id' => $this->pick($payload, array('customer-id', 'customer_id', 'provider_customer_id')),
            'plan-id' => $this->pick($payload, array('plan-id', 'plan_id', 'provider_product_id')),
            'invoice-option' => $this->pick($payload, array('invoice-option', 'invoice_option'), 'NoInvoice'),
        );
        $this->copyOptional($params, $payload, array('discount-amount'));

        $missing = $this->missingRequired($params, array('domain-name', 'months', 'customer-id', 'plan-id', 'invoice-option'));
        if ($missing) {
            return $this->missing('ssl/create', $missing, $payload);
        }

        return $this->normalizeActionResponse($this->client()->api('sslcert', 'add', $params, 'POST'));
    }

    public function renewSsl(array $payload)
    {
        return $this->endpointTodo('ssl/renew', $payload, 'ResellerClub SSL renew endpoint was found in legacy/WebPro API material as /api/sslcert/renew.xml, but an equivalent current ResellerClub help article with full parameter contract was not verified.');
    }

    public function reissueSsl(array $payload)
    {
        $params = array(
            'order-id' => $this->orderId($payload),
            'csr' => $this->pick($payload, array('csr')),
            'verification-method' => $this->normalizeVerificationMethod($this->pick($payload, array('verification-method', 'verification_method'), 'email')),
        );
        $this->copyOptional($params, $payload, array('verification-email', 'address', 'zip', 'country-of-incorporation', 'dba'));

        $missing = $this->missingRequired($params, array('order-id', 'csr'));
        if ($missing) {
            return $this->missing('ssl/reissue', $missing, $payload);
        }

        return $this->normalizeActionResponse($this->client()->api('sslcert', 'reissue', $params, 'POST'));
    }

    public function cancelSsl(array $payload)
    {
        $params = array('order-id' => $this->orderId($payload));
        $missing = $this->missingRequired($params, array('order-id'));
        if ($missing) {
            return $this->missing('ssl/cancel', $missing, $payload);
        }

        return $this->normalizeActionResponse($this->client()->api('sslcert', 'delete', $params, 'POST'));
    }

    public function getSslDetails(array $payload)
    {
        $params = array('order-id' => $this->orderId($payload));
        $missing = $this->missingRequired($params, array('order-id'));
        if ($missing) {
            return $this->missing('ssl/details', $missing, $payload);
        }

        return $this->normalizeActionResponse($this->client()->api('sslcert', 'details', $params, 'GET'));
    }

    public function downloadSsl(array $payload)
    {
        $params = array('order-id' => $this->orderId($payload));
        $missing = $this->missingRequired($params, array('order-id'));
        if ($missing) {
            return $this->missing('ssl/download', $missing, $payload);
        }

        return $this->normalizeActionResponse($this->client()->api('sslcert', 'get-cert-details', $params, 'GET'));
    }

    public function getValidationStatus(array $payload)
    {
        $params = array('order-id' => $this->orderId($payload));
        $missing = $this->missingRequired($params, array('order-id'));
        if ($missing) {
            return $this->missing('ssl/validation_status', $missing, $payload);
        }

        return $this->normalizeActionResponse($this->client()->api('sslcert', 'details', $params, 'GET'));
    }

    public function enrollSsl(array $payload)
    {
        $params = array(
            'order-id' => $this->orderId($payload),
            'csr' => $this->pick($payload, array('csr')),
            'verification-method' => $this->normalizeVerificationMethod($this->pick($payload, array('verification-method', 'verification_method'), 'email')),
        );
        $this->copyOptional($params, $payload, array('verification-email', 'address', 'zip', 'country-of-incorporation', 'dba'));

        $missing = $this->missingRequired($params, array('order-id', 'csr'));
        if ($missing) {
            return $this->missing('ssl/enroll', $missing, $payload);
        }

        return $this->normalizeActionResponse($this->client()->api('sslcert', 'enroll', $params, 'POST'));
    }

    protected function client()
    {
        return new NtRcApiClient(
            (bool)Configuration::get('NTRC_LIVE_MODE'),
            Configuration::get('NTRC_RESELLER_ID'),
            Configuration::get('NTRC_API_KEY'),
            Configuration::get('NTRC_LANG_PREF') ?: 'en'
        );
    }

    protected function normalizeActionResponse(array $response)
    {
        $response = $this->safeResponse($response);
        if (empty($response['success'])) {
            return $response;
        }

        $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : array();
        if (isset($data['status']) && strtoupper(trim((string)$data['status'])) === 'ERROR') {
            return array('success' => false, 'message' => isset($data['message']) ? $this->safeText($data['message']) : 'ResellerClub SSL action failed.', 'data' => $this->safeData($data));
        }
        if (isset($data['actionstatus'])) {
            $status = strtolower(trim((string)$data['actionstatus']));
            if (in_array($status, array('failed', 'failure', 'error', 'cancelled', 'canceled'), true)) {
                return array('success' => false, 'message' => isset($data['actionstatusdesc']) ? $this->safeText($data['actionstatusdesc']) : 'ResellerClub SSL action failed.', 'data' => $this->safeData($data));
            }
        }

        return $response;
    }

    protected function safeResponse(array $response)
    {
        unset($response['raw'], $response['last_url']);
        if (isset($response['data'])) {
            $response['data'] = $this->safeData($response['data']);
        }
        if (isset($response['error'])) {
            $response['error'] = $this->safeText($response['error']);
        }
        if (isset($response['message'])) {
            $response['message'] = $this->safeText($response['message']);
        }
        return $response;
    }

    protected function endpointTodo($action, array $payload, $reason = '')
    {
        return array(
            'success' => false,
            'message' => 'ResellerClub SSL endpoint dogrulanmadi. API cagrisi yapilmadi.',
            'todo' => $reason !== '' ? $reason : 'ResellerClub resmi SSL endpoint/resource/action ve zorunlu parametreleri dogrulaninca ' . $action . ' adapteri tamamlanacak.',
            'action' => $action,
            'safe_payload' => $this->safeData($payload),
        );
    }

    protected function missing($action, array $missing, array $payload)
    {
        return array(
            'success' => false,
            'message' => 'ResellerClub SSL action parametreleri eksik.',
            'action' => $action,
            'missing' => $missing,
            'safe_payload' => $this->safeData($payload),
        );
    }

    protected function missingRequired(array $params, array $required)
    {
        $missing = array();
        foreach ($required as $key) {
            if (!array_key_exists($key, $params) || $params[$key] === null || $params[$key] === '' || $params[$key] === array()) {
                $missing[] = $key;
            }
        }
        return $missing;
    }

    protected function copyOptional(array &$params, array $payload, array $keys)
    {
        foreach ($keys as $key) {
            $value = $this->pick($payload, array($key, str_replace('-', '_', $key)));
            if ($value !== null && $value !== '') {
                $params[$key] = $value;
            }
        }
    }

    protected function pick(array $payload, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($payload[$key]) && $payload[$key] !== '') {
                return $payload[$key];
            }
        }
        return $default;
    }

    protected function orderId(array $payload)
    {
        return $this->pick($payload, array('order-id', 'order_id', 'provider_order_id', 'provider_service_id'));
    }

    protected function monthsFromBillingCycle($billingCycle)
    {
        $billingCycle = strtolower(trim((string)$billingCycle));
        if ($billingCycle === 'biennial') {
            return 24;
        }
        if ($billingCycle === 'triennial') {
            return 36;
        }
        return 12;
    }

    protected function normalizeVerificationMethod($method)
    {
        $method = strtolower(trim((string)$method));
        return in_array($method, array('email', 'cname', 'http'), true) ? $method : 'email';
    }

    protected function safeData($data)
    {
        if (!is_array($data)) {
            return is_string($data) ? $this->safeText($data) : $data;
        }

        foreach (array(
            'raw', 'last_url', 'api-key', 'api_key', 'ApiKey', 'passwd', 'password', 'Password',
            'auth-code', 'auth_code', 'AuthCode', 'token', 'Token', 'credential', 'Credential',
            'csr', 'CSR', 'private_key', 'private-key', 'certificate', 'certificate_raw', 'cert_raw'
        ) as $key) {
            if (isset($data[$key])) {
                unset($data[$key]);
            }
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->safeData($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->safeText($value);
            }
        }

        return $data;
    }

    protected function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|csr|private_key|private-key|certificate|certificate_raw|cert_raw)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
