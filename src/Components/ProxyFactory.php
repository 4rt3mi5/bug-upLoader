<?php
/**
 * Created by PhpStorm.
 * User: chenmingming
 * Date: 2018/8/29
 * Time: 11:31
 */

namespace YX\App\Components;

class ProxyFactory
{
    private $proxies = [];
    private $count;

    public function __construct(array $proxies)
    {
        $this->proxies = $proxies;
        $this->count   = count($proxies);
    }

    /**
     * @param $id
     *
     * @return string
     */
    public function getProxy($id)
    {
        if ($this->count <= 0) {
            return '';
        }

        return $this->proxies[$id % $this->count] ?? '';
    }
}