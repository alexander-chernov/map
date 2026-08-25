<?php

namespace Map\Infra;

final class CliGuard
{
    public static function assert(): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }
        http_response_code(403);
        echo 'This script can only be run from the command line.';
        exit(1);
    }
}
