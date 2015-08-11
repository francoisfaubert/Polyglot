<?php
namespace Polyglot\Plugin\Db;

class Cache  {

    private $cacheDump = array();

    public function has($longKey)
    {
        return array_key_exists(md5($longKey), $this->cacheDump);
    }

    public function get($longKey)
    {
        return $this->cacheDump[md5($longKey)];
    }

    public function set($longKey, $value)
    {
        $this->cacheDump[md5($longKey)] = $value;
    }

}
