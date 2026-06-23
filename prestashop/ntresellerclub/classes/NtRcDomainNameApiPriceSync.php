<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcTrPriceManager.php';
require_once __DIR__ . '/providers/NtRcProviderFactory.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcDomainNameApiPriceSync
{
    public function sync()
    {
        $provider = NtRcProviderFactory::make('domainnameapi');
        if (!$provider) {
            NtRcLog::add('error', 'dna_price_sync', 'DomainNameAPI provider not available');
            return array('success' => false, 'message' => 'DomainNameAPI provider aktif degil.');
        }

        $prices = $this->fetchPrices($provider);
        if (empty($prices)) {
            NtRcLog::add('warning', 'dna_price_sync', 'No TR price data returned');
            return array('success' => false, 'message' => 'Fiyat verisi alinamadi.');
        }

        $count = 0;
        foreach ($prices as $tld => $row) {
            if (!NtRcTrPriceManager::isAllowedTld($tld)) {
                continue;
            }
            NtRcTrPriceManager::upsertCost($tld, isset($row['currency']) ? $row['currency'] : 'USD', array(
                'register' => isset($row['register']) ? $row['register'] : 0,
                'transfer' => isset($row['transfer']) ? $row['transfer'] : 0,
                'renew' => isset($row['renew']) ? $row['renew'] : 0,
                'restore' => isset($row['restore']) ? $row['restore'] : 0,
                'trustee' => isset($row['trustee']) ? $row['trustee'] : 0,
                'backorder' => isset($row['backorder']) ? $row['backorder'] : 0,
            ));
            $count++;
        }

        NtRcLog::add('info', 'dna_price_sync', 'TR price sync completed: ' . $count);
        return array('success' => true, 'count' => $count);
    }

    protected function fetchPrices($provider)
    {
        if (method_exists($provider, 'getTrPrices')) {
            $response = $provider->getTrPrices();
            if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                return $response['data'];
            }
        }

        return array();
    }
}
