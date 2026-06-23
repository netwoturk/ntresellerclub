<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcDomainSearchResultFormatter
{
    public static function toJson(array $result)
    {
        $items = array();
        foreach ((array)$result['items'] as $domain => $row) {
            $items[] = array(
                'domain' => $domain,
                'provider' => isset($row['provider']) ? $row['provider'] : '',
                'status' => isset($row['status']) ? $row['status'] : 'unknown',
                'available' => !empty($row['available']),
                'price' => isset($row['price']) ? $row['price'] : null,
                'currency' => isset($row['currency']) ? $row['currency'] : null,
            );
        }

        return array(
            'success' => !empty($result['success']),
            'query' => isset($result['query']) ? $result['query'] : '',
            'sld' => isset($result['sld']) ? $result['sld'] : '',
            'items' => $items,
            'errors' => isset($result['errors']) ? $result['errors'] : array(),
        );
    }
}
