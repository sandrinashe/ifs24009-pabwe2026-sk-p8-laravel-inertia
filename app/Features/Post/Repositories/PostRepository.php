<?php

namespace App\Features\Post\Repositories;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PostRepository implements PostRepositoryInterface
{
    /**
     * Paginated posts of a single author with optional status, category and search filters.
     */
    public function forUser(int $userId, ?string $status = null, ?string $search = null, int $perPage = 10, ?string $category = null): LengthAwarePaginator
    {
        $query = Post::query()->where('user_id', $userId);

        return $this->applyFilters($query, $status, $search, $category)
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * All posts of a single author without pagination.
     *
     * @return Collection<int, Post>
     */
    public function allForUser(int $userId): Collection
    {
        return Post::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->get();
    }

    /**
     * All posts in the system (with their authors) without pagination.
     *
     * @return Collection<int, Post>
     */
    public function all(): Collection
    {
        return Post::query()->with('user')->latest('id')->get();
    }

    /**
     * Paginated posts across all authors (admin monitoring).
     */
    public function paginateAll(?string $status = null, ?string $search = null, int $perPage = 10, ?string $category = null): LengthAwarePaginator
    {
        $query = Post::query()->with('user');

        return $this->applyFilters($query, $status, $search, $category)
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Paginated published posts for the public gallery.
     */
    public function paginatePublished(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator
    {
        $query = Post::query()->with('user')->where('is_published', true);

        return $this->applyFilters($query, null, $search, $category)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Find a published post by its slug.
     */
    public function findPublishedBySlug(string $slug): ?Post
    {
        return Post::query()
            ->with('user')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->first();
    }

    /**
     * Determine whether a slug is already used (optionally ignoring a post id).
     */
    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Post::query()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    /**
     * Create a new post.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Post
    {
        return Post::create($attributes);
    }

    /**
     * Update an existing post.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(Post $post, array $attributes): bool
    {
        return $post->update($attributes);
    }

    /**
     * Delete an existing post.
     */
    public function delete(Post $post): bool
    {
        return (bool) $post->delete();
    }

    /**
     * Statistics for a given author.
     *
     * @return array{total: int, published: int, draft: int}
     */
    public function getStatsForUser(int $userId): array
    {
        $total = Post::where('user_id', $userId)->count();
        $published = Post::where('user_id', $userId)->where('is_published', true)->count();

        return [
            'total' => $total,
            'published' => $published,
            'draft' => $total - $published,
        ];
    }

    /**
     * Apply the shared status, category and search filters.
     *
     * @param Builder<Post> $query
     * @return Builder<Post>
     */
    protected function applyFilters(Builder $query, ?string $status, ?string $search, ?string $category): Builder
    {
        if ($status === 'published') {
            $query->where('is_published', true);
        } elseif ($status === 'draft') {
            $query->where('is_published', false);
        }

        if (!empty($category)) {
            $query->where('category', $category);
        }

        if (!empty($search)) {
            // ILIKE keeps the search case-insensitive on PostgreSQL; fall back to LIKE elsewhere.
            $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $query->where(function (Builder $q) use ($search, $operator) {
                $q->where('title', $operator, "%{$search}%")
                    ->orWhere('content', $operator, "%{$search}%");
            });
        }

        return $query;
    }
}
