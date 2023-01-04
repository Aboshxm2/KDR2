<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\commands;

use Aboshxm2\KDR2\Api;
use Aboshxm2\KDR2\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;

class KDRStatsCommand extends Command implements PluginOwned
{
    use PluginOwnedTrait;

    public function __construct(Main $plugin)
    {
        $this->owningPlugin = $plugin;
        parent::__construct("kdrstats", "Shows the player's stats.", null, ["stats"]);
        $this->setPermission("KDR2.commands.kdrstats");
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!$this->testPermission($sender)) return;

        if (!isset($args[0])) {
            if (!$sender instanceof Player) {
                $sender->sendMessage("Usage: /kdrstats <player>");
                return;
            }

            $playerName = $sender->getName();
        } else {
            $playerName = $args[0];
        }

        Api::getAll($playerName, function (int $kills, int $deaths, int $killstreak) use ($sender, $playerName): void {
            if ($deaths === 0) {
                $kdr = $kills;
            } else {
                $kdr = $kills / $deaths;
            }

            $kdr = round($kdr, 3);

            $message = "§9{$playerName}§f's Records\n§fKills: §e$kills\n§fDeaths: §e$deaths\n§fKDR: §e$kdr\n§fKillstreak: §e$killstreak";//TODO make this configurable.

            $sender->sendMessage($message);
        });
    }
}
