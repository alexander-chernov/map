<?php

namespace Map\Port;

interface SearchIndexRepository
{
    /**
     * @param list<string> $tokens
     * @return array{orgIds: list<int>, streetIds: list<int>}
     */
    public function findOrgHits(array $tokens): array;

    /**
     * @param list<string> $tokens
     * @return list<int>
     */
    public function findStreetIds(array $tokens): array;

    /**
     * @param list<string> $tokens
     * @return list<string>
     */
    public function suggestWords(array $tokens, int $offset, int $limit): array;
}
