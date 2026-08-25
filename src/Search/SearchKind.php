<?php

namespace Map\Search;

enum SearchKind: string
{
    case Organization = 'org';
    case Realty = 'realty';
    case Address = 'address';
    case Stop = 'stop';

    public function listId(): string
    {
        return match ($this) {
            self::Organization => 'ol_org',
            self::Realty => 'ol_rlt',
            self::Address => 'ol_addr',
            self::Stop => 'ol_stp',
        };
    }

    public function jsLoader(): string
    {
        return match ($this) {
            self::Organization => 'getResultOrg',
            self::Realty => 'getResultRealty',
            self::Address => 'getResultAddress',
            self::Stop => 'getResultStops',
        };
    }
}
