<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/5/13
 * Time: 13:19
 */

namespace YX\App\Core\Logger;

use Monolog\Handler\RotatingFileHandler;

class BBgcStreamHandler extends RotatingFileHandler
{
    protected function streamWrite($stream, array $record)
    {
        fwrite($stream, (string)$record['message']);
    }
}