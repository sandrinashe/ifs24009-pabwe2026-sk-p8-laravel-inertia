<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_renders_public_gallery_for_guest(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/PostIndex')
                ->has('posts')
                ->has('categories', 6)
            );
    }

    public function test_articles_renders_public_gallery_for_member(): void
    {
        $member = User::factory()->member()->create();

        $this->actingAs($member)
            ->get('/articles')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page->component('Public/PostIndex'));
    }

    public function test_login_and_register_pages_are_reachable_by_guest(): void
    {
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
    }

    public function test_guest_is_redirected_from_protected_areas(): void
    {
        $this->get('/dashboard/posts')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
        $this->get('/admin/posts')->assertRedirect(route('login'));
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_health_check_endpoint(): void
    {
        $this->get('/up')->assertStatus(200);
    }
}
