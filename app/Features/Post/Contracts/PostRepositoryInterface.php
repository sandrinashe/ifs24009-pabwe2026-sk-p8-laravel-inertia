<?php

namespace App\Features\Post\Contracts;

use App\Features\Post\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface PostRepositoryInterface
{
    /**
     * Paginated posts of a single author with optional status (published|draft), category and search filters.
     */
    public function forUser(int $userId, ?string $status = null, ?string $search = null, int $perPage = 10, ?string $category = null): LengthAwarePaginator;

    /**
     * All posts of a single author without pagination.
     *
     * @return Collection<int, Post>
     */
    public function allForUser(int $userId): Collection;

    /**
     * All posts in the system (with their authors) without pagination.
     *
     * @return Collection<int, Post>
     */
    public function all(): Collection;

    /**
     * Paginated posts across all authors (admin monitoring).
     */
    public function paginateAll(?string $status = null, ?string $search = null, int $perPage = 10, ?string $category = null): LengthAwarePaginator;

    /**
     * Paginated published posts for the public gallery.
     */
    public function paginatePublished(?string $search = null, ?string $category = null, int $perPage = 9): LengthAwarePaginator;

    /**
     * Find a published post by its slug.
     */
    public function findPublishedBySlug(string $slug): ?Post;

    /**
     * Determine whether a slug is already used (optionally ignoring a post id).
     */
    public function slugExists(string $slug, ?int $ignoreId = null): bool;

    /**
     * Create a new post.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Post;

    /**
     * Update an existing post.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(Post $post, array $attributes): bool;

    /**
     * Delete an existing post.
     */
    public function delete(Post $post): bool;

    /**
     * Statistics for a given author.
     *
     * @return array{total: int, published: int, draft: int}
     */
    public function getStatsForUser(int $userId): array;
}
