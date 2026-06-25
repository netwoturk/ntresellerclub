<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcPricingEngine.php';

class NtRcTrPriceCalculator
{
    public static function calculate(array $row, $targetCurrency = null, array $options = array())
    {
        return NtRcPricingEngine::calculate($row, $targetCurrency, $options);
    }

    public static function calculateFromValues($costPrice, $costCurrency, $targetCurrency, array $rules = array())
    {
        return NtRcPricingEngine::calculateFromValues($costPrice, $costCurrency, $targetCurrency, $rules);
    }
}
