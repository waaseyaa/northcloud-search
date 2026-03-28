<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Waaseyaa\Access\AccountInterface;
use Waaseyaa\Database\DatabaseInterface;
use Waaseyaa\SSR\SsrResponse;

final class HomeController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly DatabaseInterface $database,
    ) {}

    public function index(
        array $params,
        array $query,
        AccountInterface $account,
        Request $httpRequest,
    ): SsrResponse {
        $recentItems = $this->fetchRecent(12);
        $typeCounts = $this->fetchTypeCounts();

        $html = $this->twig->render('home.html.twig', [
            'recentItems' => $recentItems,
            'typeCounts' => $typeCounts,
        ]);

        return new SsrResponse(content: $html);
    }

    /** @return list<array<string, mixed>> */
    private function fetchRecent(int $limit): array
    {
        $sql = <<<'SQL'
            SELECT m.document_id, si.title, m.content_type, m.source_name,
                   m.url, m.og_image, m.quality_score, m.topics, m.created_at
            FROM search_metadata m
            JOIN search_index si ON si.document_id = m.document_id
            ORDER BY m.created_at DESC
            LIMIT :limit
        SQL;

        $items = [];
        foreach ($this->database->query($sql, ['limit' => $limit]) as $row) {
            $row['topics'] = json_decode($row['topics'], true) ?: [];
            $items[] = $row;
        }

        return $items;
    }

    /** @return array<string, int> */
    private function fetchTypeCounts(): array
    {
        $sql = <<<'SQL'
            SELECT content_type, COUNT(*) as cnt
            FROM search_metadata
            WHERE content_type != ''
            GROUP BY content_type
            ORDER BY cnt DESC
        SQL;

        $counts = [];
        foreach ($this->database->query($sql, []) as $row) {
            $counts[$row['content_type']] = (int) $row['cnt'];
        }

        return $counts;
    }
}
