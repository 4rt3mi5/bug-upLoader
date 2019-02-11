<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/8/24
 * Time: 08:55
 */

namespace YX\App\Components\Downloader;

use Psr\Log\LoggerInterface;

abstract class AbsDownloader implements DownloaderInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var array
     */
    protected $options;

    public function __construct(array $options, LoggerInterface $logger)
    {
        $this->options = array_merge($this->options, $options);
        $this->logger  = $logger;
    }
}