<?php

namespace App\Services;

use Redis;

class RedisService
{
    protected Redis $redis;
    protected string $envPrefix;

    public function __construct()
    {
        $host = env('redis.host', 'redis');
        $port = (int) env('redis.port', 6379);
    
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
    
        $this->envPrefix = env('CI_ENVIRONMENT') === 'production' ? 'prod:' : 'dev:';
    }

    public function getPrefix(): string
    {
        return $this->envPrefix;
    }

    public function getKeysWithPrefix(string $prefix): array
    {
        return $this->redis->keys($this->envPrefix . $prefix . '*');
    }

    public function set(string $key, array $data): bool
    {
        return $this->redis->set($this->envPrefix . $key, json_encode($data));
    }

    public function get(string $key): ?array
    {
        $value = $this->redis->get($this->envPrefix . $key);
        return $value ? json_decode($value, true) : null;
    }

    public function delete(string $key): bool
    {
        return $this->redis->del($this->envPrefix . $key) > 0;
    }

    public function exists(string $key): bool
    {
        return $this->redis->exists($this->envPrefix . $key) > 0;
    }
}
