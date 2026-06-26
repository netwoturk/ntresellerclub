<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcHostingProductMappingManager.php';
require_once __DIR__ . '/NtRcOperationQueueManager.php';
require_once __DIR__ . '/NtRcServiceRepository.php';
require_once __DIR__ . '/NtRcNotificationEngine.php';
require_once __DIR__ . '/NtRcBillingEventManager.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcHostingManager
{
    const PROVIDER_CODE = 'resellerclub';

    public function maybeProvisionHosting(Order $order, array $product)
    {
        $idProduct = isset($product['id_product']) ? (int)$product['id_product'] : 0;
        $mapping = NtRcHostingProductMappingManager::getByProductId($idProduct, true);
        if (!$mapping) {
            return array('success' => true, 'skipped' => true, 'reason' => 'Hosting mapping bulunamadi.');
        }

        $domainName = $this->extractDomainName($product);
        $idService = $this->createHostingService($order, $idProduct, $domainName, $mapping);

        if ($idService <= 0) {
            NtRcLog::add('error', 'hosting_provisioning', 'Hosting service insert failed order=' . (int)$order->id . ' product=' . $idProduct);
            return array('success' => false, 'message' => 'Hosting servis kaydi olusturulamadi.');
        }

        $payload = $this->buildCreatePayload($order, $product, $mapping, $idService, $domainName);
        $queue = NtRcOperationQueueManager::enqueue(
            self::PROVIDER_CODE,
            'hosting',
            'hosting/create',
            $payload,
            (int)$order->id,
            (int)$order->id_customer,
            $idService,
            3,
            3
        );

        if (empty($queue['success'])) {
            NtRcServiceRepository::updateStatus($idService, 'error');
            return $queue;
        }

        return array(
            'success' => true,
            'service_type' => 'hosting',
            'service_id' => $idService,
            'queue_id' => isset($queue['queue_id']) ? (int)$queue['queue_id'] : 0,
            'status' => 'provisioning',
        );
    }

    public function enqueueRenew($idService, $paymentConfirmed = false, array $options = array())
    {
        $service = NtRcServiceRepository::getService((int)$idService);
        if (!$service || $service['service_type'] !== 'hosting') {
            return array('success' => false, 'message' => 'Hosting renew icin servis kaydi bulunamadi.');
        }

        if (!$paymentConfirmed) {
            NtRcServiceRepository::updateStatus((int)$idService, 'payment_required');
            $this->enqueuePaymentRequiredNotification($service);
            $this->recordPaymentRequired($service, 'hosting');
            return array('success' => true, 'status' => 'payment_required', 'message' => 'Odeme alinmadan hosting renew queue olusturulmadi.');
        }

        return NtRcOperationQueueManager::enqueue(
            self::PROVIDER_CODE,
            'hosting',
            'hosting/renew',
            $this->buildLifecyclePayload($service, $options),
            isset($service['id_order']) ? (int)$service['id_order'] : null,
            isset($service['id_customer']) ? (int)$service['id_customer'] : null,
            (int)$idService,
            3,
            2
        );
    }

    public function enqueueSuspend($idService, array $options = array())
    {
        return $this->enqueueLifecycleAction($idService, 'hosting/suspend', $options, 2);
    }

    public function enqueueUnsuspend($idService, array $options = array())
    {
        return $this->enqueueLifecycleAction($idService, 'hosting/unsuspend', $options, 2);
    }

    protected function enqueueLifecycleAction($idService, $action, array $options, $priority)
    {
        $service = NtRcServiceRepository::getService((int)$idService);
        if (!$service || $service['service_type'] !== 'hosting') {
            return array('success' => false, 'message' => 'Hosting lifecycle icin servis kaydi bulunamadi.');
        }

        return NtRcOperationQueueManager::enqueue(
            self::PROVIDER_CODE,
            'hosting',
            $action,
            $this->buildLifecyclePayload($service, $options),
            isset($service['id_order']) ? (int)$service['id_order'] : null,
            isset($service['id_customer']) ? (int)$service['id_customer'] : null,
            (int)$idService,
            3,
            $priority
        );
    }

    protected function createHostingService(Order $order, $idProduct, $domainName, array $mapping)
    {
        $billingCycle = isset($mapping['billing_cycle']) ? $mapping['billing_cycle'] : 'yearly';
        $ok = Db::getInstance()->insert('ntresellerclub_service', array(
            'id_customer' => (int)$order->id_customer,
            'id_order' => (int)$order->id,
            'id_product' => (int)$idProduct,
            'provider_code' => pSQL(self::PROVIDER_CODE),
            'service_type' => pSQL('hosting'),
            'domain_name' => $domainName !== '' ? pSQL($domainName) : null,
            'provider_service_id' => null,
            'provider_order_id' => null,
            'provider_customer_id' => null,
            'provider_contact_id' => null,
            'start_date' => date('Y-m-d'),
            'expiry_date' => $this->expiryFromBillingCycle($billingCycle),
            'status' => pSQL('provisioning'),
            'renew_price' => isset($mapping['sale_price']) ? (float)$mapping['sale_price'] : 0,
            'currency' => !empty($mapping['currency']) ? pSQL($mapping['currency']) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        return $ok ? (int)Db::getInstance()->Insert_ID() : 0;
    }

    protected function expiryFromBillingCycle($billingCycle)
    {
        $map = array(
            'monthly' => '+1 month',
            'quarterly' => '+3 months',
            'semiannual' => '+6 months',
            'yearly' => '+1 year',
            'biennial' => '+2 years',
            'triennial' => '+3 years',
        );
        $billingCycle = strtolower(trim((string)$billingCycle));
        return date('Y-m-d', strtotime(isset($map[$billingCycle]) ? $map[$billingCycle] : '+1 year'));
    }

    protected function buildCreatePayload(Order $order, array $product, array $mapping, $idService, $domainName)
    {
        $payload = NtRcHostingProductMappingManager::payloadFromMapping($mapping, array(
            'product_reference' => isset($product['product_reference']) ? $product['product_reference'] : '',
            'product_name' => isset($product['product_name']) ? $product['product_name'] : '',
        ));

        $payload['id_order'] = (int)$order->id;
        $payload['id_customer'] = (int)$order->id_customer;
        $payload['id_service'] = (int)$idService;
        $payload['id_product'] = isset($product['id_product']) ? (int)$product['id_product'] : 0;
        $payload['domain'] = $domainName;
        $payload['domain_name'] = $domainName;
        $payload['quantity'] = isset($product['product_quantity']) ? (int)$product['product_quantity'] : 1;

        return $payload;
    }

    protected function buildLifecyclePayload(array $service, array $options = array())
    {
        return array_merge(array(
            'id_service' => isset($service['id_ntresellerclub_service']) ? (int)$service['id_ntresellerclub_service'] : 0,
            'domain' => isset($service['domain_name']) ? $service['domain_name'] : '',
            'domain_name' => isset($service['domain_name']) ? $service['domain_name'] : '',
            'provider_service_id' => isset($service['provider_service_id']) ? $service['provider_service_id'] : '',
            'provider_order_id' => isset($service['provider_order_id']) ? $service['provider_order_id'] : '',
            'expiry_date' => isset($service['expiry_date']) ? $service['expiry_date'] : '',
        ), $options);
    }

    protected function enqueuePaymentRequiredNotification(array $service)
    {
        try {
            $engine = new NtRcNotificationEngine();
            $engine->enqueueServiceNotification(
                'payment_required',
                (int)$service['id_ntresellerclub_service'],
                array('checked_at' => date('Y-m-d H:i:s')),
                'customer',
                2,
                'hosting_payment_required:' . (int)$service['id_ntresellerclub_service'] . ':' . date('Y-m-d')
            );
        } catch (Exception $e) {
            NtRcLog::add('warning', 'hosting_provisioning', 'Payment required notification failed service=' . (int)$service['id_ntresellerclub_service'] . ' ' . $this->safeText($e->getMessage()));
        }
    }

    protected function recordPaymentRequired(array $service, $serviceType)
    {
        NtRcBillingEventManager::record(
            'renewal_payment_required',
            'payment_required',
            array(
                'id_order' => !empty($service['id_order']) ? (int)$service['id_order'] : null,
                'id_customer' => !empty($service['id_customer']) ? (int)$service['id_customer'] : null,
                'id_service' => !empty($service['id_ntresellerclub_service']) ? (int)$service['id_ntresellerclub_service'] : null,
                'provider_code' => self::PROVIDER_CODE,
                'service_type' => $serviceType,
            ),
            'Odeme alinmadan renew provider queue olusturulmadi.',
            array('reason' => 'renewal_payment_required')
        );
    }

    protected function extractDomainName(array $product)
    {
        foreach (array('domain_name', 'custom_domain', 'product_reference', 'reference') as $key) {
            if (empty($product[$key])) {
                continue;
            }
            $value = trim((string)$product[$key]);
            if (strpos($value, 'HOSTING:') === 0) {
                $value = substr($value, 8);
            } elseif (strpos($value, 'DOMAIN:') === 0) {
                $value = substr($value, 7);
            }
            if (preg_match('/^([a-z0-9-]+\\.)+[a-z]{2,}$/i', $value)) {
                return strtolower($value);
            }
        }

        return '';
    }

    protected function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential)=([^&\\s]+)/i', '$1=***', (string)$text);
    }
}
