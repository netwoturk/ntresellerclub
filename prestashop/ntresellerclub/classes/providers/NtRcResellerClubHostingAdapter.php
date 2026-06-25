<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcResellerClubHostingAdapter
{
    public function createHosting(array $payload)
    {
        return $this->endpointTodo('hosting/create', $payload);
    }

    public function renewHosting(array $payload)
    {
        return $this->endpointTodo('hosting/renew', $payload);
    }

    public function suspendHosting(array $payload)
    {
        return $this->endpointTodo('hosting/suspend', $payload);
    }

    public function unsuspendHosting(array $payload)
    {
        return $this->endpointTodo('hosting/unsuspend', $payload);
    }

    public function getHostingDetails(array $payload)
    {
        return $this->endpointTodo('hosting/details', $payload);
    }

    protected function endpointTodo($action, array $payload)
    {
        return array(
            'success' => false,
            'message' => 'ResellerClub hosting endpoint dogrulanmadi. API cagrisi yapilmadi.',
            'todo' => 'ResellerClub resmi hosting endpoint/resource/action ve zorunlu parametreleri dogrulaninca ' . $action . ' adapteri tamamlanacak.',
            'action' => $action,
            'safe_payload' => $this->safeData($payload),
        );
    }

    protected function safeData($data)
    {
        if (!is_array($data)) {
            return is_string($data) ? $this->safeText($data) : $data;
        }

        foreach (array('raw', 'last_url', 'api-key', 'api_key', 'ApiKey', 'passwd', 'password', 'Password', 'auth-code', 'auth_code', 'AuthCode', 'token', 'Token', 'credential', 'Credential') as $key) {
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
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential)=([^&\\s]+)/i', '$1=***', (string)$text);
    }
}
