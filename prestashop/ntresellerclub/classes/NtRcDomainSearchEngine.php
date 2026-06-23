<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/providers/NtRcProviderFactory.php';
require_once __DIR__ . '/providers/NtRcTldRouteManager.php';
require_once __DIR__ . '/providers/NtRcProviderRegistry.php';
require_once __DIR__ . '/NtRcTrPriceManager.php';
require_once __DIR__ . '/NtRcTrPriceCalculator.php';

class NtRcDomainSearchEngine
{
    public function search($query, array $tlds = array())
    {
        $query = $this->cleanQuery($query);
        if (!$query) {
            return array('success' => false, 'message' => 'Invalid query', 'items' => array());
        }

        if (!$tlds) {
            $tlds = $this->defaultTlds();
        }

        $sld = $this->extractSld($query);
        $items = array();
        $errors = array();

        foreach ($tlds as $tld) {
            $tld = strtolower(ltrim(trim($tld), '.'));
            if (!$tld) {
                continue;
            }

            $domainName = $sld . '.' . $tld;
            $providerCode = NtRcTldRouteManager::resolve($tld);

            if (!$providerCode || !NtRcProviderRegistry::isUsable($providerCode)) {
                $errors[$domainName] = 'provider_not_available';
                continue;
            }

            $provider = NtRcProviderFactory::make($providerCode);
            if (!$provider) {
                $errors[$domainName] = 'provider_factory_failed';
                continue;
            }

            $response = $provider->checkAvailability($sld, array($tld), 1);
            if (!isset($response['success']) || !$response['success']) {
                $errors[$domainName] = 'search_failed';
                continue;
            }

            foreach ((array)$response['data'] as $domain => $row) {
                $items[$domain] = $this->normalizeItem($domain, $providerCode, $row, $tld);
            }
        }

        return array('success' => count($items) > 0, 'query' => $query, 'sld' => $sld, 'items' => $items, 'errors' => $errors);
    }

    protected function cleanQuery($query)
    {
        $query = strtolower(trim((string)$query));
        $query = str_replace(array('http://', 'https://', 'www.'), '', $query);
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

    protected function normalizeItem($domain, $providerCode, $row, $tld)
    {
        $status = 'unknown';
        if (is_array($row) && isset($row['status'])) {
            $status = strtolower($row['status']);
        } elseif (is_string($row)) {
            $status = strtolower($row);
        }

        $item = array(
            'domain' => $domain,
            'provider' => $providerCode,
            'status' => $status,
            'available' => in_array($status, array('available', 'avail')),
            'raw' => $row
        );

        if ($providerCode === 'domainnameapi' && NtRcTrPriceManager::isAllowedTld($tld)) {
            $price = $this->getTrRegisterPrice($tld);
            if ($price && !empty($price['success'])) {
                $item['price'] = $price['sale_price'];
                $item['currency'] = $price['target_currency'];
                $item['cost_converted'] = $price['cost_converted'];
            }
        }

        return $item;
    }

    protected function getTrRegisterPrice($tld)
    {
        $rows = NtRcTrPriceManager::getByTld($tld);
        foreach ((array)$rows as $row) {
            if ($row['code'] === $tld . ':register') {
                return NtRcTrPriceCalculator::calculate($row);
            }
        }
        return null;
    }
}
