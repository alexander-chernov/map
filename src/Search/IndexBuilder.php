<?php

namespace Map\Search;

use Map\Domain\QueryTokenizer;
use Map\Port\Geocoder;
use Map\Port\SearchIndexWriter;

final class IndexBuilder
{
    public function __construct(
        private SearchIndexWriter $writer,
        private QueryTokenizer $tokenizer,
        private Geocoder $geocoder,
        private string $locality,
    ) {
    }

    public function rebuild(bool $updateCoords = true, bool $geocodeMissing = false): IndexStats
    {
        $this->writer->truncate();

        $orgTerms = 0;
        $orgs = $this->writer->organizationsForIndex();
        $coordsUpdated = 0;
        $geocoded = 0;
        foreach ($orgs as $row) {
            $tokens = $this->organizationTokens($row);
            $haystack = implode(' ', $tokens);
            $orgId = (int) ($row['id'] ?? 0);
            $streetId = (int) ($row['street_id'] ?? 0);
            foreach ($tokens as $word) {
                $this->writer->insertOrgTerm($word, $haystack, $orgId, $streetId);
                $orgTerms++;
            }
            if ($updateCoords) {
                $lat = isset($row['h_X']) ? (float) $row['h_X'] : 0.0;
                $lon = isset($row['h_Y']) ? (float) $row['h_Y'] : 0.0;
                if ($lat == 0.0 && $lon == 0.0 && $geocodeMissing) {
                    $point = $this->geocoder->geocode($this->orgQuery($row));
                    if ($point?->lat !== null && $point->lon !== null) {
                        $lat = $point->lat;
                        $lon = $point->lon;
                        $geocoded++;
                    }
                }
                if ($lat != 0.0 || $lon != 0.0) {
                    $this->writer->updateOrgCoords($orgId, $lat, $lon);
                    $coordsUpdated++;
                }
            }
        }

        $addressTerms = 0;
        $addresses = $this->writer->addressesForIndex();
        foreach ($addresses as $row) {
            $tokens = $this->addressTokens($row);
            $haystack = implode(' ', $tokens);
            $streetId = (int) ($row['k_shn_street_id'] ?? 0);
            foreach ($tokens as $word) {
                $this->writer->insertStreetTerm($word, $haystack, $streetId);
                $addressTerms++;
            }
        }

        return new IndexStats(
            organizations: count($orgs),
            orgTerms: $orgTerms,
            addresses: count($addresses),
            addressTerms: $addressTerms,
            coordsUpdated: $coordsUpdated,
            geocoded: $geocoded,
        );
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string>
     */
    private function organizationTokens(array $row): array
    {
        $tokens = [];
        foreach (['category', 'subcategory', 'name', 'description'] as $field) {
            $tokens = array_merge($tokens, $this->tokenizer->orgTokens((string) ($row[$field] ?? '')));
        }
        $tokens = array_merge($tokens, $this->tokenizer->streetNameTokens((string) ($row['street'] ?? '')));
        $house = $this->tokenizer->houseToken((string) ($row['house_num'] ?? ''));
        if ($house !== '') {
            $tokens[] = $house;
        }
        return array_values(array_unique($tokens));
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string>
     */
    private function addressTokens(array $row): array
    {
        $tokens = array_merge(
            $this->tokenizer->placeTokens((string) ($row['district'] ?? '')),
            $this->tokenizer->placeTokens((string) ($row['massive'] ?? '')),
            $this->tokenizer->streetNameTokens((string) ($row['street'] ?? '')),
        );
        $house = $this->tokenizer->houseToken((string) ($row['house'] ?? ''));
        if ($house !== '') {
            $tokens[] = $house;
        }
        return array_values(array_unique($tokens));
    }

    /** @param array<string, mixed> $row */
    private function orgQuery(array $row): string
    {
        $parts = array_filter([
            $this->locality,
            trim((string) ($row['street'] ?? '')),
            trim((string) ($row['house_num'] ?? '')),
            trim((string) ($row['name'] ?? '')),
        ]);
        return implode(', ', $parts);
    }
}
