<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicPostController
{
    /**
     * Public gallery of published articles (search by title/content, filter by category).
     */
    public function index(Request $request, PostService $service): Response
    {
        $search = $request->query('search');
        $category = $request->query('category');

        $posts = $service->getPublishedPosts($search ?: null, $category ?: null, 9);

        return Inertia::render('Public/PostIndex', [
            'posts' => $posts->through(fn ($post) => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category' => $post->category,
                'excerpt' => $post->excerpt,
                'cover_image_path' => $post->cover_image_path,
                'published_at' => $post->published_at?->format('d M Y'),
                'author' => $post->user?->name,
            ]),
            'filters' => [
                'search' => $search ?? '',
                'category' => $category ?? '',
            ],
            'categories' => PostCategory::options(),
        ]);
    }

    /**
     * Read a single published article.
     */
    public function show(string $slug, PostService $service): Response
    {
        $post = $service->findPublishedBySlug($slug);

        abort_if($post === null, 404, 'Artikel tidak ditemukan.');

        return Inertia::render('Public/PostShow', [
            'post' => [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'category' => $post->category,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'cover_image_path' => $post->cover_image_path,
                'published_at' => $post->published_at?->format('d M Y'),
                'author' => $post->user?->name,
            ],
        ]);
    }
}
