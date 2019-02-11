<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/5/16
 * Time: 17:05
 */

namespace YX\App\Server;

use Monolog\Logger;
use OSS\OssClient;
use Slim\Container;
use YX\App\Server;

/**
 * Class Listener
 *
 * @property-read \Redis         $redis
 * @property-read Logger         $logger
 * @property-read array          $options
 * @property-read OssClient      $ossClient
 * @property-read Container      $container
 * @package YX\App\Server
 */
abstract class Listener
{
    /** @var Server $server */
    protected $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function __get($property)
    {
        if ($this->server->{$property}) {
            return $this->server->{$property};
        }
        return null;
    }

    abstract protected function do($args);

    public function __invoke(\swoole_server $server)
    {
        if ($server->taskworker) {
            $_SERVER['x_token'] = "TASKER-" . $server->worker_id;
        } else {
            $_SERVER['x_token'] = "WORKER-" . $server->worker_id;
        }

        $this->logger->debug('trigger ' . str_replace(__NAMESPACE__ . '\\', '', static::class));
        $this->do(func_get_args());
    }
}