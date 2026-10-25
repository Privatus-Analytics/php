<?php

declare(strict_types=1);

namespace Privatus\Analytics\Tests\Laravel;

final class MiddlewareTest extends TestCase
{
    public function testAddsTheTagToTheHead(): void
    {
        $this->enable();
        $response = $this->get('/plain');
        $body = (string) $response->getContent();

        self::assertStringContainsString(self::SNIPPET . "\n</head>", $body);
        self::assertSame((string) strlen($body), $response->headers->get('Content-Length'));
    }

    public function testAddsNothingWhenTheTagIsOffEvenWithTheQueueStub(): void
    {
        config(['privatus.enabled' => false, 'privatus.queue_stub' => true]);

        self::assertSame('<html><head><title>Plain</title></head><body>Hi</body></html>', $this->get('/plain')->getContent());
    }

    public function testDoesNotAddASecondTag(): void
    {
        $this->enable();

        self::assertSame(1, substr_count((string) $this->get('/layout-injected')->getContent(), '/js/pa.js'));
    }

    public function testLeavesJsonAlone(): void
    {
        $this->enable();

        self::assertStringNotContainsString('pa.js', (string) $this->get('/data')->getContent());
    }

    public function testLeavesHtmlWithoutAHeadAlone(): void
    {
        $this->enable();

        self::assertSame('<p>No head here</p>', $this->get('/bare')->getContent());
    }

    public function testLeavesAjaxCompressedAndErrorResponsesAlone(): void
    {
        $this->enable();

        self::assertStringNotContainsString('pa.js', (string) $this->get('/plain', ['X-Requested-With' => 'XMLHttpRequest'])->getContent());
        self::assertStringNotContainsString('pa.js', (string) $this->get('/gzip')->getContent());
        self::assertStringNotContainsString('pa.js', (string) $this->get('/missing')->getContent());
    }

    public function testAddsNothingOutsideTheEnabledEnvironments(): void
    {
        self::assertStringNotContainsString('pa.js', (string) $this->get('/plain')->getContent());
    }
}
