<?php

namespace Map\Search;

use Map\Domain\QueryTokenizer;
use Map\Port\OrganizationRepository;
use Map\Port\SearchIndexRepository;

final class AutocompleteService
{
    public function __construct(
        private SearchIndexRepository $index,
        private OrganizationRepository $organizations,
        private QueryTokenizer $tokenizer,
        private int $perPage,
    ) {
    }

    /**
     * @return list<string>
     */
    public function suggestTerms(string $term, int $page = 0): array
    {
        $tokens = $this->tokenizer->split($term);
        if ($tokens === []) {
            return [];
        }
        $offset = max(0, $page) * $this->perPage;
        return array_values(array_unique($this->index->suggestWords($tokens, $offset, $this->perPage)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function suggestOrganizations(string $name, int $page = 0): array
    {
        $name = trim($name);
        if ($name === '') {
            return [];
        }
        $offset = max(0, $page) * $this->perPage;
        return $this->organizations->suggestByName($name, $offset, $this->perPage);
    }
}
