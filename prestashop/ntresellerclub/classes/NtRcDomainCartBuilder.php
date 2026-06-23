<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/providers/NtRcTldRouteManager.php';
require_once __DIR__ . '/NtRcTrPriceManager.php';
require_once __DIR__ . '/NtRcTrPriceCalculator.php';

class NtRcDomainCartBuilder
{
    public function addDomainToCart($idCart, $domainName, $years = 1, array $options = array())
    {
        $domainName = $this->normalizeDomain($domainName);
        if (!$domainName || !$this->isValidDomain($domainName)) {
            return array('success' => false, 'message' => 'Gecersiz alan adi.');
        }

        $tld = $this->extractTld($domainName);
        $providerCode = NtRcTldRouteManager::resolve($tld);
        if (!$providerCode) {
            return array('success' => false, 'message' => 'Bu uzanti icin provider bulunamadi.');
        }

        $price = $this->resolvePrice($tld, $providerCode, 'register');
        $exists = Db::getInstance()->getRow('SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_cart_domain` WHERE id_cart=' . (int)$idCart . ' AND domain_name="' . pSQL($domainName) . '"');

        $data = array(
            'id_cart' => (int)$idCart,
            'domain_name' => pSQL($domainName),
            'provider_code' => pSQL($providerCode),
            'years' => (int)$years,
            'created_at' => date('Y-m-d H:i:s'),
        );

        if ($exists) {
            Db::getInstance()->update('ntresellerclub_cart_domain', $data, 'id_ntresellerclub_cart_domain=' . (int)$exists['id_ntresellerclub_cart_domain']);
        } else {
            Db::getInstance()->insert('ntresellerclub_cart_domain', $data);
        }

        return array('success' => true, 'domain' => $domainName, 'provider' => $providerCode, 'years' => (int)$years, 'price' => $price);
    }

    public function getCartDomains($idCart)
    {
        return Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'ntresellerclub_cart_domain` WHERE id_cart=' . (int)$idCart . ' ORDER BY id_ntresellerclub_cart_domain ASC');
    }

    public function removeDomainFromCart($idCart, $domainName)
    {
        return Db::getInstance()->delete('ntresellerclub_cart_domain', 'id_cart=' . (int)$idCart . ' AND domain_name="' . pSQL($this->normalizeDomain($domainName)) . '"');
    }

    protected function resolvePrice($tld, $providerCode, $operation)
    {
        if ($providerCode === 'domainnameapi' && NtRcTrPriceManager::isAllowedTld($tld)) {
            $rows = NtRcTrPriceManager::getByTld($tld);
            foreach ((array)$rows as $row) {
                if ($row['code'] === $tld . ':' . $operation) {
                    return NtRcTrPriceCalculator::calculate($row);
                }
            }
        }
        return array('success' => true, 'sale_price' => null, 'target_currency' => null, 'source' => 'provider_or_product');
    }

    protected function normalizeDomain($domainName)
    {
        $domainName = strtolower(trim((string)$domainName));
        $domainName = str_replace(array('http://', 'https://', 'www.'), '', $domainName);
        return trim($domainName, '/ .');
    }

    protected function isValidDomain($domainName)
    {
        if (strpos($domainName, '.') === false) {
            return false;
        }
        if (strpos($domainName, ' ') !== false) {
            return false;
        }
        return true;
    }

    protected function extractTld($domainName)
    {
        $parts = explode('.', $domainName);
        if (count($parts) >= 3) {
            $lastTwo = $parts[count($parts) - 2] . '.' . $parts[count($parts) - 1];
            if (NtRcTrPriceManager::isAllowedTld($lastTwo)) {
                return $lastTwo;
            }
        }
        return end($parts);
    }
}
