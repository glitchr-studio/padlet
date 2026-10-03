<?php

namespace Padlet\Model;

/** One post of a board: its subject, its body, its colour, an attachment, the section it is in. */
final class Post
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $subject,
        public readonly ?string $body,
        public readonly ?string $color,
        public readonly ?string $attachmentUrl,
        public readonly ?string $attachmentCaption,
        public readonly ?string $sectionId,
        public readonly ?string $authorId,
        public readonly ?\DateTimeImmutable $createdAt,
        public readonly ?\DateTimeImmutable $updatedAt,
    ) {
    }

    /** @param array<string, mixed> $resource a JSON:API resource of type "post" */
    public static function fromResource(array $resource): self
    {
        $a = $resource['attributes'] ?? [];
        $at = static fn ($v) => \is_string($v) && '' !== $v ? new \DateTimeImmutable($v) : null;

        return new self(
            (string) ($resource['id'] ?? ''),
            $a['subject'] ?? null,
            $a['body'] ?? null,
            $a['color'] ?? null,
            $a['attachment']['url'] ?? ($a['attachment_url'] ?? null),
            $a['attachment']['caption'] ?? null,
            isset($resource['relationships']['section']['data']['id']) ? (string) $resource['relationships']['section']['data']['id'] : null,
            isset($resource['relationships']['author']['data']['id']) ? (string) $resource['relationships']['author']['data']['id'] : null,
            $at($a['created_at'] ?? null),
            $at($a['updated_at'] ?? null),
        );
    }
}
