<?php

namespace Padlet;

final class StaticApiKeyProvider implements ApiKeyProviderInterface
{
    public function __construct(private readonly ?string $apiKey)
    {
    }

    public function getApiKey(): ?string
    {
        return '' === $this->apiKey ? null : $this->apiKey;
    }
}
