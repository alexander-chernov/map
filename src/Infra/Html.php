<?php

namespace Map\Infra;

final class Html
{
    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function number(int|float|string|null $value): string
    {
        if (!is_numeric($value)) {
            return '0';
        }
        return (string) (0 + $value);
    }

    public static function url(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }
        if (preg_match('#^(javascript|data):#i', $value)) {
            return '';
        }
        if (!preg_match('#^https?://#i', $value)) {
            $value = self::scheme() . '://' . $value;
        }
        return self::e($value);
    }

    public static function scheme(): string
    {
        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwarded === 'https' || $forwarded === 'http') {
            return $forwarded;
        }
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return 'https';
        }
        if ((string) ($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return 'https';
        }
        return 'http';
    }

    public static function hostUrl(string $host, string $path = ''): string
    {
        $host = trim($host);
        if ($host === '') {
            return '';
        }
        $path = $path === '' ? '' : '/' . ltrim($path, '/');
        return self::e(self::scheme() . '://' . $host . $path);
    }
}
