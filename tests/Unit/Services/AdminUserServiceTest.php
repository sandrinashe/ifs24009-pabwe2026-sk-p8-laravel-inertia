<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminUserService(new PostRepository());
    }

    public function test_get_users_list_without_search(): void
    {
        User::factory()->count(3)->create();

        $list = $this->service->getUsersList();
        $this->assertCount(3, $list);
    }

    public function test_get_users_list_with_search(): void
    {
        User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@test.com']);
        User::factory()->create(['name' => 'Bob Marley', 'email' => 'bob@test.com']);

        $resultsByName = $this->service->getUsersList(search: 'Alice');
        $this->assertCount(1, $resultsByName);

        $resultsByEmail = $this->service->getUsersList(search: 'bob@test.com');
        $this->assertCount(1, $resultsByEmail);

        User::factory()->create(['name' => 'Charlie Chaplin', 'username' => 'charlie_legend', 'email' => 'charlie@test.com']);
        $resultsByUsername = $this->service->getUsersList(search: 'charlie_legend');
        $this->assertCount(1, $resultsByUsername);
    }

    public function test_get_user_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(4)->create(['user_id' => $user->id]);

        $posts = $this->service->getUserPosts($user->id);
        $this->assertCount(4, $posts);
    }

    public function test_users_list_contains_posts_count(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);

        $list = $this->service->getUsersList();
        $this->assertEquals(2, $list->first()->posts_count);
    }

    public function test_get_user_posts_with_filters(): void
    {
        $user = User::factory()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Alpha Satu', 'category' => 'Bisnis']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Beta Dua', 'category' => 'Opini']);

        $this->assertCount(1, $this->service->getUserPosts($user->id, 'published'));
        $this->assertCount(1, $this->service->getUserPosts($user->id, null, 'Beta'));
        $this->assertCount(1, $this->service->getUserPosts($user->id, null, null, 10, 'Bisnis'));
    }
}

