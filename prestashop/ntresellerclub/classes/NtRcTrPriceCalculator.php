<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcManualExchangeRate.php';

class NtRcTrPriceCalculator
{
    public static function calculate(array $row, $targetCurrency = null)
    {
        $targetCurrency = $targetCurrency ? strtoupper($targetCurrency) : NtRcManualExchangeRate::defaultTargetCurrency();
        $sourceCurrency = !empty($row['currency']) ? strtoupper($row['currency']) : 'USD';
        $cost = (float)$row['cost_price'];

        $convertedCost = NtRcManualExchangeRate::convert($cost, $sourceCurrency, $targetCurrency);
        if ($convertedCost === null) {
            return array('success' => false, 'message' => 'Kur tanimli degil.', 'currency' => $targetCurrency);
        }

        $mode = !empty($row['margin_mode']) ? $row['margin_mode'] : 'manual';
        $sale = (float)$row['sale_price'];

        if ($mode === 'percent') {
            $sale = $convertedCost + ($convertedCost * ((float)$row['margin_percent'] / 100));
        } elseif ($mode === 'fixed') {
            $sale = $convertedCost + (float)$row['margin_fixed'];
        } elseif ($mode === 'hybrid') {
            $base = $convertedCost + (float)$row['margin_fixed'];
            $sale = $base + ($base * ((float)$row['margin_percent'] / 100));
        }

        return array(
            'success' => true,
            'source_currency' => $sourceCurrency,
            'target_currency' => $targetCurrency,
            'cost_source' => $cost,
            'cost_converted' => round($convertedCost, 2),
            'sale_price' => round($sale, 2),
            'mode' => $mode,
        );
    }
}
