<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/8/24
 * Time: 08:27
 */

namespace YX\App\Components\Downloader;

use YX\Crawler\Task\DownloadTaskInterface;

interface DownloaderInterface
{
    /**
     * @param DownloadTaskInterface $task
     *
     * @return string 下载后的文件的本地路径
     */
    public function download(DownloadTaskInterface $task): string;

    public function support(DownloadTaskInterface $task): bool;
}