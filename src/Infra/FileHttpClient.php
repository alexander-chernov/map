<?php

namespace Map\Infra;

use Map\Port\HttpClient;

final class FileHttpClient implements HttpClient
{
    public function __construct(private int $timeoutSeconds = 8)
    {
    }

    public function get(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'header' => "User-Agent: map-tomsk/1.0 (local geocoder)\r\nAccept: application/json\r\n",
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        return $body === false ? null : $body;
    }
}
