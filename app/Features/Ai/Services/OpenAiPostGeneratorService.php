<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedPostItem;
use App\Features\Post\Enums\PostCategory;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiPostGeneratorService implements AiClientInterface
{
    protected string $endpoint;
    protected string $apiKey;
    protected string $model;

    public function __construct(
        ?string $endpoint = null,
        ?string $apiKey = null,
        ?string $model = null,
    ) {
        $resolvedEndpoint = (string) ($endpoint ?? config('openai.endpoint', 'https://api.openai.com/v1'));
        $resolvedKey = (string) ($apiKey ?? config('openai.key', ''));

        // Toleransi jika nilai OPENAI_ENDPOINT dan OPENAI_KEY tertukar di .env
        if ((str_starts_with($resolvedKey, 'http://') || str_starts_with($resolvedKey, 'https://')) &&
            (!str_starts_with($resolvedEndpoint, 'http://') && !str_starts_with($resolvedEndpoint, 'https://'))) {
            [$resolvedEndpoint, $resolvedKey] = [$resolvedKey, $resolvedEndpoint];
        }

        // Pastikan scheme http:// atau https:// selalu ada
        if (!empty($resolvedEndpoint) && !str_starts_with($resolvedEndpoint, 'http://') && !str_starts_with($resolvedEndpoint, 'https://')) {
            $resolvedEndpoint = 'https://'.$resolvedEndpoint;
        }

        $this->endpoint = rtrim($resolvedEndpoint, '/');
        $this->apiKey = $resolvedKey;
        $this->model = $model ?? (string) config('openai.model', 'gpt-4o-mini');
    }

    /**
     * Generate multiple structured post drafts based on a user prompt.
     *
     * @return array<int, GeneratedPostItem>
     *
     * @throws RuntimeException
     */
    public function generatePosts(string $prompt, int $count = 3): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new RuntimeException('Prompt tidak boleh kosong.');
        }

        $count = max(1, $count);
        $categories = implode(', ', PostCategory::values());

        $systemPrompt = <<<SYS
You are an expert blog writer. Generate exactly {$count} complete blog article drafts in Indonesian based on the user's request.

You MUST respond strictly with a valid JSON object matching this schema:
{
  "posts": [
    {
      "title": "Catchy article title (max 120 chars)",
      "category": "One of: {$categories}",
      "excerpt": "Engaging summary of 1-2 sentences",
      "content": "Full article body formatted as Markdown (headings, paragraphs, lists)"
    }
  ]
}

Do not include any conversational text, introductory words, or explanations outside the JSON.
SYS;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(60)->post("{$this->endpoint}/chat/completions", [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
        ]);

        if (!$response->successful()) {
            $errorMessage = $response->json('error.message') ?? "Request failed with status code {$response->status()}";
            throw new RuntimeException("OpenAI API Error: {$errorMessage}");
        }

        return $this->parseContent((string) $response->json('choices.0.message.content'));
    }

    /**
     * Parse the raw model output into post DTOs.
     *
     * @return array<int, GeneratedPostItem>
     */
    public function parseContent(string $rawContent): array
    {
        $clean = trim($rawContent);

        // Remove Markdown code fences if present
        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean);
            $clean = preg_replace('/\s*```$/', '', (string) $clean);
            $clean = trim((string) $clean);
        }

        $decoded = json_decode($clean, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('AI mengembalikan format respon yang tidak valid.');
        }

        // Support both {"posts": [...]} and [...]
        $items = isset($decoded['posts']) && is_array($decoded['posts'])
            ? $decoded['posts']
            : (array_is_list($decoded) ? $decoded : []);

        if (empty($items)) {
            throw new RuntimeException('Tidak ada artikel yang berhasil di-generate dari AI.');
        }

        $results = [];
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['title']) || empty($item['content'])) {
                continue;
            }

            $excerpt = isset($item['excerpt']) && trim((string) $item['excerpt']) !== ''
                ? trim((string) $item['excerpt'])
                : null;

            $results[] = new GeneratedPostItem(
                title: trim((string) $item['title']),
                content: trim((string) $item['content']),
                category: PostCategory::tryFrom((string) ($item['category'] ?? ''))?->value ?? PostCategory::Umum->value,
                excerpt: $excerpt,
            );
        }

        if (empty($results)) {
            throw new RuntimeException('Format artikel yang dikembalikan AI tidak sesuai spesifikasi.');
        }

        return $results;
    }
}
