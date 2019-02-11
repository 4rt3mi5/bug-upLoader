<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/4/23
 * Time: 12:34
 */

class App
{
    static public function getSetting($name = 'config')
    {
        $projectPath = dirname(__DIR__);
        $dir         = $projectPath . '/app/config/';
        $path        = $dir . $name . '.yml';
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException("配置文件{$path}不存在，或者不可读");
        }
        $content = file_get_contents($path);
        $content = str_replace("%project_path%", $projectPath, $content);

        return \Symfony\Component\Yaml\Yaml::parse($content);
    }

}