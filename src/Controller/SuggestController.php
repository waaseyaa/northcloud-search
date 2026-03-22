<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\NorthCloudClient;
use Symfony\Component\HttpFoundation\Request;
use Waaseyaa\Access\AccountInterface;
use Waaseyaa\SSR\SsrResponse;

final class SuggestController
{
    public function __construct(
        private readonly NorthCloudClient $client,
    ) {}

    public function suggest(
        array $params,
        array $query,
        AccountInterface $account,
        Request $httpRequest,
    ): SsrResponse {
        $q = trim($query['q'] ?? '');

        if ($q === '') {
            return new SsrResponse(
                content: '[]',
                headers: ['Content-Type' => 'application/json'],
            );
        }

        try {
            $suggestions = $this->client->suggest($q);

            return new SsrResponse(
                content: json_encode($suggestions, JSON_THROW_ON_ERROR),
                headers: ['Content-Type' => 'application/json'],
            );
        } catch (\Throwable) {
            return new SsrResponse(
                content: '[]',
                headers: ['Content-Type' => 'application/json'],
            );
        }
    }
}
