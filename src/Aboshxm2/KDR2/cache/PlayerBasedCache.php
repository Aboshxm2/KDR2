<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\cache;

use Aboshxm2\KDR2\Main;
use pocketmine\Server;

class PlayerBasedCache implements Cache
{
    protected array $storage = [];

    public function __construct(
        private Main $plugin
    ){}

    public function onJoin(string $playerName): void
    {
        $this->plugin->getDatabase()->getAll($playerName, function (int $kills, int $deaths, int $killstreak) use ($playerName): void {
            if(Server::getInstance()->getPlayerExact($playerName) === null) return;// to make sure the player is still online

            $this->storage[$playerName] = [$kills, $deaths, $killstreak];
        });
    }

    public function onLeave(string $playerName)
    {
        if(isset($this->storage[$playerName])) {
            unset($this->storage[$playerName]);
        }
    }

    public function get(string $playerName): ?array
    {
        return $this->storage ?? null;
    }

    public function set(string $playerName, array $data): void
    {
        if(Server::getInstance()->getPlayerExact($playerName) === null) return;

        $this->storage[$playerName] = $data;
    }
}