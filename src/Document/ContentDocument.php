<?php

declare(strict_types=1);

namespace App\Document;

use Waaseyaa\Search\SearchIndexableInterface;

final readonly class ContentDocument implements SearchIndexableInterface
{
    /**
     * @param string[] $topics
     */
    private function __construct(
        private string $id,
        private string $title,
        private string $body,
        private string $url,
        private string $contentType,
        private string $sourceName,
        private int $qualityScore,
        private array $topics,
        private string $ogImage,
        private string $publishedAt,
    ) {}

    /**
     * @param array<string, mixed> $data Redis pub/sub message
     */
    public static function fromRedisMessage(array $data): self
    {
        $sourceUrl = (string) ($data['source'] ?? $data['canonical_url'] ?? '');
        $sourceName = self::extractDomain($sourceUrl);

        return new self(
            id: (string) ($data['id'] ?? ''),
            title: (string) ($data['title'] ?? ''),
            body: (string) ($data['body'] ?? $data['raw_text'] ?? ''),
            url: (string) ($data['canonical_url'] ?? ''),
            contentType: (string) ($data['content_type'] ?? ''),
            sourceName: $sourceName,
            qualityScore: (int) ($data['quality_score'] ?? 0),
            topics: is_array($data['topics'] ?? null) ? $data['topics'] : [],
            ogImage: (string) ($data['og_image'] ?? ''),
            publishedAt: (string) ($data['published_date'] ?? date('c')),
        );
    }

    public function getSearchDocumentId(): string
    {
        return $this->id;
    }

    /** @return array{title: string, body: string} */
    public function toSearchDocument(): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
        ];
    }

    /** @return array<string, mixed> */
    public function toSearchMetadata(): array
    {
        return [
            'entity_type' => 'content',
            'content_type' => $this->contentType,
            'source_name' => $this->sourceName,
            'quality_score' => $this->qualityScore,
            'topics' => $this->topics,
            'url' => $this->url,
            'og_image' => $this->ogImage,
            'created_at' => $this->publishedAt,
        ];
    }

    private static function extractDomain(string $url): string
    {
        if ($url === '') {
            return '';
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (!is_string($host)) {
            return '';
        }

        return preg_replace('/^www\./', '', $host);
    }
}
