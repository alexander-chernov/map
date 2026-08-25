<?php

namespace Map\Domain;

final class PagedResult
{
    public const ERR_NONE = '';
    public const ERR_BAD_PARAMS = 'bad_params';
    public const ERR_INVALID_ADDRESS = 'invalid_address';
    public const ERR_NOT_FOUND = 'not_found';

    /**
     * @param list<object> $items
     */
    public function __construct(
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        public readonly array $items,
        public readonly string $error = self::ERR_NONE,
        public readonly SearchQuery $query,
    ) {
    }

    public static function error(string $error, SearchQuery $query): self
    {
        return new self(0, $query->page, $query->perPage, [], $error, $query);
    }

    public static function emptyFound(SearchQuery $query): self
    {
        return new self(0, $query->page, $query->perPage, [], self::ERR_NOT_FOUND, $query);
    }

    public function maxPage(): int
    {
        if ($this->total <= 0) {
            return 0;
        }
        return (int) ceil($this->total / $this->perPage) - 1;
    }

    public function startIndex(): int
    {
        return $this->page * $this->perPage;
    }
}
