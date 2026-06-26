<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcResellerClubSslAdapter
{
    public function createSsl(array $payload)
    {
        return $this->endpointTodo('ssl/create', $payload);
    }

    public function renewSsl(array $payload)
    {
        return $this->endpointTodo('ssl/renew', $payload);
    }

    public function reissueSsl(array $payload)
    {
        return $this->endpointTodo('ssl/reissue', $payload);
    }

    public function cancelSsl(array $payload)
    {
        return $this->endpointTodo('ssl/cancel', $payload);
    }

    public function getSslDetails(array $payload)
    {
        return $this->endpointTodo('ssl/details', $payload);
    }

    public function downloadSsl(array $payload)
    {
        return $this->endpointTodo('ssl/download', $payload);
    }

    protected function endpointTodo($action, array $payload)
    {
        return array(
            'success' => false,
            'message' => 'ResellerClub SSL endpoint dogrulanmadi. API cagrisi yapilmadi.',
            'todo' => 'ResellerClub resmi SSL endpoint/resource/action ve zorunlu parametreleri dogrulaninca ' . $action . ' adapteri tamamlanacak.',
            'action' => $action,
            'safe_payload' => $this->safeData($payload),
        );
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
