<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\cache;

use Aboshxm2\KDR2\Main;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

class CacheManager
{
    public const TTL_CACHE_TECHNIQUE = "ttl";
    public const PLAYER_CACHE_TECHNIQUE = "player";
    public const MIXED_CACHE_TECHNIQUE = "mixed";

    /**
     * @phpstan-var array<string, array<int, int>>
     */
    private array $cache = [];

    /**
     * @phpstan-var array<string, int>
     */
    private array $ttlCache = [];

    /**
     * @var int $ttl expiration time in seconds
     */
    public int $ttl = 1;

    /**
     * @var string $technique
     */
    private string $technique;


    /**
     * @param Main $plugin
     * @param string $technique
     * @param int|null $ttl
     */
    public function __construct(Main $plugin, string $technique = self::PLAYER_CACHE_TECHNIQUE, ?int $ttl = null)
    {
        $this->technique = $technique;

        if (!in_array($technique, [self::PLAYER_CACHE_TECHNIQUE, self::MIXED_CACHE_TECHNIQUE, self::TTL_CACHE_TECHNIQUE])) {
            throw new \InvalidArgumentException("Invalid technique type.");
        }

        if ($technique === self::TTL_CACHE_TECHNIQUE or $technique === self::MIXED_CACHE_TECHNIQUE) {
            if (!is_numeric($ttl) or $ttl <= 0) {
                throw new \InvalidArgumentException("Invalid ttl in the TTL technique.");
            }
            $this->ttl = $ttl;
            $plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () {
                $now = time();
                foreach ($this->ttlCache as $playerName => $time) {
                    if ($now > $time) {
                        unset($this->ttlCache[$playerName]);
                        if (isset($this->cache[$playerName])) {
                            unset($this->cache[$playerName]);
                        }
                    }
                }
            }), 20);
        }

        if ($technique === self::PLAYER_CACHE_TECHNIQUE or $technique === self::MIXED_CACHE_TECHNIQUE) {
            $plugin->getServer()->getPluginManager()->registerEvent(PlayerJoinEvent::class, function (PlayerJoinEvent $event) use ($plugin) {
                $player = $event->getPlayer();
                if (isset($this->ttlCache[$player->getName()])) {// mixed technique
                    unset($this->ttlCache[$player->getName()]);
                }

                if (!isset($this->cache[$player->getName()])) {
                    $plugin->getDatabase()->getAll($player->getName(), function (int $kills, int $deaths, int $killstreak) use ($player): void {
                        if (!isset($this->cache[$player->getName()])) {
                            $this->cache[$player->getName()] = [$kills, $deaths, $killstreak];
                        }
                    });
                }
            }, EventPriority::LOWEST, $plugin);

            $plugin->getServer()->getPluginManager()->registerEvent(PlayerQuitEvent::class, function (PlayerQuitEvent $event) {
                $player = $event->getPlayer();
                if (isset($this->cache[$player->getName()])) {
                    unset($this->cache[$player->getName()]);
                }
            }, EventPriority::MONITOR, $plugin);
        }
    }

    public function set(string $playerName, int $kills, int $deaths, int $killstreak): void
    {
        if ($this->technique === self::TTL_CACHE_TECHNIQUE) {
            $this->ttlCache[$playerName] = time() + $this->ttl;
            $this->cache[$playerName] = [$kills, $deaths, $killstreak];
        } elseif ($this->technique === self::PLAYER_CACHE_TECHNIQUE) {
            if (Server::getInstance()->getPlayerExact($playerName) instanceof Player) {// We will not save if the player isn't online in the player technique
                $this->cache[$playerName] = [$kills, $deaths, $killstreak];
            }
        } elseif ($this->technique === self::MIXED_CACHE_TECHNIQUE) {
            $this->cache[$playerName] = [$kills, $deaths, $killstreak];
            if (!Server::getInstance()->getPlayerExact($playerName) instanceof Player) {
                $this->ttlCache[$playerName] = time() + $this->ttl;
            }
        }
    }

    public function get(string $playerName): ?array
    {
        return $this->cache[$playerName] ?? null;
    }
}
