<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\NorthCloudClient;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Waaseyaa\Access\AccountInterface;
use Waaseyaa\SSR\SsrResponse;

final class HomeController
{
    private const TOPICS = [
        'top_stories' => 'Top Stories',
        'crime' => 'Crime',
        'mining' => 'Mining',
        'entertainment' => 'Entertainment',
        'local_news' => 'Local News',
        'technology' => 'Technology',
        'politics' => 'Politics',
        'sports' => 'Sports',
    ];

    public function __construct(
        private readonly Environment $twig,
        private readonly NorthCloudClient $client,
    ) {}

    public function index(
        array $params,
        array $query,
        AccountInterface $account,
        Request $httpRequest,
    ): SsrResponse {
        $topStories = [];

        try {
            $data = $this->client->search('*', 1, 6, ['top_stories']);
            $topStories = $data['hits'] ?? [];
        } catch (\Throwable) {
            // Homepage still renders without stories
        }

        $html = $this->twig->render('home.html.twig', [
            'topStories' => $topStories,
            'topics' => self::TOPICS,
        ]);

        return new SsrResponse(content: $html);
    }
}
