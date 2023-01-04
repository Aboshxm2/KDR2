<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

/**
 * Called before updating player kills in the database.
 * Canceling this causes player kills to not be updated.
 */
class PlayerKillsUpdateEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    private int $kills;

    public function __construct(Player $player, int $kills)
    {
        $this->player = $player;
        $this->kills = $kills;
    }

    /**
     * @return int
     */
    public function getKills(): int
    {
        return $this->kills;
    }

    /**
     * @param int $kills
     */
    public function setKills(int $kills): void
    {
        $this->kills = $kills;
    }
}
