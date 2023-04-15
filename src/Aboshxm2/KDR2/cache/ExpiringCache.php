<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\cache;

class ExpiringCache implements Cache
{
    protected array $storage = [];
    protected array $expiration = [];

    public function __construct(
        protected int $ttl
    ) {
    }

    public function set(string $playerName, array $data): void
    {
        $this->storage[$playerName] = $data;
        $this->expiration[$playerName] = time() + $this->ttl;
    }

    public function get(string $playerName): ?array
    {
        if(!isset($this->storage[$playerName])) {
            return null;
        }
        if(!isset($this->expiration[$playerName])) {
            return null;
        }// should not happen

        if($this->expiration[$playerName] < time()) {
            unset($this->storage[$playerName]);
            unset($this->expiration[$playerName]);

            return null;
        }

        return $this->storage[$playerName];
    }
}
