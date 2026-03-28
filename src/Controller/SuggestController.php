<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Waaseyaa\Access\AccountInterface;
use Waaseyaa\Database\DatabaseInterface;
use Waaseyaa\SSR\SsrResponse;

final class SuggestController
{
    public function __construct(
        private readonly DatabaseInterface $database,
    ) {}

    public function suggest(
        array $params,
        array $query,
        AccountInterface $account,
        Request $httpRequest,
    ): SsrResponse {
        $q = trim($query['q'] ?? '');

        if (mb_strlen($q) < 2) {
            return new SsrResponse(
                content: '[]',
                headers: ['Content-Type' => 'application/json'],
            );
        }

        $suggestions = $this->prefixSearch($q, 8);

        return new SsrResponse(
            content: json_encode($suggestions, JSON_THROW_ON_ERROR),
            headers: ['Content-Type' => 'application/json'],
        );
    }

    /** @return list<string> */
    private function prefixSearch(string $prefix, int $limit): array
    {
        $escaped = str_replace('"', '""', $prefix);
        $ftsQuery = '"' . $escaped . '"*';

        $sql = <<<'SQL'
            SELECT DISTINCT si.title
            FROM search_index si
            WHERE search_index MATCH :query
            ORDER BY si.rank
            LIMIT :limit
        SQL;

        $titles = [];
        foreach ($this->database->query($sql, ['query' => $ftsQuery, 'limit' => $limit]) as $row) {
            $titles[] = $row['title'];
        }

        return $titles;
    }
}
