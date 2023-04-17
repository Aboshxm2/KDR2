<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2;

use Aboshxm2\KDR2\cache\Cache;
use Aboshxm2\KDR2\cache\ExpiringCache;
use Aboshxm2\KDR2\cache\MixedCache;
use Aboshxm2\KDR2\cache\PlayerBasedCache;
use Aboshxm2\KDR2\commands\KDRCommand;
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
use pocketmine\utils\Config;
use Symfony\Component\Filesystem\Path;

class Main extends PluginBase
{
    public const CONFIG_VERSION = 2;

    private Database $database;
    private Cache $cache;
    private bool $isCacheEnabled;
    private Config $messages;

    protected function onEnable(): void
    {
        $this->saveResource("messages.yml");
        $this->messages = new Config(Path::join($this->getDataFolder(), "messages.yml"), Config::YAML);

        UpdateNotifier::checkUpdate($this->getName(), $this->getDescription()->getVersion());
        ConfigUpdater::checkUpdate($this, $this->getConfig(), "config-version", self::CONFIG_VERSION);

        $toLowerCase = $this->getConfig()->get("players-names-to-lower-case");

        $this->database = new SqlDatabase($this, $this->getConfig()->get("database"), $toLowerCase);
        if ($this->getConfig()->getNested("cache.enable")) {
            $this->isCacheEnabled = true;

            switch (strtolower($this->getConfig()->getNested("cache.technique"))) {
                case Cache::TTL_CACHE_TECHNIQUE:
                    $this->cache = new ExpiringCache($this->getConfig()->getNested("cache.ttl"), $toLowerCase);
                    break;
                case Cache::PLAYER_CACHE_TECHNIQUE:
                    $this->cache = new PlayerBasedCache($this->getConfig()->getNested("cache.ttl"), $toLowerCase);
                    break;
                case Cache::MIXED_CACHE_TECHNIQUE:
                    $this->cache = new MixedCache($this, $this->getConfig()->getNested("cache.ttl"), $toLowerCase);
                    break;
                default:
                    $this->getLogger()->warning("Unknown cache technique {$this->getConfig()->getNested("cache.technique")}");
                    throw new DisablePluginException();
            }
        }
        Api::init($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("KDR2", new KDRCommand($this));
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

    public function getMessage(string $key): string
    {
        return $this->messages->getNested($key) ?? $key;
    }
}
