<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/8/24
 * Time: 09:21
 */

namespace YX\App\Core\Logger;

use Psr\Log\AbstractLogger;

class DebugLogger extends AbstractLogger
{
    public function log($level, $message, array $context = array())
    {
        echo "[{$level}] {$message} " . json_encode($context) . "\n";
    }

}