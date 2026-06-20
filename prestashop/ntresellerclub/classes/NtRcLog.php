<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class NtRcLog
{
    public static function add($level, $context, $message)
    {
        return Db::getInstance()->insert('ntresellerclub_log', array(
            'level' => pSQL($level),
            'context' => pSQL($context),
            'message' => pSQL($message),
            'created_at' => date('Y-m-d H:i:s'),
        ));
    }
}
