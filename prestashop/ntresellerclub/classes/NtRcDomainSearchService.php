<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcPricingManager.php';
require_once __DIR__ . '/NtRcTrPriceManager.php';
require_once __DIR__ . '/providers/NtRcProviderFactory.php';
require_once __DIR__ . '/providers/NtRcTldRouteManager.php';

class NtRcDomainSearchService
{
    const CACHE_TTL_SECONDS = 60;

    protected static $runtimeCache = array();

    public function search($query)
    {
        $normalized = $this->normalizeDomain($query);
        if (!$normalized['success']) {
            return $this->response(false, $query, null, array($this->emptyResult($normalized['domain'], null, null, $normalized['error'])));
        }

        $domain = $normalized['domain'];
        $cached = $this->readCache($domain);
        if ($cached !== null) {
            return $cached;
        }

        $parts = $this->splitDomain($domain);
        if (!$parts) {
            $result = $this->emptyResult($domain, null, null, 'Domain uzantisi okunamadi.');
            return $this->writeCache($domain, $this->response(false, $query, $domain, array($result)));
        }

        $providerCode = $this->resolveProvider($parts['tld']);
        if (!$providerCode) {
            $result = $this->emptyResult($domain, $parts['tld'], null, 'Bu uzanti icin aktif provider route bulunamadi.');
            return $this->writeCache($domain, $this->response(false, $query, $domain, array($result)));
        }

        if (!$this->isProviderEnabled($providerCode)) {
            $result = $this->emptyResult($domain, $parts['tld'], $providerCode, 'Provider ayarlari pasif veya eksik.');
            return $this->writeCache($domain, $this->response(false, $query, $domain, array($result)));
        }

        $provider = NtRcProviderFactory::make($providerCode, false);
        if (!$provider) {
            $result = $this->emptyResult($domain, $parts['tld'], $providerCode, 'Provider aktif degil veya kullanilabilir degil.');
            return $this->writeCache($domain, $this->response(false, $query, $domain, array($result)));
        }

        try {
            $availability = $provider->checkAvailability($parts['sld'], array($parts['tld']), 1);
        } catch (Exception $e) {
            $availability = array('success' => false, 'error' => $this->safeText($e->getMessage()));
        }

        $result = $this->buildResult($domain, $parts['tld'], $providerCode, $availability);
        return $this->writeCache($domain, $this->response(empty($result['error']), $query, $domain, array($result)));
    }

    protected function normalizeDomain($query)
    {
        $domain = strtolower(trim((string)$query));
        $domain = preg_replace('#^[a-z][a-z0-9+\-.]*://#i', '', $domain);
        $domain = preg_replace('#^www\.#i', '', $domain);
        $domain = preg_replace('/\s+/', '', $domain);
        $domain = preg_replace('/[\/?#].*$/', '', $domain);
        $domain = trim($domain, ".\t\n\r\0\x0B");

        if ($domain === '') {
            return array('success' => false, 'domain' => '', 'error' => 'Domain zorunludur.');
        }

        if (preg_match('/[^\x20-\x7E]/', $domain)) {
            if (function_exists('idn_to_ascii')) {
                $variant = defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0;
                $flags = defined('IDNA_DEFAULT') ? IDNA_DEFAULT : 0;
                $ascii = idn_to_ascii($domain, $flags, $variant);
                if ($ascii === false || $ascii === '') {
                    return array('success' => false, 'domain' => $domain, 'error' => 'IDN domain ASCII formata cevrilemedi.');
                }
                $domain = strtolower($ascii);
            } else {
                return array('success' => false, 'domain' => $domain, 'error' => 'IDN domain destegi icin PHP intl eklentisi gereklidir.');
            }
        }

        if (!preg_match('/^[a-z0-9][a-z0-9\.-]*[a-z0-9]$/', $domain) || strpos($domain, '..') !== false) {
            return array('success' => false, 'domain' => $domain, 'error' => 'Domain formati gecersiz.');
        }

        return array('success' => true, 'domain' => $domain, 'error' => null);
    }

    protected function splitDomain($domain)
    {
        foreach ($this->knownMultiPartTlds() as $tld) {
            $suffix = '.' . $tld;
            if (substr($domain, -strlen($suffix)) === $suffix && strlen($domain) > strlen($suffix)) {
                return array(
                    'sld' => substr($domain, 0, -strlen($suffix)),
                    'tld' => $tld,
                );
            }
        }

        $pos = strrpos($domain, '.');
        if ($pos === false || $pos === 0 || $pos === strlen($domain) - 1) {
            return null;
        }

        return array(
            'sld' => substr($domain, 0, $pos),
            'tld' => substr($domain, $pos + 1),
        );
    }

    protected function resolveProvider($tld)
    {
        $tld = strtolower(ltrim(trim((string)$tld), '.'));
        if (NtRcTrPriceManager::isAllowedTld($tld)) {
            return 'domainnameapi';
        }

        $provider = NtRcTldRouteManager::resolve($tld);
        if ($provider) {
            return $provider;
        }

        return 'resellerclub';
    }

    protected function buildResult($domain, $tld, $providerCode, array $availability)
    {
        $price = $this->priceFor($providerCode, $tld);
        $result = array(
            'domain' => $domain,
            'tld' => $tld,
            'provider_code' => $providerCode,
            'available' => null,
            'status' => 'unknown',
            'price' => $price['price'],
            'currency' => $price['currency'],
            'final_sale_price' => $price['final_sale_price'],
            'error' => null,
        );

        if (empty($availability['success'])) {
            $result['status'] = 'provider_error';
            $result['error'] = $this->responseError($availability);
            return $result;
        }

        $row = $this->availabilityRow($availability, $domain);
        if (!$row) {
            $result['status'] = 'unknown';
            $result['error'] = 'Provider availability cevabi okunamadi.';
            return $result;
        }

        $status = isset($row['status']) ? strtolower(trim((string)$row['status'])) : 'unknown';
        $result['status'] = $status;
        $result['available'] = in_array($status, array('available', 'avail'), true);

        if ($result['price'] === null && isset($row['price']) && is_numeric($row['price'])) {
            $result['price'] = (float)$row['price'];
        }
        if ($result['currency'] === null && !empty($row['currency'])) {
            $result['currency'] = strtoupper($row['currency']);
        }

        return $result;
    }

    protected function availabilityRow(array $availability, $domain)
    {
        $data = isset($availability['data']) && is_array($availability['data']) ? $availability['data'] : array();
        if (isset($data[$domain]) && is_array($data[$domain])) {
            return $data[$domain];
        }

        $lower = strtolower($domain);
        foreach ($data as $key => $row) {
            if (strtolower((string)$key) === $lower && is_array($row)) {
                return $row;
            }
        }

        return null;
    }

    protected function priceFor($providerCode, $tld)
    {
        $providerCode = strtolower(trim((string)$providerCode));
        $tld = strtolower(ltrim(trim((string)$tld), '.'));
        $productType = $providerCode === 'domainnameapi' ? 'tr_domain' : 'domain';
        $row = NtRcPricingManager::getRow($providerCode, $productType, $tld . ':register', 1);

        if (!$row) {
            return array('price' => null, 'currency' => null, 'final_sale_price' => null);
        }

        $calculated = NtRcPricingManager::calculateRow($row);
        $currency = !empty($calculated['currency']) ? $calculated['currency'] : (isset($row['target_currency']) ? $row['target_currency'] : $row['currency']);

        return array(
            'price' => isset($row['cost_price']) ? (float)$row['cost_price'] : null,
            'currency' => $currency ? strtoupper($currency) : null,
            'final_sale_price' => !empty($calculated['success']) && isset($calculated['final_sale_price']) ? (float)$calculated['final_sale_price'] : null,
        );
    }

    protected function isProviderEnabled($providerCode)
    {
        $providerCode = strtolower(trim((string)$providerCode));
        if ($providerCode === 'resellerclub') {
            return (bool)Configuration::get('NTRC_FEATURE_RESELLERCLUB')
                && trim((string)Configuration::get('NTRC_RESELLER_ID')) !== ''
                && trim((string)Configuration::get('NTRC_API_KEY')) !== '';
        }

        if ($providerCode === 'domainnameapi') {
            return (bool)Configuration::get('NTRC_FEATURE_DOMAINNAMEAPI')
                && trim((string)Configuration::get('NTRC_DNA_USERNAME')) !== ''
                && trim((string)Configuration::get('NTRC_DNA_PASSWORD')) !== '';
        }

        return false;
    }

    protected function emptyResult($domain, $tld, $providerCode, $error)
    {
        return array(
            'domain' => $domain,
            'tld' => $tld,
            'provider_code' => $providerCode,
            'available' => null,
            'status' => 'error',
            'price' => null,
            'currency' => null,
            'final_sale_price' => null,
            'error' => $this->safeText($error),
        );
    }

    protected function response($success, $query, $domain, array $results)
    {
        return array(
            'success' => (bool)$success,
            'query' => (string)$query,
            'normalized_domain' => $domain,
            'results' => $results,
            'cached' => false,
            'checked_at' => date('Y-m-d H:i:s'),
        );
    }

    protected function readCache($domain)
    {
        if (!isset(self::$runtimeCache[$domain])) {
            return null;
        }

        $item = self::$runtimeCache[$domain];
        if (!isset($item['expires_at']) || $item['expires_at'] < time()) {
            unset(self::$runtimeCache[$domain]);
            return null;
        }

        $value = $item['value'];
        $value['cached'] = true;
        return $value;
    }

    protected function writeCache($domain, array $value)
    {
        self::$runtimeCache[$domain] = array(
            'expires_at' => time() + self::CACHE_TTL_SECONDS,
            'value' => $value,
        );
        return $value;
    }

    protected function responseError(array $response)
    {
        foreach (array('message', 'error') as $key) {
            if (!empty($response[$key])) {
                return $this->safeText($response[$key]);
            }
        }
        return 'Provider availability sorgusu basarisiz.';
    }

    protected function safeText($text)
    {
        $text = (string)$text;
        $text = preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|secret)=([^&\s]+)/i', '$1=***', $text);
        return preg_replace('/([A-Za-z0-9_\-.]{24,})/', '***', $text);
    }

    protected function knownMultiPartTlds()
    {
        return array('com.tr', 'net.tr', 'org.tr', 'av.tr', 'gen.tr', 'web.tr');
    }
}
