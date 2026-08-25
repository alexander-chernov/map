<?php

namespace Map\Search;

use Map\Domain\PagedResult;
use Map\Domain\SearchQuery;
use Map\Port\AddressRepository;
use Map\Port\OrganizationRepository;
use Map\Port\RealtyRepository;
use Map\Port\SearchIndexRepository;
use Map\Port\StopRepository;

final class SearchService
{
    public function __construct(
        private OrganizationRepository $organizations,
        private AddressRepository $addresses,
        private RealtyRepository $realty,
        private StopRepository $stops,
        private SearchIndexRepository $index,
    ) {
    }

    public function organizations(SearchQuery $query): PagedResult
    {
        if ($query->hasPhrase()) {
            if ($query->tokens === []) {
                return PagedResult::emptyFound($query);
            }
            $hits = $this->index->findOrgHits($query->tokens);
            if ($hits['orgIds'] === []) {
                return PagedResult::emptyFound($query);
            }
            $total = $this->organizations->countByIds($hits['orgIds'], $hits['streetIds']);
            if ($total === 0) {
                return PagedResult::emptyFound($query);
            }
            $items = $this->organizations->findByIds(
                $hits['orgIds'],
                $hits['streetIds'],
                $query->offset(),
                $query->perPage
            );
            return new PagedResult($total, $query->page, $query->perPage, $items, PagedResult::ERR_NONE, $query);
        }

        if (!$query->hasStreetFilter()) {
            return PagedResult::error(PagedResult::ERR_BAD_PARAMS, $query);
        }
        $scope = $this->addresses->resolveScope($query);
        if ($scope->isEmpty()) {
            return PagedResult::error(PagedResult::ERR_INVALID_ADDRESS, $query);
        }
        $total = $this->organizations->countByScope($scope);
        if ($total === 0) {
            return new PagedResult(0, $query->page, $query->perPage, [], PagedResult::ERR_NONE, $query);
        }
        $items = $this->organizations->findByScope($scope, $query->offset(), $query->perPage);
        return new PagedResult($total, $query->page, $query->perPage, $items, PagedResult::ERR_NONE, $query);
    }

    public function realty(SearchQuery $query): PagedResult
    {
        if ($query->hasPhrase()) {
            if ($query->tokens === []) {
                return PagedResult::emptyFound($query);
            }
            $streetIds = $this->index->findStreetIds($query->tokens);
            if ($streetIds === []) {
                return PagedResult::emptyFound($query);
            }
            $total = $this->realty->countByStreetIds($streetIds);
            if ($total === 0) {
                return PagedResult::emptyFound($query);
            }
            $items = $this->realty->findByStreetIds($streetIds, $query->offset(), $query->perPage);
            return new PagedResult($total, $query->page, $query->perPage, $items, PagedResult::ERR_NONE, $query);
        }

        if (!$query->hasStreetFilter()) {
            return PagedResult::error(PagedResult::ERR_BAD_PARAMS, $query);
        }
        $scope = $this->addresses->resolveScope($query);
        if ($scope->isEmpty()) {
            return PagedResult::error(PagedResult::ERR_INVALID_ADDRESS, $query);
        }
        $total = $this->realty->countByScope($scope);
        $items = $total > 0
            ? $this->realty->findByScope($scope, $query->offset(), $query->perPage)
            : [];
        return new PagedResult($total, $query->page, $query->perPage, $items, PagedResult::ERR_NONE, $query);
    }

    public function addresses(SearchQuery $query): PagedResult
    {
        if ($query->hasPhrase()) {
            if ($query->tokens === []) {
                return PagedResult::emptyFound($query);
            }
            $total = $this->addresses->countByTokens($query->tokens);
            if ($total === 0) {
                return PagedResult::emptyFound($query);
            }
            $items = $this->addresses->findByTokens($query->tokens, $query->offset(), $query->perPage);
            return new PagedResult($total, $query->page, $query->perPage, $items, PagedResult::ERR_NONE, $query);
        }

        if (!$query->hasAddressFilter()) {
            return PagedResult::error(PagedResult::ERR_BAD_PARAMS, $query);
        }
        $scope = $this->addresses->resolveScope($query);
        if ($scope->isEmpty()) {
            return PagedResult::error(PagedResult::ERR_INVALID_ADDRESS, $query);
        }
        $total = $this->addresses->countByScope($scope);
        $items = $total > 0
            ? $this->addresses->findByScope($scope, $query->offset(), $query->perPage)
            : [];
        return new PagedResult($total, $query->page, $query->perPage, $items, PagedResult::ERR_NONE, $query);
    }

    public function stops(SearchQuery $query): PagedResult
    {
        if (!$query->hasPhrase() || $query->tokens === []) {
            return new PagedResult(0, $query->page, $query->perPage, [], PagedResult::ERR_NONE, $query);
        }
        $total = $this->stops->countByTokens($query->tokens);
        if ($total === 0) {
            return PagedResult::emptyFound($query);
        }
        $items = $this->stops->findByTokens($query->tokens, $query->offset(), $query->perPage);
        return new PagedResult($total, $query->page, $query->perPage, $items, PagedResult::ERR_NONE, $query);
    }
}
