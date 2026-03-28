<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Waaseyaa\Access\AccountInterface;
use Waaseyaa\Database\DatabaseInterface;
use Waaseyaa\SSR\SsrResponse;

final class ContentController
{
    public function __construct(
        private readonly Environment $twig,
        private readonly DatabaseInterface $database,
    ) {}

    public function show(
        array $params,
        array $query,
        AccountInterface $account,
        Request $httpRequest,
    ): SsrResponse {
        $id = $params['id'] ?? '';

        $sql = <<<'SQL'
            SELECT m.*, si.title, si.body
            FROM search_metadata m
            JOIN search_index si ON si.document_id = m.document_id
            WHERE m.document_id = :id
        SQL;

        $rows = iterator_to_array($this->database->query($sql, ['id' => $id]));

        if ($rows === []) {
            $html = $this->twig->render('404.html.twig', ['path' => "/content/$id"]);
            return new SsrResponse(content: $html, statusCode: 404);
        }

        $item = $rows[0];
        $item['topics'] = json_decode($item['topics'], true) ?: [];

        $html = $this->twig->render('content.html.twig', ['item' => $item]);

        return new SsrResponse(content: $html);
    }
}
