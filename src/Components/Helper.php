<?php
/**
 * Created by PhpStorm.
 * User: zhw
 * Date: 2018/8/11
 * Time: 下午6:20
 */

namespace YX\App\Components;

class Helper
{
    public static function convertUnderline($str)
    {
        $str = preg_replace_callback(
            '/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str
        );

        return $str;
    }

    /**
     * convertCamel
     *
     * @author chenmingming
     *
     * @param $str
     *
     * @return string
     */
    static public function convertCamel($str)
    {
        $str = preg_replace_callback(
            '/([A-Z]+)/', function ($matchs) {
            return '_' . strtolower($matchs[0]);
        }, $str
        );

        return trim(preg_replace('/_{2,}/', '_', $str), '_');
    }

    /**
     * convertVideoToArray
     *
     * @author yingjun
     *
     * @param       $object
     * @param array $objectFields
     *
     * @return array
     */
    static public function convertObjectToArray(
        $object, $objectFields = []
    ) {
        $data = [];
        foreach (get_object_vars($object) as $k => $v) {
            $method = 'get' . ucfirst(self::convertUnderline($k));
            if (array_key_exists($k, $objectFields)) {
                if (is_array($v)) {
                    $temp = [];
                    foreach ($v as $item => $value) {
                        $temp[$item] = self::convertObjectToArray(
                            $value, $objectFields[$k]
                        );
                    }
                    $data[self::convertCamel($k)] = $temp;
                } else {
                    if (method_exists($object, $method)) {
                        $data[self::convertCamel($k)]
                            = self::convertObjectToArray(
                            $object->$method(), $objectFields[$k]
                        );
                    } else {
                        $data[self::convertCamel($k)] = null;
                    }
                }
            } else {
                if (method_exists($object, $method)) {
                    $data[self::convertCamel($k)] = $object->$method();
                } else {
                    $data[self::convertCamel($k)] = '';
                }
            }
        }

        return $data;
    }

    /**
     * @param $name
     * @param $value
     * @param $group
     */
    static public function push2Stats($name, $value, $group)
    {
        $time = time();
        $data = "put gc.dev.api {$time} {$value} api={$name} host={$group}\n";
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_sendto($sock, $data, strlen($data), 0, '10.10.9.64', 4244);
        socket_close($sock);
    }
}