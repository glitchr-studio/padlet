# glitchr/padlet

[Padlet's API](https://docs.padlet.dev/) in PHP, the way the Omni family does
its integrations: a small library with no framework in it, and a Symfony
bundle beside it.

Padlet's API is young and still marked beta by Padlet: what this library
relies on is its JSON:API shape (`data`, `included`, `attributes`,
`relationships`), the `X-API-KEY` header, and the `/v1/boards/{id}` and
`/v1/boards/{id}/posts` endpoints. The base URL and the key header are
configurable, and every answer is read leniently: a field Padlet renames
leaves the model empty rather than the call broken. **Check
docs.padlet.dev when a call fails**: the harness below prints the raw
answer.

What it covers, for the account whose API key is given:

- `Client::me()`: the account (when the API offers it).
- `Client::board($id)`: a board, with its sections and posts
  (`?include=posts,sections`), as `Board`, `Section`, `Post` models: title,
  body, colour, attachment URL, the section a post is in, dates.
- `Client::posts($boardId)`: the posts alone.
- `Client::createPost($boardId, $subject, $body, $sectionId)`: a post added.
- `Client::embedUrl($boardIdOrUrl)`: the embed address of a board.

Errors are `Padlet\Exception\PadletException` (`AuthenticationException` on
a refused key, `ApiException` with the status otherwise).

## Install

```bash
composer require glitchr/padlet
```

The key comes from https://padlet.com/dashboard/settings/api.

## Symfony

```php
// config/bundles.php
Padlet\Bridge\Symfony\PadletBundle::class => ['all' => true],
```

```yaml
# config/packages/padlet.yaml
padlet:
    api_key: '%env(PADLET_API_KEY)%'
    # api_key_provider: App\Tools\PadletKey   # a Padlet\ApiKeyProviderInterface, when the key is typed in a back office
```

## Try it

```bash
cd docker && cp .env.dist .env   # PADLET_API_KEY
docker compose run --rm padlet me
docker compose run --rm padlet board <id>
docker compose run --rm padlet raw /boards/<id>?include=posts
docker compose run --rm padlet test
```
