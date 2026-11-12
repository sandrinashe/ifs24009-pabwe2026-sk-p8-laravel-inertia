<?php

namespace Tests\Unit\Models;

use App\Features\Post\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $post->user);
        $this->assertSame($user->id, $post->user->id);
    }

    public function test_casts(): void
    {
        $post = Post::factory()->published()->create()->fresh();

        $this->assertIsBool($post->is_published);
        $this->assertTrue($post->is_published);
        $this->assertInstanceOf(Carbon::class, $post->published_at);
    }

    public function test_factory_states(): void
    {
        $published = Post::factory()->published()->make();
        $draft = Post::factory()->draft()->make();

        $this->assertTrue($published->is_published);
        $this->assertNotNull($published->published_at);
        $this->assertFalse($draft->is_published);
        $this->assertNull($draft->published_at);
    }

    public function test_cover_image_url(): void
    {
        Storage::fake('public');

        $withoutCover = Post::factory()->create(['cover_image_path' => null]);
        $this->assertNull($withoutCover->coverImageUrl());

        $missingFile = Post::factory()->create(['cover_image_path' => 'post-covers/missing.jpg']);
        $this->assertNull($missingFile->coverImageUrl());

        Storage::disk('public')->put('post-covers/ok.jpg', 'x');
        $withCover = Post::factory()->create(['cover_image_path' => 'post-covers/ok.jpg']);
        $this->assertStringContainsString('post-covers/ok.jpg', (string) $withCover->coverImageUrl());
    }

    public function test_slug_is_unique_in_database(): void
    {
        Post::factory()->create(['slug' => 'sama']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Post::factory()->create(['slug' => 'sama']);
    }

    public function test_deleting_user_cascades_to_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertDatabaseCount('posts', 0);
    }
}
