<?php

namespace dokuwiki\plugin\llmautotranslate;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LlmClientTest extends TestCase {
    public function testRequestBodyAndReturnedContent(): void {
        $transport = new FakeHttpTransport([
            'status' => 200,
            'body' => json_encode(['choices' => [['message' => ['content' => 'Hallo Welt']]]]),
        ]);

        $client = new LlmClient($transport, 'https://example.com/v1/chat/completions', 'secret-key', 'gpt-4o-mini');

        $result = $client->translate('system prompt', 'Hello world');

        $this->assertSame('Hallo Welt', $result);

        $decodedBody = json_decode($transport->lastBody, true);
        $this->assertSame('gpt-4o-mini', $decodedBody['model']);
        $this->assertSame(0, $decodedBody['temperature']);
        $this->assertCount(2, $decodedBody['messages']);
        $this->assertSame('system', $decodedBody['messages'][0]['role']);
        $this->assertSame('system prompt', $decodedBody['messages'][0]['content']);
        $this->assertSame('user', $decodedBody['messages'][1]['role']);
        $this->assertSame('Hello world', $decodedBody['messages'][1]['content']);

        $this->assertSame('Bearer secret-key', $transport->lastHeaders['Authorization']);
    }

    public function testThrowsLlmExceptionOnErrorStatus(): void {
        $transport = new FakeHttpTransport([
            'status' => 500,
            'body' => '{}',
        ]);

        $client = new LlmClient($transport, 'https://example.com', 'key', 'model');

        $this->expectException(LlmException::class);
        $client->translate('prompt', 'text');
    }

    public function testThrowsLlmExceptionWhenChoicesMissing(): void {
        $transport = new FakeHttpTransport([
            'status' => 200,
            'body' => json_encode(['foo' => 'bar']),
        ]);

        $client = new LlmClient($transport, 'https://example.com', 'key', 'model');

        $this->expectException(LlmException::class);
        $this->expectExceptionCode(502);
        $client->translate('prompt', 'text');
    }

    public function testContentTypeHeaderIsSent(): void {
        $transport = new FakeHttpTransport([
            'status' => 200,
            'body' => json_encode(['choices' => [['message' => ['content' => 'Hallo']]]]),
        ]);

        $client = new LlmClient($transport, 'https://example.com', 'key', 'model');
        $client->translate('prompt', 'text');

        $this->assertSame('application/json', $transport->lastHeaders['Content-Type']);
    }

    #[DataProvider('errorStatusProvider')]
    public function testThrowsLlmExceptionWithMatchingCodeOnErrorStatus(int $status): void {
        $transport = new FakeHttpTransport([
            'status' => $status,
            'body' => '{}',
        ]);

        $client = new LlmClient($transport, 'https://example.com', 'key', 'model');

        $this->expectException(LlmException::class);
        $this->expectExceptionCode($status);
        $client->translate('prompt', 'text');
    }

    public static function errorStatusProvider(): array {
        return [
            'bad request' => [400],
            'rate limited' => [429],
            'server error' => [500],
        ];
    }

    public function testThrowsLlmExceptionOnMalformedJsonBody(): void {
        $transport = new FakeHttpTransport([
            'status' => 200,
            'body' => 'not valid json {{{',
        ]);

        $client = new LlmClient($transport, 'https://example.com', 'key', 'model');

        $this->expectException(LlmException::class);
        $this->expectExceptionCode(502);
        $client->translate('prompt', 'text');
    }

    public function testErrorStatusMessageContainsResponseBodyDetail(): void {
        $transport = new FakeHttpTransport([
            'status' => 500,
            'body' => json_encode(['error' => ['message' => 'boom']]),
        ]);

        $client = new LlmClient($transport, 'https://example.com', 'key', 'model');

        try {
            $client->translate('prompt', 'text');
            $this->fail('Expected LlmException was not thrown.');
        } catch (LlmException $e) {
            $this->assertStringContainsString('boom', $e->getMessage());
            $this->assertSame(500, $e->getCode());
        }
    }

    public function testTransportFailureMessageContainsDokuWikiErrorDetail(): void {
        $transport = new FakeHttpTransport([
            'status' => -100,
            'body' => 'Could not connect to host: certificate verify failed',
        ]);

        $client = new LlmClient($transport, 'https://example.com', 'key', 'model');

        try {
            $client->translate('prompt', 'text');
            $this->fail('Expected LlmException was not thrown.');
        } catch (LlmException $e) {
            $this->assertStringContainsString('certificate verify failed', $e->getMessage());
            $this->assertSame(-100, $e->getCode());
        }
    }
}
