<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/8/24
 * Time: 14:49
 */

namespace YX\App\Components;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class FFmpeg
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(Logger $logger, Stopwatch $stopwatch)
    {
        $this->logger    = $logger->withName('ffmpeg');
        $this->stopwatch = $stopwatch;
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function interceptCover($path)
    {
        $array     = explode(".", $path);
        $ext       = end($array);
        $imagePath = substr($path, 0, -strlen($ext)) . "jpg";
        $cmd       = "ffmpeg -i {$path} -vframes 1 {$imagePath} &";

        $this->logger->debug('cmd ' . $cmd);
        $this->stopwatch->start('ffmpeg.cover');

        exec($cmd, $output, $returnVar);

        $this->stopwatch->stop('ffmpeg.cover');
        if ($returnVar != 0) {
            throw new \RuntimeException("intercept cover failed");
        }

        return $imagePath;
    }

    /**
     * @param $path
     */
    public function clearMetadata($path)
    {
        $array   = explode(".", $path);
        $ext     = end($array);
        $tmpPath = dirname($path) . '/' . uniqid('format_') . '.' . $ext;
        $cmd     = "ffmpeg -i {$path} -map_metadata -1 -c:v copy -c:a copy {$tmpPath}";
        $this->logger->debug('exec cmd:' . $cmd);
        $this->stopwatch->start('ffmpeg.clear-metadata');

        exec($cmd, $output, $returnVar);
        $this->stopwatch->stop('ffmpeg.clear-metadata');
        if ($returnVar != 0) {
            throw new \RuntimeException("clear metadata falied");
        }
        if (!rename($tmpPath, $path)) {
            throw new \RuntimeException("rename file failed");
        }
    }

    /**
     * @param $path
     *
     * @return array
     */
    public function getVideoDetail($path)
    {
        $cmd = "ffprobe -v quiet -print_format json -show_format -show_streams {$path}";

        $this->stopwatch->start('ffmpeg.detail');
        exec($cmd, $json, $returnVar);
        $this->stopwatch->stop('ffmpeg.detail');

        if ($returnVar != 0) {
            throw new \RuntimeException("ffprobe failed");
        }
        $json   = implode("", $json);
        $detail = json_decode($json, true);

        return $detail;
    }
}