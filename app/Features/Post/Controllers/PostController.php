<?php

namespace App\Features\Post\Controllers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostController
{
    public function index(Request $request, PostService $service): Response
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $category = $request->query('category');

        $posts = $service->getPostsForUser(
            (int) Auth::id(),
            $status ?: null,
            $search ?: null,
            8,
            $category ?: null
        );

        return Inertia::render('Post/Index', [
            'posts' => $posts,
            'filters' => [
                'search' => $search ?? '',
                'status' => $status ?? '',
                'category' => $category ?? '',
            ],
            'categories' => PostCategory::options(),
            'stats' => $service->getStatsForUser((int) Auth::id()),
        ]);
    }

    public function store(Request $request, PostService $service): RedirectResponse
    {
        $service->createPost((int) Auth::id(), $this->validatePost($request));

        return back()->with('success', 'Artikel baru berhasil disimpan.');
    }

    public function show(Post $post): Response
    {
        Gate::authorize('view', $post);

        return Inertia::render('Post/Show', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category' => $post->category,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'cover_image_path' => $post->cover_image_path,
                'is_published' => $post->is_published,
                'published_at' => $post->published_at?->format('d M Y H:i'),
                'created_at' => $post->created_at?->format('d M Y H:i'),
                'updated_at' => $post->updated_at?->format('d M Y H:i'),
            ],
            'categories' => PostCategory::options(),
        ]);
    }

    public function update(Request $request, Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->updatePost($post, $this->validatePost($request));

        return back()->with('success', 'Artikel berhasil diperbarui.');
    }

    public function destroy(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('delete', $post);

        $service->deletePost($post);

        return redirect()->route('posts.index')->with('success', 'Artikel berhasil dihapus.');
    }

    public function togglePublish(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->togglePublish($post);

        return back()->with('success', 'Status publikasi artikel berhasil diperbarui.');
    }

    public function updateCover(Request $request, Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $request->validate([
            'coverPhoto' => ['required', 'image', 'max:5120'],
        ]);

        $service->updateCoverPhoto($post, $request->file('coverPhoto'));

        return back()->with('success', 'Foto cover berhasil diperbarui.');
    }

    public function deleteCover(Post $post, PostService $service): RedirectResponse
    {
        Gate::authorize('update', $post);

        $service->deleteCoverPhoto($post);

        return back()->with('success', 'Foto cover berhasil dihapus.');
    }

    public function exportExcel(PostService $service, PostExcelService $excelService): StreamedResponse
    {
        $posts = $service->getAllPostsForUser((int) Auth::id());

        return $excelService->exportToResponse($posts,'artikel-'.now()->format('Ymd-His').'.xlsx');
    }

    public function importExcel(Request $request, PostExcelService $excelService): RedirectResponse
    {
        $request->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = $excelService->import($request->file('importFile')->getRealPath(), (int) Auth::id());

        return back()->with('success', "Berhasil mengimpor {$result['imported']} artikel.");
    }

    public function generateAi(Request $request, AiClientInterface $aiClient): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'min:3', 'max:1000'],
            'count' => ['required', 'integer', 'min:1', 'max:5'],
        ], [
            'prompt.required' => 'Topik / instruksi artikel harus diisi minimal 3 karakter.',
            'prompt.min' => 'Topik / instruksi artikel harus minimal 3 karakter.',
            'count.required' => 'Jumlah artikel harus diisi.',
            'count.integer' => 'Jumlah artikel harus berupa angka.',
            'count.min' => 'Jumlah artikel minimal 1.',
            'count.max' => 'Jumlah artikel maksimal 5.',
        ]);

        try {
            $items = $aiClient->generatePosts($validated['prompt'], (int) $validated['count']);
            $data = array_map(fn ($item) => $item->toArray(), $items);

            if ($request->wantsJson()) {
                return response()->json(['success' => true, 'posts' => $data]);
            }

            return back()->with('aiGeneratedItems', $data);
        } catch (\Throwable $e) {
            $message = 'Gagal menghasilkan artikel dengan AI: '.$e->getMessage();

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }
    }

    public function saveAi(Request $request, PostService $service): RedirectResponse
    {
        $validated = $request->validate([
            'posts' => ['required', 'array', 'min:1', 'max:20'],
            'posts.*.title' => ['required', 'string', 'max:255'],
            'posts.*.content' => ['required', 'string'],
            'posts.*.category' => ['nullable', Rule::enum(PostCategory::class)],
            'posts.*.excerpt' => ['nullable', 'string'],
        ]);

        foreach ($validated['posts'] as $item) {
            $service->createPost((int) Auth::id(), [
                'title' => $item['title'],
                'category' => $item['category'] ?? PostCategory::Umum->value,
                'excerpt' => $item['excerpt'] ?? null,
                'content' => $item['content'],
                'is_published' => false,
            ]);
        }

        $count = count($validated['posts']);

        return back()->with('success', "Berhasil menyimpan {$count} draf artikel hasil AI.");
    }

    /**
     * Validate the create / update payload.
     *
     * @return array<string, mixed>
     */
    protected function validatePost(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::enum(PostCategory::class)],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'is_published' => ['required', 'boolean'],
        ]);
    }
}
