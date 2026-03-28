<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Waaseyaa\Access\AccountInterface;
use Waaseyaa\Search\SearchFilters;
use Waaseyaa\Search\SearchProviderInterface;
use Waaseyaa\Search\SearchRequest;
use Waaseyaa\SSR\SsrResponse;

final class SearchController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly SearchProviderInterface $search,
    ) {}

    public function results(
        array $params,
        array $query,
        AccountInterface $account,
        Request $httpRequest,
    ): SsrResponse {
        $searchQuery = trim($query['q'] ?? '');
        $page = max(1, (int) ($query['page'] ?? 1));
        $contentType = trim($query['type'] ?? '');
        $topic = trim($query['topic'] ?? '');

        $result = null;
        if ($searchQuery !== '') {
            $filters = new SearchFilters(
                topics: $topic !== '' ? [$topic] : [],
                contentType: $contentType,
            );

            $result = $this->search->search(new SearchRequest(
                query: $searchQuery,
                filters: $filters,
                page: $page,
                pageSize: 10,
            ));
        }

        $html = $this->twig->render('search.html.twig', [
            'query' => $searchQuery,
            'result' => $result,
            'activeType' => $contentType,
            'activeTopic' => $topic,
        ]);

        return new SsrResponse(content: $html);
    }
}
