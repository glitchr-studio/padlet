<?php

namespace Padlet;

/** Where the API key comes from: the configuration, or what an administrator typed in a back office. */
interface ApiKeyProviderInterface
{
    public function getApiKey(): ?string;
}
