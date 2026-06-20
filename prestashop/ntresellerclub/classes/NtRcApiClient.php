<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcApiClient
{
    protected $liveMode;
    protected $resellerId;
    protected $secret;
    protected $langPref;
    protected $lastUrl;

    public function __construct($liveMode, $resellerId, $secret, $langPref = 'en')
    {
        $this->liveMode = (bool)$liveMode;
        $this->resellerId = $resellerId;
        $this->secret = $secret;
        $this->langPref = $langPref ?: 'en';
    }

    public function domainAvailability($domainName, array $tlds)
    {
        return $this->request('https://domaincheck.httpapi.com/api/domains/available.json', array(
            'domain-name' => $domainName,
            'tlds' => array_values($tlds),
        ), 'GET');
    }

    public function api($resource, $action, array $params = array(), $method = 'GET')
    {
        $base = $this->liveMode ? 'https://httpapi.com/api/' : 'https://test.httpapi.com/api/';
        $url = rtrim($base, '/') . '/' . trim($resource, '/') . '/' . trim($action, '/') . '.json';
        return $this->request($url, $params, $method);
    }

    public function request($url, array $params = array(), $method = 'GET')
    {
        $query = array_merge(array(
            'auth-userid' => $this->resellerId,
            'api-key' => $this->secret,
            'lang-pref' => $this->langPref,
        ), $params);

        $method = strtoupper($method);
        $ch = curl_init();

        if ($method === 'GET') {
            $url .= '?' . $this->buildQuery($query);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->buildQuery($query));
        }

        $this->lastUrl = $url;

        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_USERAGENT => 'NetwoTurk-PrestaShop-RC/0.1',
        ));

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return $this->normalize(false, null, 'cURL error ' . $errno . ': ' . $error, $http, $raw);
        }

        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->normalize($http >= 200 && $http < 300, $raw, $http >= 400 ? 'HTTP error ' . $http : null, $http, $raw);
        }

        if ($http < 200 || $http >= 300) {
            $message = isset($data['message']) ? $data['message'] : 'HTTP error ' . $http;
            return $this->normalize(false, $data, $message, $http, $raw);
        }

        return $this->normalize(true, $data, null, $http, $raw);
    }

    protected function buildQuery(array $params)
    {
        $parts = array();
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = rawurlencode($key) . '=' . rawurlencode($item);
                }
            } else {
                $parts[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
        }
        return implode('&', $parts);
    }

    protected function normalize($success, $data, $error, $httpCode, $raw)
    {
        return array(
            'success' => (bool)$success,
            'data' => $data,
            'error' => $error,
            'http_code' => $httpCode,
            'raw' => $raw,
            'last_url' => $this->lastUrl,
        );
    }
}
