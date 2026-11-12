<?php

namespace Tests\Unit\Services;

use App\Features\Ai\Services\OpenAiPostGeneratorService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiPostGeneratorServiceTest extends TestCase
{
    private function service(): OpenAiPostGeneratorService
    {
        return new OpenAiPostGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');
    }

    public function test_empty_prompt_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prompt tidak boleh kosong.');
        $this->service()->generatePosts('   ');
    }

    public function test_successful_generation(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'posts' => [
                                [
                                    'title' => 'Belajar Laravel',
                                    'category' => 'Teknologi',
                                    'excerpt' => 'Ringkasan singkat.',
                                    'content' => '## Pengantar',
                                ],
                                [
                                    'title' => 'Artikel Tanpa Kategori',
                                    'category' => 'Tidak Dikenal',
                                    'excerpt' => '   ',
                                    'content' => 'Isi artikel',
                                ],
                            ],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $results = $this->service()->generatePosts('Buatkan 2 artikel', 0);

        $this->assertCount(2, $results);
        $this->assertSame('Belajar Laravel', $results[0]->title);
        $this->assertSame('Teknologi', $results[0]->category);
        $this->assertSame('Ringkasan singkat.', $results[0]->excerpt);
        $this->assertSame('Umum', $results[1]->category);
        $this->assertNull($results[1]->excerpt);

        Http::assertSent(fn ($request) => str_contains($request['messages'][0]['content'], 'exactly 1 complete'));
    }

    public function test_api_failure_with_error_message(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'Invalid API Key provided']], 401),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: Invalid API Key provided');
        $this->service()->generatePosts('Topik');
    }

    public function test_api_failure_without_error_message(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Request failed with status code 500');
        $this->service()->generatePosts('Topik');
    }

    public function test_parse_content_with_markdown_fences_and_plain_list(): void
    {
        $raw = "```json\n[{\"title\": \"Judul\", \"content\": \"Isi\"}]\n```";

        $results = $this->service()->parseContent($raw);

        $this->assertCount(1, $results);
        $this->assertSame('Judul', $results[0]->title);
        $this->assertSame('Umum', $results[0]->category);
    }

    public function test_parse_content_invalid_json(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI mengembalikan format respon yang tidak valid.');
        $this->service()->parseContent('bukan json');
    }

    public function test_parse_content_empty_posts(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada artikel yang berhasil di-generate dari AI.');
        $this->service()->parseContent('{"posts": []}');
    }

    public function test_parse_content_without_posts_key_and_not_a_list(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada artikel yang berhasil di-generate dari AI.');
        $this->service()->parseContent('{"foo": "bar"}');
    }

    public function test_parse_content_without_valid_items(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format artikel yang dikembalikan AI tidak sesuai spesifikasi.');
        $this->service()->parseContent('{"posts": [{"bad": "x"}, "string", {"title": "Tanpa isi"}]}');
    }

    public function test_constructor_handles_swapped_endpoint_and_key(): void
    {
        Http::fake([
            'https://ai.example.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"posts":[{"title":"A","content":"B"}]}']]],
            ]),
        ]);

        $service = new OpenAiPostGeneratorService('sk-secret-key', 'https://ai.example.com/v1');

        $this->assertCount(1, $service->generatePosts('Topik'));
    }

    public function test_constructor_auto_prefixes_https(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"posts":[{"title":"A","content":"B"}]}']]],
            ]),
        ]);

        $service = new OpenAiPostGeneratorService('api.openai.com/v1', 'mock-key');

        $this->assertCount(1, $service->generatePosts('Topik'));
    }

    public function test_constructor_reads_defaults_from_config(): void
    {
        config([
            'openai.endpoint' => 'https://config.example.com/v1/',
            'openai.key' => 'config-key',
            'openai.model' => 'config-model',
        ]);

        Http::fake([
            'https://config.example.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"posts":[{"title":"A","content":"B"}]}']]],
            ]),
        ]);

        (new OpenAiPostGeneratorService())->generatePosts('Topik');

        Http::assertSent(fn ($request) => $request['model'] === 'config-model'
            && $request->hasHeader('Authorization', 'Bearer config-key'));
    }
}
