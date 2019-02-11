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
use YX\Crawler\DataEncoder;
use YX\Crawler\Response\ImageResponse;
use YX\Crawler\Response\VideoResponse;
use YX\Crawler\Task\ImageTask;
use YX\Crawler\Task\TiktokTask;
use YX\Crawler\Task\YoutubeTask;
use YX\Crawler\UploadClient;

/**
 * Class AdminLogsConsumerTask
 *
 * @property-read  Logger $logger
 */
class Client
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
        $this->server = new \swoole_server("0.0.0.0", 9513);
        $this->server->set(['worker_num' => 1]);
        $this->logger->debug('start server ...', ['options' => $this->options]);

        $this->logger = $this->logger->withName('client');
        $this->server->on('receive', function () { });
        $this->server->on('managerStart', function () { });
        $this->server->on(
            'workerStart', function (\Swoole\Server $server) {
            $dataEncoder = new DataEncoder('r3t5@43f%!^dd');
            $options     = [
//                'host'          => 'proxy1',
//                'port'          => 9501,
                'host'          => '0.0.0.0',
                'port'          => 9511,
                'try_max_count' => 3,
                'settings'      => [
                    'open_eof_check' => true,
                    'package_eof'    => "\n",
                ]
            ];
            $client      = new UploadClient($options, $dataEncoder, $this->logger);

//            $task = new YoutubeTask('Sprp3dt8tu0', 18);
            $task = new TiktokTask(
                "http://hb-redpack.bbobo.com/video/yb/20180816/61190f10f235244f5074db97ce7d25cf.mp4"
            );
            // 单独图片任务
            $task = new ImageTask(
                'test', 'http://fanyi.bdstatic.com/static/translation/img/header/logo_cbfea26.png'
            );
            $task->setTimeout(60)
                ->setIsInterceptCover(true);

            try {
                $response = $client->doTask($task);
                if ($response instanceof ImageResponse) {
                    $image = $response->getImage();
                    $this->logger->info(
                        'image', [
                            'url'    => $image->getUrl(),
                            'width'  => $image->getWidth(),
                            'height' => $image->getHeight(),
                            'size'   => $image->getObjectSize(),
                            'md5'    => $image->getMd5Value()
                        ]
                    );
                } elseif ($response instanceof VideoResponse) {
                    $video = $response->getVideo();
                    $this->logger->info(
                        'video', [
                            'url'      => $video->getUrl(),
                            'md5'      => $video->getMd5Value(),
                            'duration' => $video->getDuration()
                        ]
                    );
                }

            } catch (\Exception $e) {
                $this->logger->debug(
                    'response', [
                        'msg' => $e->getMessage()
                    ]
                );
            }
        }
        );

        $this->server->on(
            'finish', function () {

        }
        );
        $this->server->start();
    }
}

