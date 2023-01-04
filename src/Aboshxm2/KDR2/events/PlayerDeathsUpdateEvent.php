<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\events;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

/**
 * Called before updating player deaths in the database.
 * Canceling this causes player deaths to not be updated.
 */
class PlayerDeathsUpdateEvent extends PlayerEvent implements Cancellable
{
    use CancellableTrait;

    private int $deaths;

    public function __construct(Player $player, int $deaths)
    {
        $this->player = $player;
        $this->deaths = $deaths;
    }

    /**
     * @return int
     */
    public function getDeaths(): int
    {
        return $this->deaths;
    }

    /**
     * @param int $deaths
     */
    public function setDeaths(int $deaths): void
    {
        $this->deaths = $deaths;
    }
}
