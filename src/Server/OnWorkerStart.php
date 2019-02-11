<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/5/16
 * Time: 17:37
 */

namespace YX\App\Server;


/**
 * Class OnWorkerStart
 *
 * @package YX\App\Server
 */
class OnWorkerStart extends Listener
{
    protected function do($args)
    {
        /** @var \swoole_server $server */
        $server = $args[0];

        $this->logger->debug('process id', ['worker_pid' => $server->worker_pid]);


    }
}