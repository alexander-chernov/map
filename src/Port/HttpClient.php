<?php

namespace Map\Port;

interface HttpClient
{
    public function get(string $url): ?string;
}
