<?php

namespace Tests\Feature\Post;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Judul Artikel Baru',
            'category' => 'Teknologi',
            'excerpt' => 'Ringkasan singkat',
            'content' => "## Isi\n\nParagraf",
            'is_published' => false,
        ], $override);
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->get('/dashboard/posts')->assertRedirect(route('login'));
    }

    public function test_member_sees_only_own_posts_with_stats(): void
    {
        $user = User::factory()->member()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Milik Saya']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Draf Saya']);
        Post::factory()->published()->create(['title' => 'Milik Orang Lain']);

        $this->actingAs($user)
            ->get('/dashboard/posts')
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Index')
                ->has('posts.data', 2)
                ->where('stats.total', 2)
                ->where('stats.published', 1)
                ->where('stats.draft', 1)
                ->has('categories', 6)
            );
    }

    public function test_index_search_status_and_category_filters(): void
    {
        $user = User::factory()->member()->create();
        Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Alpha', 'category' => 'Bisnis']);
        Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Beta', 'category' => 'Opini']);

        $this->actingAs($user)->get('/dashboard/posts?search=Alp')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('posts.data.0.title', 'Alpha'));

        $this->actingAs($user)->get('/dashboard/posts?status=draft')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('posts.data.0.title', 'Beta'));

        $this->actingAs($user)->get('/dashboard/posts?status=published')
            ->assertInertia(fn (Assert $page) => $page->has('posts.data', 1)->where('posts.data.0.title', 'Alpha'));

        $this->actingAs($user)->get('/dashboard/posts?category=Opini')
            ->assertInertia(fn (Assert $page) => $page
                ->has('posts.data', 1)
                ->where('filters.category', 'Opini'));
    }

    public function test_member_can_create_draft_and_published_posts(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts', $this->payload())
            ->assertSessionHas('success');

        $draft = Post::where('title', 'Judul Artikel Baru')->firstOrFail();
        $this->assertSame('judul-artikel-baru', $draft->slug);
        $this->assertFalse($draft->is_published);
        $this->assertNull($draft->published_at);
        $this->assertSame($user->id, $draft->user_id);

        $this->actingAs($user)
            ->post('/dashboard/posts', $this->payload(['is_published' => true]))
            ->assertSessionHas('success');

        $published = Post::where('slug', 'judul-artikel-baru-2')->firstOrFail();
        $this->assertTrue($published->is_published);
        $this->assertNotNull($published->published_at);
    }

    public function test_store_validation_errors(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts', $this->payload(['title' => '', 'category' => 'Tidak Ada', 'content' => '']))
            ->assertSessionHasErrors(['title', 'category', 'content']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_member_can_view_own_post_detail(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->published()->create(['user_id' => $user->id, 'title' => 'Detail Saya']);

        $this->actingAs($user)
            ->get("/dashboard/posts/{$post->id}")
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Post/Show')
                ->where('post.title', 'Detail Saya')
                ->where('post.is_published', true)
                ->has('categories', 6)
            );
    }

    public function test_member_cannot_access_other_members_post(): void
    {
        $user = User::factory()->member()->create();
        $other = Post::factory()->create();

        $this->actingAs($user)->get("/dashboard/posts/{$other->id}")->assertStatus(403);
        $this->actingAs($user)->put("/dashboard/posts/{$other->id}", $this->payload())->assertStatus(403);
        $this->actingAs($user)->delete("/dashboard/posts/{$other->id}")->assertStatus(403);
        $this->actingAs($user)->patch("/dashboard/posts/{$other->id}/publish")->assertStatus(403);
    }

    public function test_admin_can_read_but_not_modify_other_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create(['title' => 'Milik Member']);

        $this->actingAs($admin)->get("/dashboard/posts/{$post->id}")->assertStatus(200);
        $this->actingAs($admin)->put("/dashboard/posts/{$post->id}", $this->payload())->assertStatus(403);
        $this->actingAs($admin)->delete("/dashboard/posts/{$post->id}")->assertStatus(403);

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Milik Member']);
    }

    public function test_member_can_update_own_post_and_slug_stays_stable(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->draft()->create(['user_id' => $user->id, 'title' => 'Lama', 'slug' => 'lama']);

        $this->actingAs($user)
            ->put("/dashboard/posts/{$post->id}", $this->payload(['title' => 'Baru', 'is_published' => true]))
            ->assertSessionHas('success');

        $post->refresh();
        $this->assertSame('Baru', $post->title);
        $this->assertSame('lama', $post->slug);
        $this->assertTrue($post->is_published);
        $this->assertNotNull($post->published_at);
    }

    public function test_member_can_delete_own_post_and_cover(): void
    {
        $user = User::factory()->member()->create();
        Storage::disk('public')->put('post-covers/x.jpg', 'img');
        $post = Post::factory()->create(['user_id' => $user->id, 'cover_image_path' => 'post-covers/x.jpg']);

        $this->actingAs($user)
            ->delete("/dashboard/posts/{$post->id}")
            ->assertRedirect(route('posts.index'));

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        Storage::disk('public')->assertMissing('post-covers/x.jpg');
    }

    public function test_member_can_toggle_publish_status(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->draft()->create(['user_id' => $user->id]);

        $this->actingAs($user)->patch("/dashboard/posts/{$post->id}/publish")->assertSessionHas('success');
        $this->assertTrue($post->refresh()->is_published);
        $this->assertNotNull($post->published_at);

        $this->actingAs($user)->patch("/dashboard/posts/{$post->id}/publish");
        $this->assertFalse($post->refresh()->is_published);
        $this->assertNull($post->published_at);
    }

    public function test_member_can_upload_and_delete_cover(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/dashboard/posts/{$post->id}/cover", ['coverPhoto' => UploadedFile::fake()->image('cover.jpg')])
            ->assertSessionHas('success');

        $post->refresh();
        $this->assertNotNull($post->cover_image_path);
        Storage::disk('public')->assertExists($post->cover_image_path);
        $oldPath = $post->cover_image_path;

        $this->actingAs($user)->delete("/dashboard/posts/{$post->id}/cover")->assertSessionHas('success');

        $this->assertNull($post->refresh()->cover_image_path);
        Storage::disk('public')->assertMissing($oldPath);
    }

    public function test_cover_upload_requires_an_image(): void
    {
        $user = User::factory()->member()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->post("/dashboard/posts/{$post->id}/cover", ['coverPhoto' => UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('coverPhoto');
    }

    public function test_export_excel_downloads_own_posts(): void
    {
        $user = User::factory()->member()->create();
        Post::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard/posts/export');

        $response->assertStatus(200);
        $this->assertInstanceOf(StreamedResponse::class, $response->baseResponse);
    }

    public function test_import_excel_creates_posts(): void
    {
        $user = User::factory()->member()->create();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['No', 'Judul', 'Kategori', 'Ringkasan', 'Konten', 'Status'],
            [1, 'Impor Satu', 'Bisnis', 'Ringkas', 'Konten satu', 'Published'],
            [2, 'Impor Dua', 'Tidak Valid', null, 'Konten dua', 'Draft'],
            [3, '', 'Umum', null, 'Tanpa judul', 'Draft'],
        ]);

        $tempFile = tempnam(sys_get_temp_dir(), 'post_excel_').'.xlsx';
        (new Xlsx($spreadsheet))->save($tempFile);
        $uploaded = new UploadedFile($tempFile, 'import.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $this->actingAs($user)
            ->post('/dashboard/posts/import', ['importFile' => $uploaded])
            ->assertSessionHas('success', 'Berhasil mengimpor 2 artikel.');

        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'Impor Satu', 'category' => 'Bisnis', 'is_published' => true]);
        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'Impor Dua', 'category' => 'Umum', 'is_published' => false]);
        $this->assertDatabaseCount('posts', 2);

        @unlink($tempFile);
    }

    public function test_import_requires_a_file(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts/import', [])
            ->assertSessionHasErrors('importFile');
    }

    public function test_ai_generate_returns_json_and_session_payload(): void
    {
        $user = User::factory()->member()->create();

        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generatePosts')->willReturn([
            new GeneratedPostItem('Artikel AI', 'Konten AI', 'Teknologi', 'Ringkasan AI'),
        ]);
        $this->app->instance(AiClientInterface::class, $stub);

        $this->actingAs($user)
            ->postJson('/dashboard/posts/ai-generate', ['prompt' => 'Tulis tentang Laravel', 'count' => 1])
            ->assertStatus(200)
            ->assertJson(['success' => true, 'posts' => [['title' => 'Artikel AI', 'category' => 'Teknologi']]]);

        $this->actingAs($user)
            ->post('/dashboard/posts/ai-generate', ['prompt' => 'Tulis tentang Laravel', 'count' => 1])
            ->assertSessionHas('aiGeneratedItems');
    }

    public function test_ai_generate_handles_client_failure(): void
    {
        $user = User::factory()->member()->create();

        $stub = $this->createStub(AiClientInterface::class);
        $stub->method('generatePosts')->willThrowException(new \RuntimeException('boom'));
        $this->app->instance(AiClientInterface::class, $stub);

        $this->actingAs($user)
            ->postJson('/dashboard/posts/ai-generate', ['prompt' => 'Tulis sesuatu', 'count' => 2])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->actingAs($user)
            ->post('/dashboard/posts/ai-generate', ['prompt' => 'Tulis sesuatu', 'count' => 2])
            ->assertSessionHas('error');
    }

    public function test_ai_generate_validates_count_range(): void
    {
        $user = User::factory()->member()->create();

        foreach ([0, 6, 'abc', null] as $invalid) {
            $this->actingAs($user)
                ->postJson('/dashboard/posts/ai-generate', ['prompt' => 'Tulis sesuatu', 'count' => $invalid])
                ->assertStatus(422)
                ->assertJsonValidationErrors('count');
        }

        $this->actingAs($user)
            ->postJson('/dashboard/posts/ai-generate', ['prompt' => 'ab', 'count' => 1])
            ->assertStatus(422)
            ->assertJsonValidationErrors('prompt');
    }

    public function test_ai_save_stores_drafts(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)
            ->post('/dashboard/posts/ai-save', [
                'posts' => [
                    ['title' => 'AI Satu', 'content' => 'Isi satu', 'category' => 'Pendidikan', 'excerpt' => 'Ringkas'],
                    ['title' => 'AI Dua', 'content' => 'Isi dua'],
                ],
            ])
            ->assertSessionHas('success', 'Berhasil menyimpan 2 draf artikel hasil AI.');

        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'AI Satu', 'category' => 'Pendidikan', 'is_published' => false]);
        $this->assertDatabaseHas('posts', ['user_id' => $user->id, 'title' => 'AI Dua', 'category' => 'Umum', 'is_published' => false]);
    }

    public function test_ai_save_validation(): void
    {
        $user = User::factory()->member()->create();

        $this->actingAs($user)->post('/dashboard/posts/ai-save', [])->assertSessionHasErrors('posts');
        $this->actingAs($user)
            ->post('/dashboard/posts/ai-save', ['posts' => [['title' => '', 'content' => '']]])
            ->assertSessionHasErrors(['posts.0.title', 'posts.0.content']);
    }
}
