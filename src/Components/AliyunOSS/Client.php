<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/8/29
 * Time: 15:30
 */

namespace YX\App\Components\AliyunOSS;

use OSS\OssClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use YX\Crawler\File\AbstractOssObject;
use YX\Crawler\File\ExtensionGuesser;
use YX\Crawler\File\ImageObject;
use YX\Crawler\File\VideoObject;

class Client
{
    private $options
        = [
            'access_key_id'     => '',
            'access_key_secret' => '',
            'end_point'         => '',
            'bucket'            => '',
            'domain'            => '',
            'upload_dir'        => 'crawler',
        ];
    /**
     * @var OssClient
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var ExtensionGuesser
     */
    private $extensionGuesser;

    /**
     * OssUploader constructor.
     *
     * @param array           $options
     *
     * @param LoggerInterface $logger
     *
     * @param Stopwatch       $stopwatch
     *
     * @throws \OSS\Core\OssException
     */
    public function __construct($options, LoggerInterface $logger, Stopwatch $stopwatch)
    {
        $this->options          = array_merge($this->options, $options);
        $this->client           = new OssClient(
            $this->options['access_key_id'],
            $this->options['access_key_secret'],
            $this->options['end_point']
        );
        $this->logger           = $logger;
        $this->stopwatch        = $stopwatch;
        $this->extensionGuesser = new ExtensionGuesser();
    }

    /**
     * @param string $filePath
     * @param string $dir
     *
     * @return AbstractOssObject
     * @throws \OSS\Core\OssException
     */
    public function upload(string $filePath, string $dir = '')
    {
        $extension  = $this->extensionGuesser->guessExtension($filePath);
        $object     = $this->createObject($filePath, $extension);
        $objectPath = $this->options['upload_dir'] . "/{$dir}/"
            . substr($object->getMd5Value(), 0, 2) . '/'
            . substr($object->getMd5Value(), 2, 2) . "/"
            . $object->getMd5Value() . "." . $extension;

        $this->logger->info('prepare upload file', ['path' => $filePath, 'object' => $objectPath]);
        $this->stopwatch->start('upload.' . $object->getObjectType());
        $this->client->uploadFile($this->options['bucket'], $objectPath, $filePath);
        $this->stopwatch->stop('upload.' . $object->getObjectType());

        return $object->setUrl("http://" . $this->options['domain'] . '/' . $objectPath);
    }

    /**
     * @param string $filePath
     *
     * @param string $extension
     *
     * @return AbstractOssObject
     */
    private function createObject(string $filePath, string $extension)
    {
        $md5Value = md5_file($filePath);
        switch ($extension) {
        case 'mp4':
            $object = new VideoObject();
            break;
        case 'png':
        case 'jpg':
        case 'gif':
            $object = new ImageObject();
            list($width, $height) = getimagesize($filePath);
            $object->setWidth($width)
                ->setHeight($height);
            break;
        default:
            throw new \UnexpectedValueException("do not support this extension file to upload. [{$extension}]");
        }
        $object->setMd5Value($md5Value)
            ->setObjectSize(filesize($filePath));

        return $object;
    }

}