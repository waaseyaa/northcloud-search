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

        $topics = array_filter(explode(',', $query['topics'] ?? ''));
        $sources = array_filter(explode(',', $query['sources'] ?? ''));

        $results = [];
        $total = 0;
        $totalPages = 0;
        $error = false;

        if ($searchQuery !== '') {
            try {
                $data = $this->client->search($searchQuery, $page, 10, $topics, $sources);
                $results = $data['hits'] ?? [];
                $total = $data['total_hits'] ?? 0;
                $totalPages = $data['total_pages'] ?? 0;
            } catch (\Throwable) {
                $error = true;
            }
        }

        // Build topic facets from hit data (API doesn't return aggregated facets)
        $topicCounts = [];
        foreach ($results as $hit) {
            foreach ($hit['topics'] ?? [] as $topic) {
                $topicCounts[$topic] = ($topicCounts[$topic] ?? 0) + 1;
            }
        }
        arsort($topicCounts);

        $html = $this->twig->render('search.html.twig', [
            'query' => $searchQuery,
            'results' => $results,
            'total' => $total,
            'page' => $page,
            'totalPages' => $totalPages,
            'topicCounts' => $topicCounts,
            'activeTopics' => $topics,
            'activeSources' => $sources,
            'error' => $error,
        ]);

        return new SsrResponse(content: $html);
    }
}
