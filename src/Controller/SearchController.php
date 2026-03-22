<?php

declare(strict_types=1);

namespace App\Controller;

use App\Support\NorthCloudClient;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Waaseyaa\Access\AccountInterface;
use Waaseyaa\SSR\SsrResponse;

final class SearchController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly NorthCloudClient $client,
    ) {}

    public function results(
        array $params,
        array $query,
        AccountInterface $account,
        Request $httpRequest,
    ): SsrResponse {
        $searchQuery = trim($query['q'] ?? '');
        $page = max(1, (int) ($query['page'] ?? 1));
        $size = 10;
        $from = ($page - 1) * $size;

        $topics = array_filter(explode(',', $query['topics'] ?? ''));
        $sources = array_filter(explode(',', $query['sources'] ?? ''));

        $results = [];
        $total = 0;
        $facets = ['topics' => [], 'sources' => []];
        $error = false;

        if ($searchQuery !== '') {
            try {
                $data = $this->client->search($searchQuery, $from, $size, $topics, $sources);
                $results = $data['hits'] ?? [];
                $total = $data['total'] ?? 0;
                $facets = $data['facets'] ?? $facets;
            } catch (\Throwable) {
                $error = true;
            }
        }

        $totalPages = (int) ceil($total / $size);

        $html = $this->twig->render('search.html.twig', [
            'query' => $searchQuery,
            'results' => $results,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'facets' => $facets,
            'activeTopics' => $topics,
            'activeSources' => $sources,
            'error' => $error,
        ]);

        return new SsrResponse(content: $html);
    }
}
