<?php

namespace App\Interfaces;

interface Party
{
    /**
     * @return int number of new items found
     */
    public function run(): int;

    /**
     * @return int Cache length
     */
    public function cacheLen(): int;

    /**
     * @return bool Cache clear status
     */
    public function clearCache(): bool;
}
