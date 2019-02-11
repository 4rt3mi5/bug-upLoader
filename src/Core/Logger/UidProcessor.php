<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/4/23
 * Time: 16:39
 */

namespace YX\App\Core\Logger;

class UidProcessor
{
    private $uid;

    public function __construct($length = 8)
    {
        if (!is_int($length) || $length > 32 || $length < 1) {
            throw new \InvalidArgumentException('The uid length must be an integer between 1 and 32');
        }

        $this->uid = substr(hash('md5', uniqid('', true)), 0, $length);
    }

    public function __invoke(array $record)
    {
        $record['extra']['uid']  = $this->uid;
        $record['extra']['time'] = microtime(true);

        return $record;
    }

    /**
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }
}