<?php
namespace Polyglot\Plugin\Db;

class Cache  {

    // public function has($longKey)
    // {
    //     return get_site_transient($this->toKey($longKey)) !== false;
    // }

    // public function get($longKey)
    // {
    //     return get_site_transient($this->toKey($longKey));
    // }

    // public function set($longKey, $value)
    // {
    //     return set_site_transient($this->toKey($longKey), $value);
    // }

    // public function remove($longKey)
    // {
    //     return delete_site_transient($this->toKey($longKey));
    // }

    // private function toKey($longKey)
    // {
    //     return "plglt_" . md5($longKey);
    // }


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

    public function remove($longKey)
    {
        unset($this->cacheDump[md5($longKey)]);
    }

}
