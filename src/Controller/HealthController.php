<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Waaseyaa\Access\AccountInterface;
use Waaseyaa\SSR\SsrResponse;

final class HealthController
{
    public function check(
        array $params,
        array $query,
        AccountInterface $account,
        Request $httpRequest,
    ): SsrResponse {
        return new SsrResponse(
            content: '{"status":"ok"}',
            headers: ['Content-Type' => 'application/json'],
        );
    }
}
