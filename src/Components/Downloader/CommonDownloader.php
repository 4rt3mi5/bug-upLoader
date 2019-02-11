<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/8/24
 * Time: 08:23
 */

namespace YX\App\Components\Downloader;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use YX\Crawler\Task\CommonTask;
use YX\Crawler\Task\DownloadTaskInterface;
use YX\Crawler\Task\ImageTask;

class CommonDownloader extends AbsDownloader
{

    protected $options
        = [
            'save_path' => '/dev/shm',
        ];

    /**
     * @var Client
     */
    private $client;

    public function __construct(array $options, LoggerInterface $logger)
    {
        parent::__construct($options, $logger);
        $this->client = new Client();
    }

    /**
     * download
     *
     * @author chenmingming
     *
     * @param DownloadTaskInterface $task
     *
     * @return string
     */
    public function download(DownloadTaskInterface $task): string
    {
        if (!$task instanceof CommonTask) {
            throw new \InvalidArgumentException('expect arg is common task. but get ' . get_class($task));
        }
        $url       = $task->getUrl();
        $extension = $task instanceof ImageTask ? 'jpg' : 'mp4';
        $savePath  = $this->options['save_path'] . '/' . uniqid() . '.' . $extension;
        $options   = [
            'save_to' => $savePath,
            'timeout' => $task->getTimeout(),
        ];
        if ($proxy = $task->getProxy()) {
            $options['proxy'] = $proxy;
        }

        $this->logger->info('request video by url', ['url' => $url, 'options' => $options]);
        try {

            $response = $this->client->get($url, $options);
            if ($response->getStatusCode() != 200) {
                file_exists($savePath) && unlink($savePath);
                $this->logger->error('download resource failed. response status code is not 200');
                throw new \RuntimeException("download resource failed");
            }
            $this->logger->info('download resource success');

            return $savePath;
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function support(DownloadTaskInterface $task): bool
    {
        return $task instanceof CommonTask;
    }

}