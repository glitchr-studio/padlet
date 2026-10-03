<?php

namespace Padlet\Model;

/** A board: its title and description, its address, its sections and posts when they were asked for. */
final class Board
{
    /**
     * @param list<Section> $sections
     * @param list<Post>    $posts
     */
    public function __construct(
        public readonly string $id,
        public readonly ?string $title,
        public readonly ?string $description,
        public readonly ?string $url,
        public readonly ?string $format,
        public readonly array $sections = [],
        public readonly array $posts = [],
    ) {
    }

    /** @param array<string, mixed> $document the whole JSON:API answer of GET /boards/{id} */
    public static function fromDocument(array $document): self
    {
        $data = $document['data'] ?? [];
        $a = $data['attributes'] ?? [];
        $sections = [];
        $posts = [];
        foreach ($document['included'] ?? [] as $resource) {
            match ($resource['type'] ?? null) {
                'section' => $sections[] = Section::fromResource($resource),
                'post' => $posts[] = Post::fromResource($resource),
                default => null,
            };
        }

        return new self((string) ($data['id'] ?? ''), $a['title'] ?? null, $a['description'] ?? null, $a['url'] ?? ($a['links']['self'] ?? null), $a['format'] ?? null, $sections, $posts);
    }

    public function section(?string $id): ?Section
    {
        foreach ($this->sections as $section) {
            if ($section->id === $id) {
                return $section;
            }
        }

        return null;
    }
}
