<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/8/24
 * Time: 08:23
 */

namespace YX\App\Components\Downloader;

use YX\Crawler\Task\DownloadTaskInterface;
use YX\Crawler\Task\YoutubeTask;

class YoutubeDownloader extends AbsDownloader
{
    protected $options
        = [
            "save_path" => '/dev/shm',
        ];

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
        if (!$task instanceof YoutubeTask) {
            throw new \InvalidArgumentException('expect arg is Youtube task. but get ' . get_class($task));
        }

        $cmd = "you-get  --no-caption --itag={$task->getITag()} ";

        $fileName = $task->getVideoId();

        $cmd .= "-o {$this->options['save_path']} -O {$fileName} ";
        if ($proxy = $task->getProxy()) {
            $cmd .= "-x {$proxy} ";
        }

        $cmd .= 'https://www.youtube.com/watch?v=' . $task->getVideoId();

        $this->logger->info('exec download cmd', ['cmd' => $cmd]);

        exec($cmd, $output, $returnVar);
        if ($returnVar !== 0) {
            $this->logger->error('download video failed', ['output' => $output, 'returnVar' => $returnVar]);
            throw new \RuntimeException("you-get download video failed");
        }

        $this->logger->info("download video success");

        return $this->options['save_path'] . '/' . $fileName . '.mp4';
    }

    public function support(DownloadTaskInterface $task): bool
    {
        return $task instanceof YoutubeTask;
    }

}