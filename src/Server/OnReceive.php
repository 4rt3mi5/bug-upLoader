<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/8/23
 * Time: 23:24
 */

namespace YX\App\Server;

use Monolog\Logger;
use Symfony\Component\Stopwatch\Stopwatch;
use YX\App\Components\AliyunOSS\Client;
use YX\App\Components\Downloader\Downloader;
use YX\App\Components\FFmpeg;
use YX\App\Components\ProxyFactory;
use YX\App\Server;
use YX\Crawler\DataEncoder;
use YX\Crawler\File\ImageObject;
use YX\Crawler\Response\AbstractResponse;
use YX\Crawler\Response\ErrorResponse;
use YX\Crawler\Response\ImageResponse;
use YX\Crawler\Response\VideoResponse;
use YX\Crawler\Task\AbsDownloadTask;
use YX\Crawler\Task\ImageTask;
use YX\Crawler\Video;
use YX\Crawler\VideoCover;

/**
 * Class OnReceive
 *
 * @property-read Logger       $logger
 * @property-read array        $options
 * @property-read Downloader   $downloader
 * @property-read Client       $oss
 * @property-read FFmpeg       $ffmpeg
 * @property-read DataEncoder  $dataEncoder
 * @property-read Stopwatch    $stopwatch
 * @property-read ProxyFactory $proxyProvider;
 * @package YX\App\Server
 */
class OnReceive
{
    /** @var Server $server */
    protected $server;
    /**
     * @var AbstractResponse
     */
    private $response;
    private $requestId;

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

    public function __invoke(\Swoole\Server $server, int $fd, int $reactor_id, string $data)
    {
        $this->requestId = $_SERVER['uuid'] = uniqid();
        try {
            $data = $this->dataEncoder->decode($data);
            $task = unserialize($data);
            if (false === $task || !$task instanceof AbsDownloadTask) {
                $this->logger->error('data is invalid');
                throw new \RuntimeException("send task data is invalid");
            }
            $this->stopwatch->openSection();
            if (!$task->getProxy()) {
                $task->setProxy($this->proxyProvider->getProxy($server->worker_id));
            }
            $filePath = $this->downloader->download($task);

            if (!$task instanceof ImageTask) {
                $this->createVideoResponse($filePath, $task);
            } else {
                $this->createImageResponse($filePath, $task);
            }

        } catch (\Exception $e) {
            $this->response = new ErrorResponse($e->getMessage(), $this->requestId);
        } finally {
            isset($filePath) && file_exists($filePath) && unlink($filePath);

            $sendData = serialize($this->response);
            $sendData = $this->dataEncoder->encode($sendData);
            $server->send($fd, $sendData . PHP_EOL);

        }

        if (!$this->response instanceof ErrorResponse && isset($task)) {
            $this->stopwatch->stopSection($task->getTaskId());
            $costTimes = [];
            foreach ($this->stopwatch->getSectionEvents($task->getTaskId()) as $name => $event) {
                $costTimes[$name] = sprintf('%.6fs', $event->getDuration() / 1000);
            }
            $this->logger->info("task {$task->getTaskId()} success.", ['time' => $costTimes]);
        }

        $this->stopwatch->reset();

    }

    /**
     * @param string          $filePath
     * @param AbsDownloadTask $task
     *
     * @throws \OSS\Core\OssException
     */
    private function createImageResponse(string $filePath, AbsDownloadTask $task)
    {
        $imageObject = $this->oss->upload($filePath, $task->getPlatform() . '_images');
        if (!$imageObject instanceof ImageObject) {
            throw new \UnexpectedValueException("file is not a valid image");
        }
        $this->response = new ImageResponse($imageObject, $this->requestId);
    }

    /**
     * @param string          $filePath
     * @param AbsDownloadTask $task
     *
     * @throws \OSS\Core\OssException
     */
    private function createVideoResponse(string $filePath, AbsDownloadTask $task)
    {
        $this->ffmpeg->clearMetadata($filePath);
        $detail = $this->ffmpeg->getVideoDetail($filePath);
        $cover  = $this->downloader->downloadRemoteCover($task);
        if (empty($cover) && $task->isInterceptCover()) {
            $cover = $this->ffmpeg->interceptCover($filePath);
        }

        $object = $this->oss->upload($filePath, $task->getPlatform());

        $video = new Video();
        $video->setUrl($object->getUrl())
            ->setDuration(round($detail['format']['duration']))
            ->setMd5Value($object->getMd5Value())
            ->setDetail($detail)
            ->setCover($this->dealCover($cover, $task->getPlatform() . "_cover"));

        $this->response = new VideoResponse($video, $this->requestId);
    }

    /**
     * @param $coverPath
     * @param $dir
     *
     * @return null|VideoCover
     * @throws \OSS\Core\OssException
     */
    private function dealCover($coverPath, $dir)
    {
        if (empty($coverPath)) {
            return null;
        }
        $object = $this->oss->upload($coverPath, $dir);

        if (!$object instanceof ImageObject) {
            throw new \UnexpectedValueException("video cover must be a image object.");
        }

        $vCover = new VideoCover();

        $vCover->setUrl($object->getUrl())
            ->setWidth($object->getWidth())
            ->setHeight($object->getHeight())
            ->setMd5Value($object->getMd5Value());

        unlink($coverPath);

        return $vCover;
    }
}

