<?php

declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\ContentDocument;
use PHPUnit\Framework\TestCase;

final class ContentDocumentTest extends TestCase
{
    public function testFromRedisMessageMapsArticle(): void
    {
        $message = [
            'id' => 'es-doc-123',
            'title' => 'Police Investigate Break-In',
            'body' => 'Full article text here.',
            'canonical_url' => 'https://example.com/article',
            'published_date' => '2026-03-28T10:00:00Z',
            'quality_score' => 85,
            'topics' => ['crime', 'local_news'],
            'content_type' => 'article',
            'og_image' => 'https://example.com/image.jpg',
            'source' => 'https://example.com/original',
        ];

        $doc = ContentDocument::fromRedisMessage($message);

        $this->assertSame('es-doc-123', $doc->getSearchDocumentId());

        $searchDoc = $doc->toSearchDocument();
        $this->assertSame('Police Investigate Break-In', $searchDoc['title']);
        $this->assertSame('Full article text here.', $searchDoc['body']);

        $meta = $doc->toSearchMetadata();
        $this->assertSame('content', $meta['entity_type']);
        $this->assertSame('article', $meta['content_type']);
        $this->assertSame('example.com', $meta['source_name']);
        $this->assertSame(85, $meta['quality_score']);
        $this->assertSame(['crime', 'local_news'], $meta['topics']);
        $this->assertSame('https://example.com/article', $meta['url']);
        $this->assertSame('https://example.com/image.jpg', $meta['og_image']);
    }

    public function testFromRedisMessageExtractsSourceDomain(): void
    {
        $doc = ContentDocument::fromRedisMessage([
            'id' => 'doc-1',
            'title' => 'Test',
            'body' => 'Body',
            'canonical_url' => 'https://www.cbc.ca/news/article',
            'source' => 'https://www.cbc.ca/feed',
            'published_date' => '2026-03-28T10:00:00Z',
            'quality_score' => 50,
            'topics' => [],
            'content_type' => 'article',
        ]);

        $this->assertSame('cbc.ca', $doc->toSearchMetadata()['source_name']);
    }

    public function testFromRedisMessageHandlesMissingFields(): void
    {
        $doc = ContentDocument::fromRedisMessage([
            'id' => 'doc-2',
            'title' => 'Minimal',
            'canonical_url' => 'https://example.com',
        ]);

        $searchDoc = $doc->toSearchDocument();
        $this->assertSame('Minimal', $searchDoc['title']);
        $this->assertSame('', $searchDoc['body']);

        $meta = $doc->toSearchMetadata();
        $this->assertSame('', $meta['content_type']);
        $this->assertSame(0, $meta['quality_score']);
        $this->assertSame([], $meta['topics']);
    }

    public function testFromRedisMessageUsesRawTextFallback(): void
    {
        $doc = ContentDocument::fromRedisMessage([
            'id' => 'doc-3',
            'title' => 'Test',
            'raw_text' => 'Text from raw_text field',
            'canonical_url' => 'https://example.com',
        ]);

        $this->assertSame('Text from raw_text field', $doc->toSearchDocument()['body']);
    }
}
