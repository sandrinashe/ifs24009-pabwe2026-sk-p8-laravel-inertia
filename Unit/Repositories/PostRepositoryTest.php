<?php

namespace Tests\Unit\Repositories;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected PostRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new PostRepository();
    }

    public function test_for_user_filters_status_category_and_search(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Belajar Laravel', 'category' => 'Teknologi']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Resep Kopi', 'category' => 'Gaya Hidup']);
        Post::factory()->create(); // milik user lain

        $this->assertSame(2, $this->repository->forUser($user->id)->total());
        $this->assertSame(1, $this->repository->forUser($user->id, 'published')->total());
        $this->assertSame(1, $this->repository->forUser($user->id, 'draft')->total());
        $this->assertSame(1, $this->repository->forUser($user->id, null, null, 10, 'Teknologi')->total());
        $this->assertSame(1, $this->repository->forUser($user->id, null, 'laravel')->total());
    }

    public function test_all_for_user_and_all(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);
        Post::factory()->create();

        $this->assertCount(2, $this->repository->allForUser($user->id));
        $this->assertCount(3, $this->repository->all());
        $this->assertTrue($this->repository->all()->first()->relationLoaded('user'));
    }

    public function test_paginate_all_and_paginate_published(): void
    {
        Post::factory()->published()->create(['title' => 'Artikel Publik']);
        Post::factory()->draft()->create(['title' => 'Artikel Draf']);

        $this->assertSame(2, $this->repository->paginateAll()->total());
        $this->assertSame(1, $this->repository->paginateAll('draft')->total());
        $this->assertSame(1, $this->repository->paginatePublished()->total());
        $this->assertSame(0, $this->repository->paginatePublished('zzzxqq')->total());
    }

    public function test_find_published_by_slug(): void
    {
        Post::factory()->published()->create(['slug' => 'publik']);
        Post::factory()->draft()->create(['slug' => 'draf']);

        $this->assertNotNull($this->repository->findPublishedBySlug('publik'));
        $this->assertNull($this->repository->findPublishedBySlug('draf'));
        $this->assertNull($this->repository->findPublishedBySlug('tidak-ada'));
    }

    public function test_slug_exists_with_and_without_ignored_id(): void
    {
        $post = Post::factory()->create(['slug' => 'slug-unik']);

        $this->assertTrue($this->repository->slugExists('slug-unik'));
        $this->assertFalse($this->repository->slugExists('slug-unik', $post->id));
        $this->assertFalse($this->repository->slugExists('lain'));
    }

    public function test_create_update_delete(): void
    {
        $user = User::factory()->create();
        $post = $this->repository->create([
            'user_id' => $user->id,
            'title' => 'Judul',
            'slug' => 'judul',
            'category' => 'Umum',
            'content' => 'Isi',
        ]);

        $this->assertTrue($this->repository->update($post, ['title' => 'Judul Baru']));
        $this->assertSame('Judul Baru', $post->fresh()->title);
        $this->assertTrue($this->repository->delete($post));
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_get_stats_for_user(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->count(2)->create(['user_id' => $user->id]);
        Post::factory()->draft()->create(['user_id' => $user->id]);

        $this->assertSame(['total' => 3, 'published' => 2, 'draft' => 1], $this->repository->getStatsForUser($user->id));
    }
}
