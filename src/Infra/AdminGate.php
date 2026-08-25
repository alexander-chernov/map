<?php

namespace Map\Infra;

final class AdminGate
{
    public function allow(): bool
    {
        if (defined('MAP_ADMIN_TOKEN') && MAP_ADMIN_TOKEN !== '') {
            $token = (string) ($_GET['token'] ?? $_SESSION['map_admin_token'] ?? '');
            if ($token !== '' && hash_equals((string) MAP_ADMIN_TOKEN, $token)) {
                $_SESSION['map_admin_token'] = $token;
                return true;
            }
            return false;
        }
        if (defined('MAP_ALLOW_ADMIN')) {
            return (bool) MAP_ALLOW_ADMIN;
        }
        return true;
    }
}
