<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcDomainSearchService.php';

class NtRcDomainSearchEngine
{
    public function search($query, array $tlds = array())
    {
        $query = $this->cleanQuery($query);
        if (!$query) {
            return array('success' => false, 'message' => 'Invalid query', 'items' => array(), 'errors' => array());
        }

        if (!$tlds) {
            $tlds = $this->defaultTlds();
        }

        $sld = $this->extractSld($query);
        $items = array();
        $errors = array();
        $service = new NtRcDomainSearchService();

        foreach ($tlds as $tld) {
            $tld = strtolower(ltrim(trim($tld), '.'));
            if (!$tld) {
                continue;
            }

            $domainName = $sld . '.' . $tld;
            $response = $service->search($domainName);
            $result = isset($response['results'][0]) ? $response['results'][0] : null;
            if (!$result || !empty($result['error'])) {
                $errors[$domainName] = $result && !empty($result['error']) ? $result['error'] : 'search_failed';
                continue;
            }

            $items[$domainName] = array(
                'domain' => $result['domain'],
                'provider' => $result['provider_code'],
                'provider_code' => $result['provider_code'],
                'status' => $result['status'],
                'available' => $result['available'],
                'price' => $result['final_sale_price'],
                'currency' => $result['currency'],
                'final_sale_price' => $result['final_sale_price'],
            );
        }

        return array('success' => count($items) > 0, 'query' => $query, 'sld' => $sld, 'items' => $items, 'errors' => $errors);
    }

    protected function cleanQuery($query)
    {
        $query = strtolower(trim((string)$query));
        $query = preg_replace('#^[a-z][a-z0-9+\-.]*://#i', '', $query);
        $query = preg_replace('#^www\.#i', '', $query);
        $query = preg_replace('/\s+/', '', $query);
        $query = preg_replace('/[\/?#].*$/', '', $query);

        $allowed = 'abcdefghijklmnopqrstuvwxyz0123456789-.';
        $clean = '';
        for ($i = 0; $i < strlen($query); $i++) {
            if (strpos($allowed, $query[$i]) !== false) {
                $clean .= $query[$i];
            }
        }
        return trim($clean, '.-');
    }

    protected function extractSld($query)
    {
        $parts = explode('.', $query);
        return $parts[0];
    }

    protected function defaultTlds()
    {
        return array('com', 'net', 'org', 'info', 'biz', 'tr', 'com.tr', 'net.tr', 'org.tr', 'av.tr', 'gen.tr', 'web.tr');
    }
}
