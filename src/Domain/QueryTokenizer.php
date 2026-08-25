<?php

namespace Map\Domain;

final class QueryTokenizer
{
    /** @var list<string> */
    private const STREET_STOPWORDS = [
        'улица', 'переулок', 'проспект', 'проезд', 'тупик', 'площадь', 'тракт',
    ];

    /** @var list<string> */
    private const GEO_STOPWORDS = ['томск'];

    /** @var list<string> */
    private const SETTLEMENT_STOPWORDS = [
        'томск', 'улица', 'переулок', 'проспект', 'проезд', 'тупик', 'площадь', 'тракт',
        'поселок', 'село', 'станция', 'деревня', 'пос', 'п', 'с', 'ст',
    ];

    /** @var list<string> */
    private const PLACE_STOPWORDS = [
        'район', 'поселок', 'микрорайон', 'город', 'станция', 'село', 'деревня',
    ];

    /**
     * @return list<string>
     */
    public function split(string $text): array
    {
        if ($text === '') {
            return [];
        }
        preg_match_all('/[а-яА-Яa-zA-Z0-9]+/u', $text, $matches);
        return array_values($matches[0] ?? []);
    }

    /**
     * Tokens for address/org search: lowercase, drop street-type words.
     *
     * @return list<string>
     */
    public function searchTokens(string $text): array
    {
        $out = [];
        foreach ($this->split($text) as $item) {
            $item = mb_strtolower($item);
            if (in_array($item, self::STREET_STOPWORDS, true)) {
                continue;
            }
            if (str_contains($item, '_')) {
                $item = str_replace('_', ' ', $item);
            }
            if ($item !== '') {
                $out[] = $item;
            }
        }
        return $out;
    }

    /**
     * Tokens for matching street geometry / geocoder names.
     *
     * @return list<string>
     */
    public function geoTokens(string $text): array
    {
        $out = [];
        foreach ($this->split($text) as $item) {
            $item = mb_strtolower($item);
            if (mb_strlen($item) <= 2) {
                continue;
            }
            if (in_array($item, self::GEO_STOPWORDS, true) || in_array($item, self::STREET_STOPWORDS, true)) {
                continue;
            }
            $out[] = $item;
        }
        return $out;
    }

    /**
     * @param list<string> $stopwords
     * @return list<string>
     */
    public function fieldTokens(string $text, array $stopwords, int $minLength = 3): array
    {
        $out = [];
        foreach ($this->split($text) as $item) {
            $item = mb_strtolower($item);
            if (mb_strlen($item) < $minLength) {
                continue;
            }
            if (in_array($item, $stopwords, true)) {
                continue;
            }
            $out[] = $item;
        }
        return $out;
    }

    /** @return list<string> */
    public function orgTokens(string $text): array
    {
        return $this->fieldTokens($text, self::GEO_STOPWORDS, 3);
    }

    /** @return list<string> */
    public function streetNameTokens(string $text): array
    {
        return $this->fieldTokens($text, self::SETTLEMENT_STOPWORDS, 3);
    }

    /** @return list<string> */
    public function placeTokens(string $text): array
    {
        return $this->fieldTokens($text, self::PLACE_STOPWORDS, 3);
    }

    public function houseToken(string $house): string
    {
        return mb_strtolower(trim($house));
    }
}
