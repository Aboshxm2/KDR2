<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\commands;

use Aboshxm2\KDR2\Api;
use Aboshxm2\KDR2\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginOwned;

class KDRCommand extends Command implements PluginOwned
{
    public function __construct(private Main $plugin)
    {
        parent::__construct("kdr", "kdr commands");
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if(!isset($args[0])) {
            help:
            $sender->sendMessage(// Sorry jason, I'm too lazy to write this using multiple if statements
                $this->plugin->getMessage("help.help") . "\n".

                ($sender->hasPermission("KDR2.commands.kdr.stats") ?
                    (
                        $this->plugin->getMessage("help.stats") . "\n"
                    ): "") .

                ($sender->hasPermission("KDR2.commands.kdr.edit") ?
                    (
                        $this->plugin->getMessage("help.set-kills") . "\n".
                        $this->plugin->getMessage("help.set-deaths") . "\n".
                        $this->plugin->getMessage("help.set-killstreak")
                    ) : "")
            );

            return;
        }

        switch (strtolower($args[0])) {
            default:
            case "help":
                goto help;
            case "stats":
                if(!$sender->hasPermission("KDR2.commands.kdr.stats")) {
                    $sender->sendMessage($this->plugin->getMessage("general.no-permission"));
                    return;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage($this->plugin->getMessage("stats.usage"));
                    return;
                }

                Api::getAll($args[1], function (int $kills, int $deaths, int $killstreak) use ($args, $sender): void {
                    if ($deaths === 0) {
                        $kdr = $kills;
                    } else {
                        $kdr = $kills / $deaths;
                    }

                    $kdr = round($kdr, 3);

                    $sender->sendMessage(str_replace(["{player}", "{kills}", "{deaths}", "{killstreak}", "{kdr}"], [$args[1], $kills, $deaths, $killstreak, $kdr], $this->plugin->getMessage("stats.message")));
                });
                break;
            case "setkills":
            case "set-kills":
                if(!$sender->hasPermission("KDR2.commands.kdr.edit")) {
                    $sender->sendMessage($this->plugin->getMessage("general.no-permission"));
                    return;
                }
                if(!isset($args[2])) {
                    $sender->sendMessage($this->plugin->getMessage("set-kills.usage"));
                    return;
                }

                if(!is_numeric($args[2]) || ($newKills = (int)$args[2]) < 0) {
                    $sender->sendMessage($this->plugin->getMessage("set-kills.not-valid"));
                    return;
                }

                $sender->sendMessage(str_replace("{player}", $args[1], $this->plugin->getMessage("set-kills.message")));

                Api::getAll($args[1], function (int $kills, int $deaths, int $killstreak) use ($newKills, $args, $sender): void {
                    Api::setAll($args[1], $newKills, $deaths, $killstreak);
                });
            break;
            case "setdeaths":
            case "set-deaths":
                if(!$sender->hasPermission("KDR2.commands.kdr.edit")) {
                    $sender->sendMessage($this->plugin->getMessage("general.no-permission"));
                    return;
                }
                if(!isset($args[2])) {
                    $sender->sendMessage($this->plugin->getMessage("set-deaths.usage"));
                    return;
                }

                if(!is_numeric($args[2]) || ($newDeaths = (int)$args[2]) < 0) {
                    $sender->sendMessage($this->plugin->getMessage("set-deaths.not-valid"));
                    return;
                }

                $sender->sendMessage(str_replace("{player}", $args[1], $this->plugin->getMessage("set-deaths.message")));

                Api::getAll($args[1], function (int $kills, int $deaths, int $killstreak) use ($newDeaths, $args, $sender): void {
                    Api::setAll($args[1], $kills, $newDeaths, $killstreak);
                });
                break;
            case "setkillstreak":
            case "set-killstreak":
                if(!$sender->hasPermission("KDR2.commands.kdr.edit")) {
                    $sender->sendMessage($this->plugin->getMessage("general.no-permission"));
                    return;
                }
                if(!isset($args[2])) {
                    $sender->sendMessage($this->plugin->getMessage("set-killstreak.usage"));
                    return;
                }

                if(!is_numeric($args[2]) || ($newKillstreak = (int)$args[2]) < 0) {
                    $sender->sendMessage($this->plugin->getMessage("set-killstreak.not-valid"));
                    return;
                }

                $sender->sendMessage(str_replace("{player}", $args[1], $this->plugin->getMessage("set-killstreak.message")));

                Api::getAll($args[1], function (int $kills, int $deaths, int $killstreak) use ($newKillstreak, $args, $sender): void {
                    Api::setAll($args[1], $kills, $deaths, $newKillstreak);
                });
                break;
        }
    }

    public function getOwningPlugin(): Main
    {
        return $this->plugin;
    }
}