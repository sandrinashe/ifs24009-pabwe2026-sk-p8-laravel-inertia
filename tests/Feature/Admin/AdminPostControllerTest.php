<?php

namespace Tests\Feature\Admin;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AdminPostControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_monitors_all_posts_with_filters(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->published()->create(['title' => 'Terbit Alpha', 'category' => 'Teknologi']);
        Post::factory()->draft()->create(['title' => 'Draf Beta', 'category' => 'Opini']);

        $this->actingAs($admin)
            ->get('/admin/posts')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PostList')
                ->has('posts.data', 2)
                ->has('categories', 6)
            );

        $this->actingAs($admin)->get('/admin/posts?search=Alpha')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('posts.data.0.title', 'Terbit Alpha'));

        $this->actingAs($admin)->get('/admin/posts?status=draft')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('posts.data.0.title', 'Draf Beta'));

        $this->actingAs($admin)->get('/admin/posts?category=Opini')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('filters.category', 'Opini'));
    }

    public function test_admin_can_export_all_posts(): void
    {
        $admin = User::factory()->admin()->create();
        Post::factory()->count(3)->create();

        $response = $this->actingAs($admin)->get('/admin/posts/export');

        $response->assertStatus(200);
        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
    }
}
