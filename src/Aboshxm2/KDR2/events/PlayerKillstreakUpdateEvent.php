<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

/**
 * Called before updating player killstreak in the database.
 * Canceling this causes player killstreak to not be updated.
 */
class PlayerKillstreakUpdateEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    private int $killstreak;

    public function __construct(Player $player, int $killstreak)
    {
        $this->player = $player;
        $this->killstreak = $killstreak;
    }

    /**
     * @return int
     */
    public function getKillstreak(): int
    {
        return $this->killstreak;
    }

    /**
     * @param int $killstreak
     */
    public function setKillstreak(int $killstreak): void
    {
        $this->killstreak = $killstreak;
    }
}
