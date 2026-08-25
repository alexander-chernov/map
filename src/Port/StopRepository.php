<?php

namespace Map\Port;

use Map\Domain\TransitStop;

interface StopRepository
{
    /** @param list<string> $tokens */
    public function countByTokens(array $tokens): int;

    /**
     * @param list<string> $tokens
     * @return list<TransitStop>
     */
    public function findByTokens(array $tokens, int $offset, int $limit): array;
}
