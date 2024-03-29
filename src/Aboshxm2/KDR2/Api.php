<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2;

use pocketmine\player\Player;
use pocketmine\Server;

class Api
{
    private static Main $plugin;

    /**
     * @internal
     */
    public static function init(Main $plugin): void
    {
        self::$plugin = $plugin;
    }

    /**
     * @param string $playerName
     * @param \Closure(int $kills, int $deaths, int $killstreak): void $then
     * @return void
     */
    public static function getAll(string $playerName, \Closure $then): void
    {
        if (self::$plugin->isCacheEnabled()) {
            $data = self::$plugin->getCache()->get($playerName);
            if ($data !== null) {
                $then($data[0], $data[1], $data[2]);
            } else {
                self::$plugin->getDatabase()->getAll($playerName, function (int $kills, int $deaths, int $killstreak) use ($playerName, $then): void {
                    self::$plugin->getCache()->set($playerName, [$kills, $deaths, $killstreak]);

                    $then($kills, $deaths, $killstreak);
                });
            }
        } else {
            self::$plugin->getDatabase()->getAll($playerName, $then);
        }
    }

    /**
     * @param string $playerName
     * @param int $kills
     * @param int $deaths
     * @param int $killstreak
     * @return void
     */
    public static function setAll(string $playerName, int $kills, int $deaths, int $killstreak): void
    {
        if (self::$plugin->isCacheEnabled()) {
            self::$plugin->getCache()->set($playerName, [$kills, $deaths, $killstreak]);
        }

        self::$plugin->getDatabase()->setAll($playerName, $kills, $deaths, $killstreak);

        if (($player = Server::getInstance()->getPlayerExact($playerName)) instanceof Player) {
            self::$plugin->updateScoreHudTags($player);
        }
    }
}
