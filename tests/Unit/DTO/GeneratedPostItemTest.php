<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedPostItem;
use PHPUnit\Framework\TestCase;

class GeneratedPostItemTest extends TestCase
{
    public function test_to_array_contains_all_fields(): void
    {
        $item = new GeneratedPostItem('Judul', 'Konten **md**', 'Bisnis', 'Ringkasan');

        $this->assertSame([
            'title' => 'Judul',
            'category' => 'Bisnis',
            'excerpt' => 'Ringkasan',
            'content' => 'Konten **md**',
        ], $item->toArray());
    }

    public function test_defaults(): void
    {
        $item = new GeneratedPostItem('Judul', 'Isi');

        $this->assertSame('Umum', $item->category);
        $this->assertNull($item->excerpt);
    }
}
