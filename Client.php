<?php

namespace Padlet;

use Padlet\Exception\ApiException;
use Padlet\Exception\AuthenticationException;
use Padlet\Model\Board;
use Padlet\Model\Post;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/** Padlet's API for one account: its boards read, a post written. */
final class Client
{
    public const BASE_URL = 'https://api.padlet.dev/v1';
    public const KEY_HEADER = 'X-API-KEY';

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly ApiKeyProviderInterface $apiKey,
        private readonly string $baseUrl = self::BASE_URL,
        private readonly string $keyHeader = self::KEY_HEADER,
    ) {
    }

    public function isConfigured(): bool
    {
        return null !== $this->apiKey->getApiKey();
    }

    /** @return array<string, mixed> the account, as the API describes it */
    public function me(): array
    {
        return $this->call('GET', '/me')['data'] ?? [];
    }

    /** A board with its sections and posts. The id is the board's (the end of its address, after the last "-"). */
    public function board(string $id, bool $withPosts = true): Board
    {
        return Board::fromDocument($this->call('GET', '/boards/'.rawurlencode($id), ['query' => $withPosts ? ['include' => 'posts,sections'] : []]));
    }

    /** @return list<Post> */
    public function posts(string $boardId): array
    {
        $document = $this->call('GET', '/boards/'.rawurlencode($boardId).'/posts');
        $items = $document['data'] ?? [];
        // Some answers list the posts as data, others include them.
        if (isset($items['type'])) {
            $items = array_filter($document['included'] ?? [], fn ($r) => 'post' === ($r['type'] ?? null));
        }

        return array_values(array_map(fn (array $r) => Post::fromResource($r), array_filter($items, fn ($r) => 'post' === ($r['type'] ?? 'post'))));
    }

    public function createPost(string $boardId, ?string $subject, ?string $body, ?string $sectionId = null, ?string $color = null): Post
    {
        $payload = ['data' => ['type' => 'post', 'attributes' => array_filter(['subject' => $subject, 'body' => $body, 'color' => $color], fn ($v) => null !== $v)]];
        if ($sectionId) {
            $payload['data']['relationships'] = ['section' => ['data' => ['type' => 'section', 'id' => $sectionId]]];
        }

        return Post::fromResource($this->call('POST', '/boards/'.rawurlencode($boardId).'/posts', ['json' => $payload])['data'] ?? []);
    }

    /** The raw answer of any path, for the harness and for what the models do not read yet. @return array<string, mixed> */
    public function raw(string $method, string $path, array $options = []): array
    {
        return $this->call($method, $path, $options);
    }

    /** The embed address of a board (https://padlet.com/embed/<id>), from its id or its padlet.com address ("…/<title>-<id>"). */
    public static function embedUrl(string $boardIdOrUrl): string
    {
        return 'https://padlet.com/embed/'.rawurlencode(self::boardId($boardIdOrUrl));
    }

    /** A board's id from its address - the end of it, after the last "-" - or the id itself. */
    public static function boardId(string $boardIdOrUrl): string
    {
        $last = basename((string) (parse_url(rtrim($boardIdOrUrl, '/'), \PHP_URL_PATH) ?: $boardIdOrUrl));
        $dash = strrpos($last, '-');

        return false === $dash ? $last : substr($last, $dash + 1);
    }

    /** @return array<string, mixed> */
    private function call(string $method, string $path, array $options = []): array
    {
        $key = $this->apiKey->getApiKey() ?? throw new AuthenticationException('No Padlet API key.');
        try {
            $response = $this->http->request($method, $this->baseUrl.$path, array_merge_recursive($options, ['headers' => [$this->keyHeader => $key, 'Accept' => 'application/json']]));
            $status = $response->getStatusCode();
            $content = $response->getContent(false);
        } catch (ExceptionInterface $e) {
            throw new ApiException('Padlet could not be reached: '.$e->getMessage(), 0, null, $e);
        }
        if (401 === $status || 403 === $status) {
            throw new AuthenticationException(sprintf('Padlet refused the API key (%d).', $status));
        }
        $data = '' === $content ? [] : (json_decode($content, true) ?? []);
        if ($status >= 400) {
            throw new ApiException(sprintf('Padlet answered %d on %s %s: %s', $status, $method, $path, $data['errors'][0]['detail'] ?? $data['error'] ?? $data['message'] ?? 'no detail'), $status, $content);
        }

        return $data;
    }
}
