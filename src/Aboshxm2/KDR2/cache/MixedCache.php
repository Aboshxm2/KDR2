<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\cache;

use Aboshxm2\KDR2\Main;
use pocketmine\Server;

class MixedCache extends PlayerBasedCache implements Cache
{
    protected array $expiration = [];

    public function __construct(
        Main $plugin,
        protected int $ttl
    ){
        parent::__construct($plugin);
    }


    public function set(string $playerName, array $data): void
    {
        $this->storage[$playerName] = $data;

        if(Server::getInstance()->getPlayerExact($playerName) === null) {
            // if the player is not online then use the expiring technique
            $this->expiration[$playerName] = time() + $this->ttl;
        }
    }

    public function get(string $playerName): ?array
    {
        if(!isset($this->storage[$playerName])) return null;

        if(Server::getInstance()->getPlayerExact($playerName) !== null) {
            return $this->storage[$playerName];
        }

        if(!isset($this->expiration[$playerName])) return null;// should not happen

        if($this->expiration[$playerName] < time()) {
            unset($this->storage[$playerName]);
            unset($this->expiration[$playerName]);

            return null;
        }

        return $this->storage;
    }
}