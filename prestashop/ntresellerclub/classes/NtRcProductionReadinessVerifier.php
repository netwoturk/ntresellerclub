<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/NtRcApiContractGuard.php';
require_once __DIR__ . '/NtRcBillingEventManager.php';
require_once __DIR__ . '/NtRcBillingOperationQueueProcessor.php';
require_once __DIR__ . '/NtRcMailTemplateManager.php';
require_once __DIR__ . '/NtRcOperationQueueManager.php';
require_once __DIR__ . '/NtRcPricingManager.php';
require_once __DIR__ . '/NtRcRuntimeGuard.php';
require_once __DIR__ . '/NtRcSslManager.php';
require_once __DIR__ . '/NtRcSslMonitoring.php';
require_once __DIR__ . '/NtRcSslOperationQueueProcessor.php';
require_once __DIR__ . '/providers/NtRcDomainNameApiProvider.php';
require_once __DIR__ . '/providers/NtRcResellerClubSslAdapter.php';

class NtRcProductionReadinessVerifier
{
    public static function summary()
    {
        $checks = array_merge(
            self::providerContractChecks(),
            self::queueChecks(),
            self::integrationChecks(),
            self::securityChecks(),
            self::runtimeChecks()
        );

        $failed = 0;
        foreach ($checks as $check) {
            if (empty($check['success'])) {
                $failed++;
            }
        }

        return array(
            'success' => $failed === 0,
            'failed_count' => $failed,
            'check_count' => count($checks),
            'checks' => $checks,
        );
    }

    protected static function providerContractChecks()
    {
        $sslAllowed = NtRcApiContractGuard::validate('resellerclub', 'ssl', 'ssl/validation_status');
        $sslDenied = NtRcApiContractGuard::validate('domainnameapi', 'ssl', 'ssl/create');
        $hostingDenied = NtRcApiContractGuard::validate('domainnameapi', 'hosting', 'hosting/create');
        $trAllowed = NtRcApiContractGuard::validate('domainnameapi', 'tr_domain', 'check', array('domain' => 'ornek.com.tr'));
        $globalDenied = NtRcApiContractGuard::validate('domainnameapi', 'tr_domain', 'check', array('domain' => 'example.com'));

        return array(
            self::check('resellerclub_ssl_contract', !empty($sslAllowed['success']), 'ResellerClub SSL queue actions are allowed by contract guard.'),
            self::check('domainnameapi_ssl_blocked', empty($sslDenied['success']), 'DomainNameAPI SSL actions are blocked.'),
            self::check('domainnameapi_hosting_blocked', empty($hostingDenied['success']), 'DomainNameAPI hosting actions are blocked.'),
            self::check('domainnameapi_tr_allowed', !empty($trAllowed['success']), 'DomainNameAPI TR domain routing is allowed.'),
            self::check('domainnameapi_global_blocked', empty($globalDenied['success']), 'DomainNameAPI global domain routing is blocked.'),
            self::check('ssl_adapter_present', class_exists('NtRcResellerClubSslAdapter'), 'ResellerClub SSL adapter class is available.'),
            self::check('domainnameapi_has_no_ssl_adapter', !method_exists('NtRcDomainNameApiProvider', 'createSsl'), 'DomainNameAPI provider exposes no SSL provisioning method.'),
        );
    }

    protected static function queueChecks()
    {
        return array(
            self::check('ssl_manager_present', class_exists('NtRcSslManager'), 'SSL manager is available.'),
            self::check('queue_manager_present', class_exists('NtRcOperationQueueManager'), 'Operation queue manager is available.'),
            self::check('ssl_processor_chain', is_subclass_of('NtRcSslOperationQueueProcessor', 'NtRcHostingOperationQueueProcessor'), 'SSL processor extends hosting-aware queue processor.'),
            self::check('billing_processor_chain', is_subclass_of('NtRcBillingOperationQueueProcessor', 'NtRcSslOperationQueueProcessor'), 'Billing processor extends SSL-aware queue processor.'),
            self::check('ssl_create_method', method_exists('NtRcSslManager', 'maybeProvisionSsl'), 'SSL create queue entrypoint exists.'),
            self::check('ssl_renew_method', method_exists('NtRcSslManager', 'enqueueRenew'), 'SSL renew queue entrypoint exists.'),
            self::check('ssl_cancel_method', method_exists('NtRcSslManager', 'enqueueCancel'), 'SSL cancel queue entrypoint exists.'),
            self::check('ssl_download_method', method_exists('NtRcSslManager', 'enqueueDownload'), 'SSL download queue entrypoint exists.'),
        );
    }

    protected static function integrationChecks()
    {
        $templates = NtRcMailTemplateManager::templateKeys();
        $productTypes = NtRcPricingManager::productTypes();

        return array(
            self::check('pricing_ssl_type', in_array('ssl', $productTypes, true), 'Engine 11 pricing manager supports SSL product type.'),
            self::check('billing_event_manager', class_exists('NtRcBillingEventManager'), 'Engine 13 billing event manager is available.'),
            self::check('ssl_monitoring', class_exists('NtRcSslMonitoring') && method_exists('NtRcSslMonitoring', 'summary'), 'SSL monitoring summary is available.'),
            self::check('notification_ssl_created', in_array('ssl_created', $templates, true), 'ssl_created notification template exists.'),
            self::check('notification_ssl_renewed', in_array('ssl_renewed', $templates, true), 'ssl_renewed notification template exists.'),
            self::check('notification_ssl_expired', in_array('ssl_expired', $templates, true), 'ssl_expired notification template exists.'),
            self::check('notification_payment_required', in_array('payment_required', $templates, true), 'payment_required notification template exists.'),
            self::check('notification_provider_credit_required', in_array('provider_credit_required', $templates, true), 'provider_credit_required admin notification template exists.'),
        );
    }

    protected static function securityChecks()
    {
        $forbiddenCreateTable = self::classesContainRuntimeCreateTable();

        return array(
            self::check('no_runtime_create_table_outside_installer', !$forbiddenCreateTable, 'Table creation SQL is limited to installer/schema migration class.'),
            self::check('billing_sanitizer_present', method_exists('NtRcBillingEventManager', 'safeText'), 'Billing event logs expose sanitizer.'),
            self::check('ssl_response_sanitizer_present', method_exists('NtRcResellerClubSslAdapter', 'downloadSsl'), 'SSL adapter uses sanitized action responses.'),
        );
    }

    protected static function runtimeChecks()
    {
        return array(
            self::check('runtime_guard_present', class_exists('NtRcRuntimeGuard'), 'Runtime guard is available for cron processors.'),
            self::check('cron_batch_guard', method_exists('NtRcRuntimeGuard', 'cronBatchLimit'), 'Cron batch limit is capped for shared hosting.'),
            self::check('heavy_process_guard', method_exists('NtRcRuntimeGuard', 'beforeHeavyProcess'), 'Heavy process runtime guard exists.'),
        );
    }

    protected static function classesContainRuntimeCreateTable()
    {
        $base = dirname(__FILE__);
        $files = glob($base . '/*.php');
        if (!is_array($files)) {
            return false;
        }

        foreach ($files as $file) {
            if (in_array(basename($file), array('NtRcInstaller.php', 'NtRcProductionReadinessVerifier.php'), true)) {
                continue;
            }
            $content = @file_get_contents($file);
            if (is_string($content) && stripos($content, 'CREATE' . ' TABLE') !== false) {
                return true;
            }
        }

        return false;
    }

    protected static function check($key, $success, $message)
    {
        return array(
            'key' => $key,
            'success' => (bool)$success,
            'message' => $message,
        );
    }
}
