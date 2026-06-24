<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcTrPriceManager.php';
require_once __DIR__ . '/providers/NtRcProviderFactory.php';
require_once __DIR__ . '/NtRcLog.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcHistoryManager.php';

class NtRcDomainNameApiPriceSync
{
    public function sync()
    {
        NtRcRuntimeGuard::beforeHeavyProcess('dna_price_sync');

        $provider = NtRcProviderFactory::make('domainnameapi');
        if (!$provider) {
            NtRcLog::add('error', 'dna_price_sync', 'DomainNameAPI provider not available');
            return array('success' => false, 'message' => 'DomainNameAPI provider aktif degil.');
        }

        try {
            $prices = $this->fetchPrices($provider);
        } catch (Exception $e) {
            NtRcLog::add('error', 'dna_price_sync', 'Fetch exception: ' . $e->getMessage());
            return array('success' => false, 'message' => 'Fiyat verisi alinirken hata olustu.', 'error' => $e->getMessage());
        }

        if (empty($prices)) {
            NtRcLog::add('warning', 'dna_price_sync', 'No TR price data returned');
            return array('success' => false, 'message' => 'Fiyat verisi alinamadi.');
        }

        $count = 0;
        $changed = 0;
        $errors = array();

        foreach ($prices as $tld => $row) {
            try {
                if (!NtRcTrPriceManager::isAllowedTld($tld)) {
                    continue;
                }

                $currency = isset($row['currency']) ? $row['currency'] : 'USD';
                $costs = array(
                    'register' => isset($row['register']) ? $row['register'] : 0,
                    'transfer' => isset($row['transfer']) ? $row['transfer'] : 0,
                    'renew' => isset($row['renew']) ? $row['renew'] : 0,
                    'restore' => isset($row['restore']) ? $row['restore'] : 0,
                    'trustee' => isset($row['trustee']) ? $row['trustee'] : 0,
                    'backorder' => isset($row['backorder']) ? $row['backorder'] : 0,
                );

                $changed += $this->recordChangesBeforeUpsert($tld, $currency, $costs);
                NtRcTrPriceManager::upsertCost($tld, $currency, $costs);
                $count++;
            } catch (Exception $e) {
                $errors[] = $tld . ': ' . $e->getMessage();
                NtRcLog::add('error', 'dna_price_sync', 'TLD sync error ' . $tld . ' ' . $e->getMessage());
            }
        }

        NtRcLog::add('info', 'dna_price_sync', 'TR price sync completed count=' . $count . ' changed=' . $changed);
        return array('success' => true, 'count' => $count, 'changed' => $changed, 'errors' => $errors);
    }

    protected function recordChangesBeforeUpsert($tld, $currency, array $costs)
    {
        $changed = 0;
        $existingRows = NtRcTrPriceManager::getByTld($tld);
        $existing = array();
        foreach ((array)$existingRows as $row) {
            $existing[$row['code']] = $row;
        }

        foreach ($costs as $operation => $newCost) {
            $code = $tld . ':' . $operation;
            $oldCost = isset($existing[$code]) ? (float)$existing[$code]['cost_price'] : null;
            $oldSale = isset($existing[$code]) ? (float)$existing[$code]['sale_price'] : null;
            if ($oldCost === null || (float)$oldCost !== (float)$newCost) {
                NtRcHistoryManager::addPriceChange('domainnameapi', 'tr_domain', $code, $oldCost, (float)$newCost, $oldSale, $oldSale, $currency, 'dna_sync');
                $changed++;
            }
        }

        return $changed;
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
