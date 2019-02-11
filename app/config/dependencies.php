<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/4/23
 * Time: 11:15
 */

// logger.processors
$container['logger.processors'] = function () {
    return [
        new \YX\App\Core\Logger\SwUidProcessor()
    ];
};
// logger
$container['logger'] = function (\Slim\Container $c) {
    $settings = $c->get('settings')['logger'];
    $logger   = new Monolog\Logger($settings['name']);
    if ($c->has('logger.processors')) {
        foreach ($c->get('logger.processors') as $processor) {
            $logger->pushProcessor($processor);
        }
    }
    $logger->pushHandler(
        new Monolog\Handler\RotatingFileHandler(
            $settings['path'], 0, $settings['level']
        )
    );

    return $logger;
};

// UploadManager
$container['oss'] = function (\Slim\Container $container) {
    $settings = $container->get('settings')['oss'] ?? [];
    if (empty($settings)) {
        throw new InvalidArgumentException('oss 配置不存在');
    }
    $client = new \YX\App\Components\AliyunOSS\Client(
        $settings, $container->get('logger'), $container->get('stopwatch')
    );

    return $client;
};

$container['downloader'] = function (\Slim\Container $container) {

    $settings = $container->get('settings')['downloader'] ?? [];
    $download = new \YX\App\Components\Downloader\Downloader(
        $settings, $container->get('logger'), $container->get('stopwatch')
    );
    $youtubeDownloader = new \YX\App\Components\Downloader\YoutubeDownloader(
        $settings, $container->get('logger')
    );
    $commonDownloader = new \YX\App\Components\Downloader\CommonDownloader(
        $settings, $container->get('logger')
    );

    $download->addDownloader($youtubeDownloader);
    $download->addDownloader($commonDownloader);

    return $download;
};

$container['ffmpeg'] = function (\Slim\Container $container) {
    return new \YX\App\Components\FFmpeg($container->get('logger'), $container->get('stopwatch'));
};

$container['dataEncoder'] = function (\Slim\Container $container) {

    return new \YX\Crawler\DataEncoder($container->get('settings')['secret']);
};

$container['stopwatch'] = function (\Slim\Container $container) {
    return new \Symfony\Component\Stopwatch\Stopwatch(true);
};

$container['proxyProvider'] = function (\Slim\Container $container) {
    return new \YX\App\Components\ProxyFactory($container->get('settings')['proxies']);
};