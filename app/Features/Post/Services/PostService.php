<?php

namespace App\Features\Post\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService
{
    public function __construct(
        protected PostRepositoryInterface $postRepository
    ) {}

    /**
     * Paginated posts of the given author.
     */
    public function getPostsForUser(int $userId, ?string $status = null, ?string $search = null, int $perPage = 10, ?string $category = null): LengthAwarePaginator
    {
        return $this->postRepository->forUser($userId, $status, $search, $perPage, $category);
    }

    /**
     * Paginated published posts for the public gallery.
     */
    public function getPublishedPosts(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator
    {
        return $this->postRepository->paginatePublished($search, $category, $perPage);
    }

    /**
     * Find a published post by slug.
     */
    public function findPublishedBySlug(string $slug): ?Post
    {
        return $this->postRepository->findPublishedBySlug($slug);
    }

    /**
     * Paginated posts across all authors (admin monitoring).
     */
    public function getAllPosts(?string $status = null, ?string $search = null, int $perPage = 10, ?string $category = null): LengthAwarePaginator
    {
        return $this->postRepository->paginateAll($status, $search, $perPage, $category);
    }

    /**
     * Every post of the author (for export).
     *
     * @return Collection<int, Post>
     */
    public function getAllPostsForUser(int $userId): Collection
    {
        return $this->postRepository->allForUser($userId);
    }

    /**
     * Every post in the system with authors (for admin export).
     *
     * @return Collection<int, Post>
     */
    public function getEveryPost(): Collection
    {
        return $this->postRepository->all();
    }

    /**
     * Create a new post for the author.
     *
     * @param array<string, mixed> $data
     */
    public function createPost(int $userId, array $data): Post
    {
        $isPublished = (bool) ($data['is_published'] ?? false);

        return $this->postRepository->create([
            'user_id' => $userId,
            'title' => $data['title'],
            'slug' => $this->generateUniqueSlug($data['title']),
            'category' => $data['category'] ?? PostCategory::Umum->value,
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'] ?? '',
            'cover_image_path' => $data['cover_image_path'] ?? null,
            'is_published' => $isPublished,
            'published_at' => $isPublished ? now() : null,
        ]);
    }

    /**
     * Update an existing post. The slug stays stable so published URLs do not break.
     *
     * @param array<string, mixed> $data
     */
    public function updatePost(Post $post, array $data): bool
    {
        $isPublished = array_key_exists('is_published', $data) ? (bool) $data['is_published'] : $post->is_published;

        return $this->postRepository->update($post, [
            'title' => $data['title'] ?? $post->title,
            'category' => $data['category'] ?? $post->category,
            'excerpt' => array_key_exists('excerpt', $data) ? $data['excerpt'] : $post->excerpt,
            'content' => $data['content'] ?? $post->content,
            'is_published' => $isPublished,
            'published_at' => $this->resolvePublishedAt($post, $isPublished),
        ]);
    }

    /**
     * Toggle between draft and published.
     */
    public function togglePublish(Post $post): bool
    {
        return $this->updatePost($post, ['is_published' => !$post->is_published]);
    }

    /**
     * Upload or replace the cover image.
     */
    public function updateCoverPhoto(Post $post, mixed $file): string
    {
        $this->removeCoverFile($post);

        $path = $file->store('post-covers', 'public');
        $this->postRepository->update($post, ['cover_image_path' => $path]);

        return $path;
    }

    /**
     * Delete the cover image.
     */
    public function deleteCoverPhoto(Post $post): bool
    {
        $this->removeCoverFile($post);

        return $this->postRepository->update($post, ['cover_image_path' => null]);
    }

    /**
     * Delete a post together with its cover image.
     */
    public function deletePost(Post $post): bool
    {
        $this->removeCoverFile($post);

        return $this->postRepository->delete($post);
    }

    /**
     * Statistics summary of the author.
     *
     * @return array{total: int, published: int, draft: int}
     */
    public function getStatsForUser(int $userId): array
    {
        return $this->postRepository->getStatsForUser($userId);
    }

    /**
     * Build a unique slug from a title.
     */
    public function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title);
        $base = $base === '' ? 'artikel' : Str::limit($base, 200, '');

        $slug = $base;
        $counter = 2;

        while ($this->postRepository->slugExists($slug, $ignoreId)) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Keep the original publish time, set it on first publish, clear it on draft.
     */
    protected function resolvePublishedAt(Post $post, bool $isPublished): mixed
    {
        if (!$isPublished) {
            return null;
        }

        return $post->published_at ?? now();
    }

    /**
     * Remove the stored cover file (if any).
     */
    protected function removeCoverFile(Post $post): void
    {
        if ($post->cover_image_path && Storage::disk('public')->exists($post->cover_image_path)) {
            Storage::disk('public')->delete($post->cover_image_path);
        }
    }
}
