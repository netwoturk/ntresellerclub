<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcRuntimeGuard
{
    public static function beforeHeavyProcess($context = 'runtime')
    {
        @ini_set('memory_limit', self::preferredMemoryLimit());
        @set_time_limit((int)self::preferredTimeLimit());
        self::logRuntime($context);
    }

    public static function preferredMemoryLimit()
    {
        return Configuration::get('NTRC_MEMORY_LIMIT') ?: '512M';
    }

    public static function preferredTimeLimit()
    {
        return Configuration::get('NTRC_TIME_LIMIT') ?: 120;
    }

    public static function cronBatchLimit($default = 10)
    {
        $limit = (int)Configuration::get('NTRC_CRON_BATCH_LIMIT');
        if ($limit <= 0) {
            $limit = (int)$default;
        }
        if ($limit > 25) {
            $limit = 25;
        }
        return $limit;
    }

    public static function isCli()
    {
        return PHP_SAPI === 'cli';
    }

    protected static function logRuntime($context)
    {
        if (!class_exists('NtRcLog')) {
            $logFile = _PS_MODULE_DIR_ . 'ntresellerclub/classes/NtRcLog.php';
            if (file_exists($logFile)) {
                require_once $logFile;
            }
        }

        if (class_exists('NtRcLog')) {
            NtRcLog::add('info', 'runtime_guard', $context . ' memory=' . ini_get('memory_limit') . ' max_execution=' . ini_get('max_execution_time'));
        }
    }
}
