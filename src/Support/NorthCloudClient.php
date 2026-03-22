<?php

declare(strict_types=1);

namespace App\Support;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class NorthCloudClient
{
    private readonly HttpClientInterface $http;
    private readonly string $apiUrl;

    public function __construct()
    {
        $this->http = HttpClient::create();
        $this->apiUrl = getenv('NORTHCLOUD_API_URL') ?: 'http://search:8092';
    }

    /**
     * @return array{hits: list<array<string, mixed>>, total: int, facets: array<string, array<string, int>>}
     */
    public function search(string $query, int $from = 0, int $size = 10, array $topics = [], array $sources = []): array
    {
        $body = ['query' => $query, 'from' => $from, 'size' => $size];
        if ($topics !== []) {
            $body['topics'] = $topics;
        }
        if ($sources !== []) {
            $body['sources'] = $sources;
        }

        $response = $this->http->request('POST', $this->apiUrl . '/api/v1/search', [
            'json' => $body,
            'timeout' => 5,
        ]);

        return $response->toArray();
    }

    /**
     * @return list<string>
     */
    public function suggest(string $query): array
    {
        $response = $this->http->request('GET', $this->apiUrl . '/api/v1/search/suggest', [
            'query' => ['q' => $query],
            'timeout' => 3,
        ]);

        return $response->toArray();
    }
}
