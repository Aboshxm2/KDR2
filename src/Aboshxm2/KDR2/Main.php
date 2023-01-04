<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2;

use Aboshxm2\KDR2\cache\CacheManager;
use Aboshxm2\KDR2\commands\KDRStatsCommand;
use Aboshxm2\KDR2\database\Database;
use Aboshxm2\KDR2\database\SqlDatabase;
use Ifera\ScoreHud\event\PlayerTagsUpdateEvent;
use Ifera\ScoreHud\scoreboard\ScoreTag;
use Ifera\ScoreHud\ScoreHud;
use JackMD\ConfigUpdater\ConfigUpdater;
use JackMD\UpdateNotifier\UpdateNotifier;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase
{
    public const CONFIG_VERSION = 1;

    private Database $database;
    private CacheManager $cacheManager;

    protected function onEnable(): void
    {
        UpdateNotifier::checkUpdate($this->getName(), $this->getDescription()->getVersion());
        ConfigUpdater::checkUpdate($this, $this->getConfig(), "config-version", self::CONFIG_VERSION);

        $this->database = new SqlDatabase($this, $this->getConfig()->get("database"));
        if ($this->getConfig()->getNested("cache.enable")) {
            $this->cacheManager = new CacheManager($this, $this->getConfig()->getNested("cache.technique"), $this->getConfig()->getNested("cache.ttl"));
        }
        Api::init($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("KDR2", new KDRStatsCommand($this));
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

    /**
     * @return Database
     */
    public function getDatabase(): Database
    {
        return $this->database;
    }

    /**
     * @return CacheManager
     */
    public function getCacheManager(): CacheManager
    {
        return $this->cacheManager;
    }
}
