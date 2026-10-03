<?php

namespace Padlet\Model;

/** A section (a column, a shelf) of a board. */
final class Section
{
    public function __construct(public readonly string $id, public readonly ?string $title)
    {
    }

    /** @param array<string, mixed> $resource a JSON:API resource of type "section" */
    public static function fromResource(array $resource): self
    {
        return new self((string) ($resource['id'] ?? ''), $resource['attributes']['title'] ?? null);
    }
}
