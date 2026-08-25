<?php

namespace Map;

final class Autoload
{
    public static function register(): void
    {
        spl_autoload_register(static function (string $class): void {
            $prefix = 'Map\\';
            if (!str_starts_with($class, $prefix)) {
                return;
            }
            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $path = __DIR__ . '/' . $relative . '.php';
            if (is_file($path)) {
                require $path;
            }
        });
    }
}
