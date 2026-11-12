<?php

namespace Tests\Feature\Post;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_only_published_posts(): void
    {
        $author = User::factory()->create(['name' => 'Penulis Satu']);
        Post::factory()->published()->create(['user_id' => $author->id, 'title' => 'Artikel Terbit']);
        Post::factory()->draft()->create(['user_id' => $author->id, 'title' => 'Artikel Draf']);

        $this->get('/articles')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Artikel Terbit')
                ->where('posts.data.0.author', 'Penulis Satu')
            );
    }

    public function test_guest_can_search_and_filter_by_category(): void
    {
        Post::factory()->published()->create(['title' => 'Belajar Laravel', 'content' => 'isi biasa', 'category' => 'Teknologi']);
        Post::factory()->published()->create(['title' => 'Resep Sehat', 'content' => 'mengandung kata unik kubis', 'category' => 'Gaya Hidup']);

        $this->get('/articles?search=Laravel')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Belajar Laravel')
                ->where('filters.search', 'Laravel')
            );

        // Pencarian juga mencakup konten
        $this->get('/articles?search=kubis')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Resep Sehat')
            );

        $this->get('/articles?category=Gaya%20Hidup')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.category', 'Gaya Hidup')
                ->where('filters.category', 'Gaya Hidup')
            );
    }

    public function test_guest_can_read_published_article_by_slug(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Artikel Lengkap',
            'slug' => 'artikel-lengkap',
            'content' => "# Judul\n\nIsi **markdown**",
        ]);

        $this->get('/articles/artikel-lengkap')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostShow')
                ->where('post.id', $post->id)
                ->where('post.title', 'Artikel Lengkap')
                ->where('post.content', "# Judul\n\nIsi **markdown**")
            );
    }

    public function test_draft_and_unknown_articles_return_404(): void
    {
        Post::factory()->draft()->create(['slug' => 'masih-draf']);

        $this->get('/articles/masih-draf')->assertStatus(404);
        $this->get('/articles/tidak-ada')->assertStatus(404);
    }
}
