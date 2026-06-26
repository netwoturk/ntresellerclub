<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcSslProductMappingManager.php';
require_once __DIR__ . '/NtRcOperationQueueManager.php';
require_once __DIR__ . '/NtRcServiceRepository.php';
require_once __DIR__ . '/NtRcNotificationEngine.php';
require_once __DIR__ . '/NtRcBillingEventManager.php';
require_once __DIR__ . '/NtRcProviderCustomerManager.php';
require_once __DIR__ . '/NtRcLog.php';

class NtRcSslManager
{
    const PROVIDER_CODE = 'resellerclub';

    public function maybeProvisionSsl(Order $order, array $product)
    {
        $idProduct = isset($product['id_product']) ? (int)$product['id_product'] : 0;
        $mapping = NtRcSslProductMappingManager::getByProductId($idProduct, true);
        if (!$mapping) {
            return array('success' => true, 'skipped' => true, 'reason' => 'SSL mapping bulunamadi.');
        }

        $domainName = $this->extractDomainName($product);
        $providerCustomer = NtRcProviderCustomerManager::ensure((int)$order->id_customer, self::PROVIDER_CODE, $domainName);
        if (empty($providerCustomer['success'])) {
            return $providerCustomer;
        }
        if (empty($providerCustomer['provider_customer_id'])) {
            return array(
                'success' => true,
                'skipped' => true,
                'reason' => 'ResellerClub customer mapping pending; SSL provisioning waits for customer queue.',
                'queue_id' => isset($providerCustomer['queue_id']) ? (int)$providerCustomer['queue_id'] : 0,
            );
        }

        $idService = $this->createSslService($order, $idProduct, $domainName, $mapping);

        if ($idService <= 0) {
            NtRcLog::add('error', 'ssl_provisioning', 'SSL service insert failed order=' . (int)$order->id . ' product=' . $idProduct);
            return array('success' => false, 'message' => 'SSL servis kaydi olusturulamadi.');
        }

        $payload = $this->buildCreatePayload($order, $product, $mapping, $idService, $domainName, $providerCustomer['provider_customer_id']);
        $queue = NtRcOperationQueueManager::enqueue(
            self::PROVIDER_CODE,
            'ssl',
            'ssl/create',
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
            'service_type' => 'ssl',
            'service_id' => $idService,
            'queue_id' => isset($queue['queue_id']) ? (int)$queue['queue_id'] : 0,
            'status' => 'provisioning',
        );
    }

    public function enqueueRenew($idService, $paymentConfirmed = false, array $options = array())
    {
        $service = $this->sslService($idService, 'SSL renew icin servis kaydi bulunamadi.');
        if (empty($service['success'])) {
            return $service;
        }

        if (!$paymentConfirmed && empty($options['payment_confirmed'])) {
            NtRcServiceRepository::updateStatus((int)$idService, 'payment_required');
            $this->enqueuePaymentRequiredNotification($service['service']);
            $this->recordPaymentRequired($service['service']);
            return array('success' => true, 'status' => 'payment_required', 'message' => 'Odeme alinmadan SSL renew queue olusturulmadi.');
        }

        return $this->enqueueLifecycleAction($service['service'], 'ssl/renew', $options, 2);
    }

    public function enqueueReissue($idService, array $options = array())
    {
        $service = $this->sslService($idService, 'SSL reissue icin servis kaydi bulunamadi.');
        if (empty($service['success'])) {
            return $service;
        }

        return $this->enqueueLifecycleAction($service['service'], 'ssl/reissue', $options, 2);
    }

    public function enqueueCancel($idService, array $options = array())
    {
        $service = $this->sslService($idService, 'SSL cancel icin servis kaydi bulunamadi.');
        if (empty($service['success'])) {
            return $service;
        }

        return $this->enqueueLifecycleAction($service['service'], 'ssl/cancel', $options, 2);
    }

    public function enqueueDetails($idService, array $options = array())
    {
        $service = $this->sslService($idService, 'SSL details icin servis kaydi bulunamadi.');
        if (empty($service['success'])) {
            return $service;
        }

        return $this->enqueueLifecycleAction($service['service'], 'ssl/details', $options, 3);
    }

    public function enqueueDownload($idService, array $options = array())
    {
        $service = $this->sslService($idService, 'SSL download icin servis kaydi bulunamadi.');
        if (empty($service['success'])) {
            return $service;
        }

        return $this->enqueueLifecycleAction($service['service'], 'ssl/download', $options, 3);
    }

    public function enqueueValidationStatus($idService, array $options = array())
    {
        $service = $this->sslService($idService, 'SSL validation status icin servis kaydi bulunamadi.');
        if (empty($service['success'])) {
            return $service;
        }

        return $this->enqueueLifecycleAction($service['service'], 'ssl/validation_status', $options, 3);
    }

    protected function sslService($idService, $message)
    {
        $service = NtRcServiceRepository::getService((int)$idService);
        if (!$service || $service['service_type'] !== 'ssl') {
            return array('success' => false, 'message' => $message);
        }

        return array('success' => true, 'service' => $service);
    }

    protected function enqueueLifecycleAction(array $service, $action, array $options, $priority)
    {
        return NtRcOperationQueueManager::enqueue(
            self::PROVIDER_CODE,
            'ssl',
            $action,
            $this->buildLifecyclePayload($service, $options),
            isset($service['id_order']) ? (int)$service['id_order'] : null,
            isset($service['id_customer']) ? (int)$service['id_customer'] : null,
            isset($service['id_ntresellerclub_service']) ? (int)$service['id_ntresellerclub_service'] : null,
            3,
            $priority
        );
    }

    protected function createSslService(Order $order, $idProduct, $domainName, array $mapping)
    {
        $billingCycle = isset($mapping['billing_cycle']) ? $mapping['billing_cycle'] : 'yearly';
        $ok = Db::getInstance()->insert('ntresellerclub_service', array(
            'id_customer' => (int)$order->id_customer,
            'id_order' => (int)$order->id,
            'id_product' => (int)$idProduct,
            'provider_code' => pSQL(self::PROVIDER_CODE),
            'service_type' => pSQL('ssl'),
            'domain_name' => $domainName !== '' ? pSQL($domainName) : null,
            'provider_service_id' => null,
            'provider_order_id' => null,
            'provider_customer_id' => null,
            'provider_contact_id' => null,
            'ssl_certificate_number' => null,
            'start_date' => date('Y-m-d'),
            'expiry_date' => $this->expiryFromBillingCycle($billingCycle),
            'status' => pSQL('provisioning'),
            'currency' => !empty($mapping['currency']) ? pSQL($mapping['currency']) : null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ));

        return $ok ? (int)Db::getInstance()->Insert_ID() : 0;
    }

    protected function expiryFromBillingCycle($billingCycle)
    {
        $map = array(
            'yearly' => '+1 year',
            'biennial' => '+2 years',
            'triennial' => '+3 years',
        );
        $billingCycle = NtRcSslProductMappingManager::normalizeBillingCycle($billingCycle);
        return date('Y-m-d', strtotime(isset($map[$billingCycle]) ? $map[$billingCycle] : '+1 year'));
    }

    protected function buildCreatePayload(Order $order, array $product, array $mapping, $idService, $domainName, $providerCustomerId)
    {
        $payload = NtRcSslProductMappingManager::payloadFromMapping($mapping, array(
            'product_reference' => isset($product['product_reference']) ? $product['product_reference'] : '',
            'product_name' => isset($product['product_name']) ? $product['product_name'] : '',
        ));

        $payload['id_order'] = (int)$order->id;
        $payload['id_customer'] = (int)$order->id_customer;
        $payload['customer-id'] = $providerCustomerId;
        $payload['provider_customer_id'] = $providerCustomerId;
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
            'ssl_certificate_number' => isset($service['ssl_certificate_number']) ? $service['ssl_certificate_number'] : '',
            'expiry_date' => isset($service['expiry_date']) ? $service['expiry_date'] : '',
        ), $this->safeOptions($options));
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
                'ssl_payment_required:' . (int)$service['id_ntresellerclub_service'] . ':' . date('Y-m-d')
            );
        } catch (Exception $e) {
            NtRcLog::add('warning', 'ssl_provisioning', 'Payment required notification failed service=' . (int)$service['id_ntresellerclub_service'] . ' ' . $this->safeText($e->getMessage()));
        }
    }

    protected function recordPaymentRequired(array $service)
    {
        NtRcBillingEventManager::record(
            'renewal_payment_required',
            'payment_required',
            array(
                'id_order' => !empty($service['id_order']) ? (int)$service['id_order'] : null,
                'id_customer' => !empty($service['id_customer']) ? (int)$service['id_customer'] : null,
                'id_service' => !empty($service['id_ntresellerclub_service']) ? (int)$service['id_ntresellerclub_service'] : null,
                'provider_code' => self::PROVIDER_CODE,
                'service_type' => 'ssl',
            ),
            'Odeme alinmadan SSL renew provider queue olusturulmadi.',
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
            if (stripos($value, 'SSL:') === 0) {
                $value = substr($value, 4);
            }
            if (preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/i', $value)) {
                return strtolower($value);
            }
        }

        return '';
    }

    protected function safeOptions(array $options)
    {
        foreach (array('csr', 'private_key', 'private-key', 'certificate', 'certificate_raw', 'cert_raw', 'api-key', 'api_key', 'password', 'token', 'credential', 'auth-code', 'auth_code') as $key) {
            if (isset($options[$key])) {
                unset($options[$key]);
            }
        }
        return $options;
    }

    protected function safeText($text)
    {
        return preg_replace('/(api-key|api_key|auth-code|auth_code|passwd|password|token|credential|csr|private_key|private-key|certificate|certificate_raw|cert_raw)=([^&\s]+)/i', '$1=***', (string)$text);
    }
}
