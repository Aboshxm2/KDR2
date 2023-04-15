<?php

declare(strict_types=1);

namespace Aboshxm2\KDR2\cache;

interface Cache
{
    public const TTL_CACHE_TECHNIQUE = "ttl";
    public const PLAYER_CACHE_TECHNIQUE = "player";
    public const MIXED_CACHE_TECHNIQUE = "mixed";

    public function get(string $playerName): ?array;

    public function set(string $playerName, array $data): void;
}
