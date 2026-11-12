<?php

namespace Tests\Feature\Admin;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AdminUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_forbidden_from_admin_pages(): void
    {
        $member = User::factory()->member()->create();
        $targetUser = User::factory()->member()->create();

        $this->actingAs($member)->get('/admin/users')->assertStatus(403);
        $this->actingAs($member)->get("/admin/users/{$targetUser->id}/posts")->assertStatus(403);
        $this->actingAs($member)->get("/admin/users/{$targetUser->id}/posts/export")->assertStatus(403);
        $this->actingAs($member)->get('/admin/posts')->assertStatus(403);
        $this->actingAs($member)->get('/admin/posts/export')->assertStatus(403);
    }

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->admin()->create();
        $budi = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@test.com']);
        User::factory()->create(['name' => 'Citra Lestari', 'email' => 'citra@test.com']);
        Post::factory()->count(2)->create(['user_id' => $budi->id]);

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserList')
                ->has('users.data')
            );

        $this->actingAs($admin)
            ->get('/admin/users?search=Budi')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserList')
                ->where('users.data.0.name', 'Budi Santoso')
                ->where('users.data.0.posts_count', 2)
            );
    }

    public function test_admin_can_view_user_posts_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->member()->create(['name' => 'Doni Target']);

        Post::factory()->published()->create(['user_id' => $targetUser->id, 'title' => 'Post Spesifik Doni', 'category' => 'Bisnis']);
        Post::factory()->draft()->create(['user_id' => $targetUser->id, 'title' => 'Post Lain Doni', 'category' => 'Opini']);

        $this->actingAs($admin)
            ->get("/admin/users/{$targetUser->id}/posts")
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/UserPostList')
                ->where('targetUser.name', 'Doni Target')
                ->has('posts.data', 2)
                ->has('categories', 6)
            );

        $this->actingAs($admin)
            ->get("/admin/users/{$targetUser->id}/posts?search=Spesifik")
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Post Spesifik Doni')
            );

        $this->actingAs($admin)
            ->get("/admin/users/{$targetUser->id}/posts?status=draft")
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('posts.data.0.title', 'Post Lain Doni')
            );

        $this->actingAs($admin)
            ->get("/admin/users/{$targetUser->id}/posts?category=Bisnis")
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('filters.category', 'Bisnis')
            );
    }

    public function test_admin_can_export_user_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->member()->create(['name' => 'Budi Target']);
        Post::factory()->count(2)->create(['user_id' => $targetUser->id]);

        $response = $this->actingAs($admin)->get("/admin/users/{$targetUser->id}/posts/export");

        $response->assertStatus(200);
        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
    }
}
