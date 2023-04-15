<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2;

use Aboshxm2\KDR2\cache\PlayerBasedCache;
use Aboshxm2\KDR2\events\PlayerDeathsUpdateEvent;
use Aboshxm2\KDR2\events\PlayerKillstreakUpdateEvent;
use Aboshxm2\KDR2\events\PlayerKillsUpdateEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\Server;

class EventListener implements Listener
{
    public array $lastHits = [];

    public function __construct(
        private Main $plugin
    ) {
    }

    /**
     * @priority MONITOR
     */
    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();

        Api::getAll($player->getName(), function (int $kills, int $deaths, int $killstreak) use ($player) {
            $ev = new PlayerDeathsUpdateEvent($player, $deaths + 1);
            $ev->call();

            $ev2 = new PlayerKillstreakUpdateEvent($player, 0);
            $ev2->call();

            if (!$ev->isCancelled() and !$ev2->isCancelled()) {
                Api::setAll($player->getName(), $kills, $ev->getDeaths(), $ev2->getKillstreak());
            } elseif(!$ev->isCancelled()) {
                Api::setAll($player->getName(), $kills, $ev->getDeaths(), $killstreak);
            } elseif(!$ev2->isCancelled()) {
                Api::setAll($player->getName(), $kills, $deaths, $ev2->getKillstreak());
            }
        });

        if (isset($this->lastHits[$player->getName()])) {
            $attackerName = $this->lastHits[$player->getName()];

            if (($attacker = Server::getInstance()->getPlayerExact($attackerName)) instanceof Player) {
                Api::getAll($attacker->getName(), function (int $kills, int $deaths, int $killstreak) use ($attacker) {
                    $ev = new PlayerKillsUpdateEvent($attacker, $kills + 1);
                    $ev->call();

                    $ev2 = new PlayerKillstreakUpdateEvent($attacker, $killstreak + 1);
                    $ev2->call();

                    if (!$ev->isCancelled() and !$ev2->isCancelled()) {
                        Api::setAll($attacker->getName(), $ev->getKills(), $deaths, $ev2->getKillstreak());
                    } elseif(!$ev->isCancelled()) {
                        Api::setAll($attacker->getName(), $ev->getKills(), $deaths, $killstreak);
                    } elseif(!$ev2->isCancelled()) {
                        Api::setAll($attacker->getName(), $kills, $deaths, $ev2->getKillstreak());
                    }
                });
            }
        }
    }

    /**
     * @priority MONITOR
     */
    public function onHit(EntityDamageByEntityEvent $event): void
    {
        $player = $event->getEntity();
        if (!$player instanceof Player) {
            return;
        }

        $attacker = $event->getDamager();
        if (!$attacker instanceof Player) {
            return;
        }

        $this->lastHits[$player->getName()] = $attacker->getName();
    }

    /**
     * @priority MONITOR
     */
    public function onHealthRegenerate(EntityRegainHealthEvent $event): void
    {
        $player = $event->getEntity();
        if (!$player instanceof Player) {
            return;
        }

        if (($player->getHealth() + $event->getAmount()) >= 20) {
            if (isset($this->lastHits[$player->getName()])) {
                unset($this->lastHits[$player->getName()]);
            }
        }
    }

    /**
     * @priority MONITOR
     */
    public function onQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        if (isset($this->lastHits[$player->getName()])) {
            unset($this->lastHits[$player->getName()]);
        }

        if($this->plugin->isCacheEnabled()) {
            $cache = $this->plugin->getCache();
            if($cache instanceof PlayerBasedCache) {
                $cache->onLeave($player->getName());
            }
        }
    }

    /**
     * @priority MONITOR
     */
    public function onJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();

        $this->plugin->updateScoreHudTags($player);
    }

    /**
     * @priority MONITOR
     */
    public function onLogin(PlayerLoginEvent $event): void
    {
        if(!$this->plugin->isCacheEnabled()) {
            return;
        }

        $player = $event->getPlayer();

        $cache = $this->plugin->getCache();
        if($cache instanceof PlayerBasedCache) {
            $cache->onJoin($player->getName());
        }
    }
}
