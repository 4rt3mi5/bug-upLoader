<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/5/16
 * Time: 13:12
 */

namespace YX\App;

use Monolog\Logger;
use Slim\Container;
use YX\App\Server\OnFinish;
use YX\App\Server\OnReceive;
use YX\App\Server\OnManagerStart;
use YX\App\Server\OnWorkerStart;

/**
 * Class AdminLogsConsumerTask
 *
 * @property-read  Logger $logger
 */
class Server
{
    private $container;
    /** @var \swoole_server */
    public $server;

    public $options
        = [
            'server' => [
                'worker_num' => 5,    //worker process num
            ]
        ];

    /**
     * Server constructor.
     *
     * @param Container $container
     *
     * @throws \Interop\Container\Exception\ContainerException
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->options   = array_merge($this->options, $container->get('settings')['task'] ?? []);
    }

    public function __get($property)
    {
        if ($this->container->{$property}) {
            return $this->container->{$property};
        }

        return null;
    }

    public function run()
    {
        $_SERVER['x_token'] = uniqid();
        $this->server       = new \swoole_server("0.0.0.0", 9511);
        $this->server->set($this->options['server']);
        $this->logger->debug('start server ...', ['options' => $this->options]);
        $this->server->on('receive', new OnReceive($this));
        $this->server->on('managerStart', new OnManagerStart($this));
        $this->server->on('workerStart', new OnWorkerStart($this));

        $this->server->on('finish', new OnFinish($this));
        $this->server->start();
    }
}

