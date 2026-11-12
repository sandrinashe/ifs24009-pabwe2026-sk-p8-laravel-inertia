<?php

namespace App\Features\Ai\DTO;

class GeneratedPostItem
{
    public function __construct(
        public string $title,
        public string $content,
        public string $category = 'Umum',
        public ?string $excerpt = null,
    ) {}

    /**
     * Convert the DTO to an associative array for persistence.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'category' => $this->category,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
        ];
    }
}
