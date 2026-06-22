<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcProviderInterface.php';
require_once dirname(__DIR__) . '/NtRcApiClient.php';

class NtRcResellerClubProvider implements NtRcProviderInterface
{
    protected $client;

    public function __construct(NtRcApiClient $client)
    {
        $this->client = $client;
    }

    public function getCode()
    {
        return 'resellerclub';
    }

    public function checkAvailability($sld, array $tlds, $period = 1)
    {
        $response = $this->client->domainAvailability($sld, $tlds);
        if (!$response['success']) {
            return $response;
        }

        return array(
            'success' => true,
            'data' => $this->normalizeAvailability($response['data'])
        );
    }

    public function registerDomain($domainName, $years, array $contact, array $nameservers, array $extra = array())
    {
        $params = array_merge($extra, array(
            'domain-name' => $domainName,
            'years' => (int)$years,
        ));

        return $this->client->api('domains', 'register', $params, 'POST');
    }

    public function renewDomain($domainName, $years)
    {
        return $this->client->api('domains', 'renew', array(
            'domain-name' => $domainName,
            'years' => (int)$years,
        ), 'POST');
    }

    public function transferDomain($domainName, $authCode, $years = 1)
    {
        return $this->client->api('domains', 'transfer', array(
            'domain-name' => $domainName,
            'auth-code' => $authCode,
            'years' => (int)$years,
        ), 'POST');
    }

    public function getDetails($domainName)
    {
        return $this->client->api('domains', 'details', array(
            'domain-name' => $domainName,
        ), 'GET');
    }

    protected function normalizeAvailability($data)
    {
        $items = array();
        foreach ((array)$data as $domain => $row) {
            if (is_array($row) && isset($row['status'])) {
                $status = $row['status'];
            } else {
                $status = is_string($row) ? $row : 'unknown';
            }

            $items[$domain] = array(
                'status' => $status,
                'provider' => $this->getCode(),
                'raw' => $row,
            );
        }
        return $items;
    }
}
