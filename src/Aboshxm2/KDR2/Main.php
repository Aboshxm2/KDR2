<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2;

use Aboshxm2\KDR2\cache\Cache;
use Aboshxm2\KDR2\cache\CacheManager;
use Aboshxm2\KDR2\cache\ExpiringCache;
use Aboshxm2\KDR2\cache\MixedCache;
use Aboshxm2\KDR2\cache\PlayerBasedCache;
use Aboshxm2\KDR2\commands\KDRStatsCommand;
use Aboshxm2\KDR2\database\Database;
use Aboshxm2\KDR2\database\SqlDatabase;
use Ifera\ScoreHud\event\PlayerTagsUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;
use Ifera\ScoreHud\ScoreHud;
use JackMD\ConfigUpdater\ConfigUpdater;
use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\player\Player;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase
{
    public const CONFIG_VERSION = 1;

    private Database $database;
    private Cache $cache;
    private bool $isCacheEnabled;

    protected function onEnable(): void
    {
        UpdateNotifier::checkUpdate($this->getName(), $this->getDescription()->getVersion());
        ConfigUpdater::checkUpdate($this, $this->getConfig(), "config-version", self::CONFIG_VERSION);

        $this->database = new SqlDatabase($this, $this->getConfig()->get("database"));
        if ($this->getConfig()->getNested("cache.enable")) {
            $this->isCacheEnabled = true;

            $this->cache = match (strtolower($this->getConfig()->getNested("cache.technique"))) {
                Cache::TTL_CACHE_TECHNIQUE => new ExpiringCache($this->getConfig()->getNested("cache.ttl")),
                Cache::PLAYER_CACHE_TECHNIQUE => new PlayerBasedCache($this),
                Cache::MIXED_CACHE_TECHNIQUE => new MixedCache($this, $this->getConfig()->getNested("cache.ttl")),
                default => throw new DisablePluginException("Unknown cache technique {$this->getConfig()->getNested("cache.technique")}"),
            };
        }
        Api::init($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("KDR2", new KDRStatsCommand($this));
    }

    /**
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * @return Cache
     */
    public function getCache(): Cache
    {
        return $this->cache;
    }

    /**
     * @return bool
     */
    public function isCacheEnabled(): bool
    {
        return $this->isCacheEnabled;
    }

    /**
     * @internal
     */
    public function updateScoreHudTags(Player $player): void
    {
        if(class_exists(ScoreHud::class)) {
            Api::getAll($player->getName(), function (int $kills, int $deaths, int $killstreak) use ($player) {
                if ($deaths === 0) {
                    $kdr = $kills;
                } else {
                    $kdr = $kills / $deaths;
                }

                $kdr = round($kdr, 3);

                $ev = new PlayerTagsUpdateEvent(
                    $player,
                    [
                        new ScoreTag("KDR2.kills", (string)$kills),
                        new ScoreTag("KDR2.deaths", (string)$deaths),
                        new ScoreTag("KDR2.killstreak", (string)$killstreak),
                        new ScoreTag("KDR2.kdr", (string)$kdr)
                    ]
                );
                $ev->call();
            });
        }
    }
}
