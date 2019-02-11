<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/8/24
 * Time: 09:14
 */

namespace YX\App\Components\Downloader;

use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use YX\Crawler\Task\AbsDownloadTask;

class Downloader
{
    const IMAGE_EXT_ARRAY = ['jpg', 'png', 'jpeg'];
    /**
     * @var DownloaderInterface[]
     */
    private $adapters = [];

    private $options
        = [
            'save_path' => '/dev/shm',
        ];

    private $logger;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(array $options, LoggerInterface $logger, Stopwatch $stopwatch)
    {
        $this->options   = array_merge($this->options, $options);
        $this->logger    = $logger;
        $this->stopwatch = $stopwatch;
    }

    public function addDownloader(DownloaderInterface $downloader)
    {
        $this->adapters[] = $downloader;
    }

    /**
     * @param AbsDownloadTask $task
     *
     * @return string
     */
    public function download(AbsDownloadTask $task)
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->support($task)) {
                $this->stopwatch->start('download.resource');
                $path = $adapter->download($task);
                $this->stopwatch->stop('download.resource');

                return $path;
            }
        }
        throw new \InvalidArgumentException("no downloader for this task ." . $task->getPlatform());
    }

    public function downloadRemoteCover(AbsDownloadTask $task)
    {
        if (empty($task->getRemoteCoverUrl())) {
            return '';
        }
        $cover    = $task->getRemoteCoverUrl();
        $extArray = explode(".", $cover);
        $ext      = end($extArray);
        if (!in_array($ext, self::IMAGE_EXT_ARRAY)) {
            $ext = 'jpg';
        }

        $path = $this->options['save_path'] . '/' . uniqid('cover') . '.' . $ext;

        $cmd = "curl -o {$path}";
        if ($task->getProxy()) {
            $cmd .= " --proxy http://{$task->getProxy()} ";
        }
        $cmd .= ' "' . $cover . '"';

        $this->logger->info("exec " . $cmd);

        $this->stopwatch->start('download.cover');

        exec($cmd, $output, $returnVar);
        $this->stopwatch->stop('download.cover');
        if ($returnVar > 0) {
            throw new \RuntimeException("下载封面图片失败.");
        }

        return $path;
    }
}