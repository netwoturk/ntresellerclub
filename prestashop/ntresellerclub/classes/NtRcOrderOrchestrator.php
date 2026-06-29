<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcDomainManager.php';
require_once __DIR__ . '/NtRcHostingManager.php';
require_once __DIR__ . '/NtRcHostingProductMappingManager.php';
require_once __DIR__ . '/NtRcSslManager.php';
require_once __DIR__ . '/NtRcSslProductMappingManager.php';
require_once __DIR__ . '/NtRcBillingEventManager.php';
require_once __DIR__ . '/NtRcNotificationEngine.php';
require_once __DIR__ . '/NtRcOperationQueueManager.php';
require_once __DIR__ . '/NtRcProviderCustomerManager.php';
require_once __DIR__ . '/NtRcCartDomain.php';
require_once __DIR__ . '/NtRcLog.php';
require_once __DIR__ . '/providers/NtRcTldRouteManager.php';

class NtRcOrderOrchestrator
{
    protected $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function processOrder($idOrder)
    {
        $order = new Order((int)$idOrder);
        if (!Validate::isLoadedObject($order)) {
            return array('success' => false, 'message' => 'Siparis bulunamadi.');
        }

        $state = $this->orderPaymentState($order);
        if (!$state['paid']) {
            $eventType = $this->eventTypeForUnpaidState($state['normalized']);
            NtRcBillingEventManager::record($eventType, 'skipped', $this->orderContext($order), $state['label'], array('order_state' => $state['normalized']));
            $this->notifyAdmin('order_state_invalid', $this->orderContext($order), 'Order state provisioning icin uygun degil: ' . $state['label']);
            return array('success' => true, 'skipped' => true, 'order_state' => $state['label'], 'message' => 'Odeme onaylanmadan provisioning baslatilmadi.');
        }

        NtRcBillingEventManager::record('order_paid', 'ok', $this->orderContext($order), 'Order paid/accepted.');

        $results = array();
        $processedDomains = array();
        $domainManager = new NtRcDomainManager($this->module);
        $hostingManager = new NtRcHostingManager();
        $sslManager = new NtRcSslManager();

        foreach ((array)NtRcCartDomain::getDomainsByCart((int)$order->id_cart) as $cartDomain) {
            if (!empty($cartDomain['domain_name'])) {
                $processedDomains[strtolower(trim((string)$cartDomain['domain_name']))] = true;
            }
            $results[] = $this->processCartDomain($order, $cartDomain, $domainManager);
        }

        foreach ((array)$order->getProducts() as $product) {
            $classified = $this->classifyProduct($product);
            if ($classified['service_type'] === '') {
                continue;
            }
            if ($classified['domain_name'] !== '' && isset($processedDomains[$classified['domain_name']]) && in_array($classified['service_type'], array('domain', 'tr_domain'), true)) {
                continue;
            }

            if ($classified['service_type'] === 'hosting') {
                $results[] = $this->processHostingProduct($order, $product, $classified, $hostingManager);
            } elseif ($classified['service_type'] === 'ssl') {
                $results[] = $this->processSslProduct($order, $product, $classified, $sslManager);
            } else {
                $results[] = $this->processDomainProduct($order, $product, $classified, $domainManager);
            }
        }

        return array('success' => true, 'order_id' => (int)$order->id, 'items' => $results);
    }

    protected function processCartDomain(Order $order, array $cartDomain, NtRcDomainManager $domainManager)
    {
        $validation = $this->validateCartDomainMetadata($cartDomain);
        if (empty($validation['success'])) {
            return $this->cartMetadataInvalid($order, $cartDomain, $validation['missing']);
        }

        $domainName = !empty($cartDomain['domain_name']) ? strtolower(trim((string)$cartDomain['domain_name'])) : '';
        if ($domainName === '') {
            return array('success' => false, 'message' => 'Sepet domain bilgisi bulunamadi.');
        }

        $providerCode = strtolower($cartDomain['provider_code']);
        $serviceType = strtolower($cartDomain['service_type']);
        $idProduct = !empty($cartDomain['id_product']) ? (int)$cartDomain['id_product'] : 0;

        if ($this->serviceExists((int)$order->id, $idProduct, $serviceType, $domainName, $providerCode)) {
            return $this->duplicateSkipped($order, $idProduct, $serviceType, $domainName, $providerCode);
        }

        $providerCustomer = NtRcProviderCustomerManager::ensure((int)$order->id_customer, $providerCode, $domainName);
        if (empty($providerCustomer['success'])) {
            NtRcBillingEventManager::record('provisioning_failed', 'failed', $this->itemContext($order, 0, $serviceType, $domainName, $providerCode), isset($providerCustomer['message']) ? $providerCustomer['message'] : 'Provider customer hazirlanamadi.');
            return $providerCustomer;
        }

        $result = $domainManager->provisionCartDomain($order, $cartDomain, $providerCustomer);
        return $this->recordProvisionResult($order, $result, $idProduct, $serviceType, $domainName, $providerCode);
    }

    protected function validateCartDomainMetadata(array $cartDomain)
    {
        $required = array('domain_name', 'tld', 'provider_code', 'service_type', 'years', 'id_product', 'price_snapshot', 'currency');
        $missing = array();

        foreach ($required as $field) {
            if (!array_key_exists($field, $cartDomain) || $cartDomain[$field] === null || $cartDomain[$field] === '') {
                $missing[] = $field;
            }
        }

        if (!empty($cartDomain['id_product']) && (int)$cartDomain['id_product'] <= 0) {
            $missing[] = 'id_product';
        }
        if (!empty($cartDomain['years']) && (int)$cartDomain['years'] <= 0) {
            $missing[] = 'years';
        }
        if (!empty($cartDomain['domain_name']) && !preg_match('/^([a-z0-9-]+\.)+[a-z0-9-]{2,}$/i', (string)$cartDomain['domain_name'])) {
            $missing[] = 'domain_name';
        }
        if (!empty($cartDomain['provider_code']) && !in_array(strtolower((string)$cartDomain['provider_code']), array('resellerclub', 'domainnameapi'), true)) {
            $missing[] = 'provider_code';
        }
        if (!empty($cartDomain['service_type']) && !in_array(strtolower((string)$cartDomain['service_type']), array('domain', 'tr_domain'), true)) {
            $missing[] = 'service_type';
        }

        $missing = array_values(array_unique($missing));
        return array('success' => empty($missing), 'missing' => $missing);
    }

    protected function cartMetadataInvalid(Order $order, array $cartDomain, array $missing)
    {
        $context = $this->itemContext(
            $order,
            !empty($cartDomain['id_product']) ? (int)$cartDomain['id_product'] : 0,
            !empty($cartDomain['service_type']) ? (string)$cartDomain['service_type'] : 'domain',
            !empty($cartDomain['domain_name']) ? (string)$cartDomain['domain_name'] : '',
            !empty($cartDomain['provider_code']) ? (string)$cartDomain['provider_code'] : ''
        );

        NtRcBillingEventManager::record(
            'cart_metadata_invalid',
            'failed',
            $context,
            'Sepet domain metadata eksik veya gecersiz.',
            array('missing' => $missing)
        );
        $this->notifyAdmin('cart_metadata_invalid', $context, 'Cart domain metadata invalid: ' . implode(',', $missing));

        return array(
            'success' => false,
            'skipped' => true,
            'code' => 'cart_metadata_invalid',
            'message' => 'Sepet domain metadata gecersiz.',
        );
    }

    protected function processDomainProduct(Order $order, array $product, array $classified, NtRcDomainManager $domainManager)
    {
        if ($this->serviceExists((int)$order->id, (int)$classified['id_product'], $classified['service_type'], $classified['domain_name'], $classified['provider_code'])) {
            return $this->duplicateSkipped($order, (int)$classified['id_product'], $classified['service_type'], $classified['domain_name'], $classified['provider_code']);
        }

        $result = $domainManager->maybeProvisionDomain($order, $product, array('success' => true));
        return $this->recordProvisionResult($order, $result, (int)$classified['id_product'], $classified['service_type'], $classified['domain_name'], $classified['provider_code']);
    }

    protected function processHostingProduct(Order $order, array $product, array $classified, NtRcHostingManager $hostingManager)
    {
        if ($this->serviceExists((int)$order->id, (int)$classified['id_product'], 'hosting', $classified['domain_name'], 'resellerclub')) {
            return $this->duplicateSkipped($order, (int)$classified['id_product'], 'hosting', $classified['domain_name'], 'resellerclub');
        }

        $result = $hostingManager->maybeProvisionHosting($order, $product);
        return $this->recordProvisionResult($order, $result, (int)$classified['id_product'], 'hosting', $classified['domain_name'], 'resellerclub');
    }

    protected function processSslProduct(Order $order, array $product, array $classified, NtRcSslManager $sslManager)
    {
        if ($this->serviceExists((int)$order->id, (int)$classified['id_product'], 'ssl', $classified['domain_name'], 'resellerclub')) {
            return $this->duplicateSkipped($order, (int)$classified['id_product'], 'ssl', $classified['domain_name'], 'resellerclub');
        }

        $result = $sslManager->maybeProvisionSsl($order, $product);
        return $this->recordProvisionResult($order, $result, (int)$classified['id_product'], 'ssl', $classified['domain_name'], 'resellerclub');
    }

    protected function classifyProduct(array $product)
    {
        $idProduct = isset($product['id_product']) ? (int)$product['id_product'] : 0;
        $domainName = $this->extractDomainName($product);
        $reference = isset($product['product_reference']) ? strtoupper(trim((string)$product['product_reference'])) : '';

        $hostingMapping = NtRcHostingProductMappingManager::getByProductId($idProduct, true);
        if ($hostingMapping) {
            return array('service_type' => 'hosting', 'provider_code' => 'resellerclub', 'domain_name' => $domainName, 'id_product' => $idProduct);
        }

        $sslMapping = NtRcSslProductMappingManager::getByProductId($idProduct, true);
        if ($sslMapping || strpos($reference, 'SSL:') === 0) {
            return array('service_type' => 'ssl', 'provider_code' => 'resellerclub', 'domain_name' => $domainName, 'id_product' => $idProduct);
        }

        if ($domainName !== '') {
            $providerCode = NtRcTldRouteManager::resolveDomain($domainName);
            return array(
                'service_type' => $providerCode === 'domainnameapi' ? 'tr_domain' : 'domain',
                'provider_code' => $providerCode,
                'domain_name' => $domainName,
                'id_product' => $idProduct,
            );
        }

        return array('service_type' => '', 'provider_code' => '', 'domain_name' => '', 'id_product' => $idProduct);
    }

    protected function recordProvisionResult(Order $order, array $result, $idProduct, $serviceType, $domainName, $providerCode)
    {
        $context = $this->itemContext($order, $idProduct, $serviceType, $domainName, $providerCode);
        if (!empty($result['service_id'])) {
            $context['id_service'] = (int)$result['service_id'];
        }

        if (!empty($result['success']) && empty($result['skipped'])) {
            NtRcBillingEventManager::record('provisioning_queued', 'queued', $context, 'Provisioning queue olusturuldu.', array('queue_id' => isset($result['queue_id']) ? (int)$result['queue_id'] : 0));
        } elseif (!empty($result['skipped'])) {
            NtRcBillingEventManager::record('duplicate_skipped', 'skipped', $context, isset($result['reason']) ? $result['reason'] : 'Provisioning atlandi.');
        } else {
            NtRcBillingEventManager::record('provisioning_failed', 'failed', $context, isset($result['message']) ? $result['message'] : 'Provisioning basarisiz.');
            $this->notifyAdmin('provisioning_failed', $context, isset($result['message']) ? $result['message'] : 'Provisioning basarisiz.');
        }

        return $result;
    }

    protected function duplicateSkipped(Order $order, $idProduct, $serviceType, $domainName, $providerCode)
    {
        $context = $this->itemContext($order, $idProduct, $serviceType, $domainName, $providerCode);
        NtRcBillingEventManager::record('duplicate_skipped', 'skipped', $context, 'Duplicate provisioning engellendi.');
        $this->notifyAdmin('duplicate_provisioning_attempt', $context, 'Duplicate provisioning attempt skipped.');
        return array('success' => true, 'skipped' => true, 'source' => 'duplicate_guard', 'service_type' => $serviceType, 'domain' => $domainName);
    }

    protected function serviceExists($idOrder, $idProduct, $serviceType, $domainName, $providerCode)
    {
        $domainSql = $domainName === '' ? '(domain_name IS NULL OR domain_name="")' : 'domain_name="' . pSQL($domainName) . '"';
        $serviceTypes = $serviceType === 'tr_domain' ? '("domain", "tr_domain")' : '("' . pSQL($serviceType) . '")';

        return (bool)Db::getInstance()->getValue(
            'SELECT id_ntresellerclub_service FROM `' . _DB_PREFIX_ . 'ntresellerclub_service` '
            . 'WHERE id_order=' . (int)$idOrder . ' '
            . 'AND id_product=' . (int)$idProduct . ' '
            . 'AND service_type IN ' . $serviceTypes . ' '
            . 'AND provider_code="' . pSQL($providerCode) . '" '
            . 'AND ' . $domainSql
        );
    }

    protected function createService(Order $order, $idProduct, $serviceType, $domainName, $providerCode, $status)
    {
        $ok = Db::getInstance()->insert('ntresellerclub_service', array(
            'id_customer' => (int)$order->id_customer,
            'id_order' => (int)$order->id,
            'id_product' => (int)$idProduct,
            'provider_code' => pSQL($providerCode),
            'service_type' => pSQL($serviceType),
            'domain_name' => $domainName !== '' ? pSQL($domainName) : null,
            'start_date' => date('Y-m-d'),
            'status' => pSQL($status),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        return $ok ? (int)Db::getInstance()->Insert_ID() : 0;
    }

    protected function orderPaymentState(Order $order)
    {
        $label = '';
        $paidFlag = false;

        if (!empty($order->current_state)) {
            $state = new OrderState((int)$order->current_state, isset($order->id_lang) ? (int)$order->id_lang : null);
            if (Validate::isLoadedObject($state)) {
                $paidFlag = !empty($state->paid);
                if (is_array($state->name)) {
                    $label = reset($state->name);
                } else {
                    $label = (string)$state->name;
                }
            }
        }

        $normalized = strtolower(trim($label));
        $normalized = preg_replace('/[^a-z0-9_ ]/', '', $normalized);
        $paid = $paidFlag || preg_match('/\b(payment accepted|accepted|paid)\b/i', $label);
        $blocked = preg_match('/awaiting payment|cancelled|canceled|refunded|payment error|failed|chargeback/i', $label);

        return array('paid' => $paid && !$blocked, 'label' => $label, 'normalized' => $normalized);
    }

    protected function eventTypeForUnpaidState($normalized)
    {
        if (strpos($normalized, 'cancel') !== false) {
            return 'order_cancelled';
        }
        if (strpos($normalized, 'refund') !== false) {
            return 'order_refunded';
        }
        return 'order_not_paid';
    }

    protected function extractDomainName(array $product)
    {
        foreach (array('domain_name', 'custom_domain', 'product_reference', 'reference') as $key) {
            if (empty($product[$key])) {
                continue;
            }
            $value = strtolower(trim((string)$product[$key]));
            foreach (array('hosting:', 'domain:', 'ssl:') as $prefix) {
                if (strpos($value, $prefix) === 0) {
                    $value = substr($value, strlen($prefix));
                }
            }
            if (preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/i', $value)) {
                return $value;
            }
        }

        return '';
    }

    protected function notifyAdmin($eventType, array $context, $message)
    {
        try {
            $engine = new NtRcNotificationEngine();
            $engine->enqueueAdminNotification(
                'queue_failed_admin',
                array(
                    'provider_code' => isset($context['provider_code']) ? $context['provider_code'] : '',
                    'queue_id' => 0,
                    'provider_status' => $eventType,
                    'error_message' => NtRcBillingEventManager::safeText($message),
                    'checked_at' => date('Y-m-d H:i:s'),
                ),
                'admin',
                'billing_orchestrator:' . $eventType . ':' . (isset($context['id_order']) ? (int)$context['id_order'] : 0) . ':' . md5(json_encode($context)),
                1
            );
        } catch (Exception $e) {
            NtRcLog::add('warning', 'order_orchestrator', 'Admin notification failed ' . NtRcBillingEventManager::safeText($e->getMessage()));
        }
    }

    protected function orderContext(Order $order)
    {
        return array('id_order' => (int)$order->id, 'id_customer' => (int)$order->id_customer);
    }

    protected function itemContext(Order $order, $idProduct, $serviceType, $domainName, $providerCode)
    {
        return array(
            'id_order' => (int)$order->id,
            'id_customer' => (int)$order->id_customer,
            'id_product' => (int)$idProduct,
            'service_type' => $serviceType,
            'domain_name' => $domainName,
            'provider_code' => $providerCode,
        );
    }
}
