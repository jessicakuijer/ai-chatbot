<?php

namespace App\Cache;

use BotMan\BotMan\Interfaces\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CustomSymfonyCache implements CacheInterface
{
    private $cache;

    public function __construct()
    {
        $this->cache = new FilesystemAdapter();
    }

    public function has($key)
    {
        return $this->cache->hasItem($key);
    }

    public function get($key, $default = null)
    {
        $item = $this->cache->getItem($key);
        return $item->isHit() ? $item->get() : $default;
    }

    public function put($key, $value, $minutes)
    {
        $item = $this->cache->getItem($key);
        $item->set($value);
        $item->expiresAfter($minutes * 60);
        $this->cache->save($item);
    }

    public function pull($key, $default = null)
    {
        $value = $this->get($key, $default);
        $this->cache->deleteItem($key);
        return $value;
    }
}