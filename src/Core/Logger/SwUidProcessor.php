<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/4/23
 * Time: 16:47
 */

namespace YX\App\Core\Logger;

class SwUidProcessor
{
    public function __invoke(array $record)
    {
        $record['extra']['uuid'] = $_SERVER['uuid'] ?? '';
        $record['extra']['pid']  = $_SERVER['x_token'] ?? uniqid();

        return $record;
    }

}