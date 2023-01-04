<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\database;

interface Database
{
    /**
     * @param string $playerName
     * @param \Closure(int $kills, int $deaths, int $killstreak): void $then
     * @return void
     */
    public function getAll(string $playerName, \Closure $then): void;

    /**
     * @param string $playerName
     * @param int $kills
     * @param int $deaths
     * @param int $killstreak
     * @return void
     */
    public function setAll(string $playerName, int $kills, int $deaths, int $killstreak): void;
}
