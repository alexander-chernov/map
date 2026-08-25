<?php

namespace Map\Map;

final class TransitLabel
{
    public static function format(string $code): string
    {
        return str_replace(
            ['А', 'Т', 'М'],
            ['Автобус №', 'Троллейбус №', 'Марштурка №'],
            $code
        );
    }
}
