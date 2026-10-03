<?php

namespace Padlet\Tests;

use Padlet\Client;
use Padlet\Exception\AuthenticationException;
use Padlet\StaticApiKeyProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ClientTest extends TestCase
{
    public function testABoardIsReadWithItsSectionsAndPosts(): void
    {
        $document = [
            'data' => ['id' => 'b1', 'type' => 'board', 'attributes' => ['title' => 'Allemand CE1', 'description' => 'Vocabulaire', 'url' => 'https://padlet.com/x/allemand-ce1-b1']],
            'included' => [
                ['id' => 's1', 'type' => 'section', 'attributes' => ['title' => 'Les couleurs']],
                ['id' => 'p1', 'type' => 'post', 'attributes' => ['subject' => 'rot', 'body' => 'rouge', 'color' => 'red', 'attachment' => ['url' => 'https://img/rot.png'], 'created_at' => '2026-09-01T10:00:00Z'], 'relationships' => ['section' => ['data' => ['id' => 's1', 'type' => 'section']]]],
            ],
        ];
        $http = new MockHttpClient(function (string $method, string $url, array $options) use ($document) {
            self::assertSame('GET', $method);
            self::assertStringStartsWith('https://api.padlet.dev/v1/boards/b1?include=', $url);
            self::assertContains('X-API-KEY: k3y', $options['headers']);

            return new MockResponse(json_encode($document));
        });
        $board = (new Client($http, new StaticApiKeyProvider('k3y')))->board('b1');
        self::assertSame('Allemand CE1', $board->title);
        self::assertCount(1, $board->sections);
        self::assertCount(1, $board->posts);
        self::assertSame('Les couleurs', $board->section($board->posts[0]->sectionId)?->title);
        self::assertSame('https://img/rot.png', $board->posts[0]->attachmentUrl);
        self::assertSame('2026-09-01', $board->posts[0]->createdAt?->format('Y-m-d'));
    }

    public function testARefusedKeyIsAnAuthenticationError(): void
    {
        $client = new Client(new MockHttpClient([new MockResponse('{"errors":[{"detail":"nope"}]}', ['http_code' => 401])]), new StaticApiKeyProvider('bad'));
        $this->expectException(AuthenticationException::class);
        $client->me();
    }

    public function testNoKeyMeansNotConfigured(): void
    {
        $client = new Client(new MockHttpClient(), new StaticApiKeyProvider(''));
        self::assertFalse($client->isConfigured());
        self::assertSame('https://padlet.com/embed/abc', Client::embedUrl('abc'));
        self::assertSame('https://padlet.com/embed/b1', Client::embedUrl('https://padlet.com/x/allemand-ce1-b1'));
        self::assertSame('rvyfl7p6pw4o7gp0', Client::boardId('https://padlet.com/maitresse/die-farben-rvyfl7p6pw4o7gp0'));
    }
}
