<?php

namespace App\Features\Admin\Services;

use App\Features\Post\Contracts\PostRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(
        protected PostRepositoryInterface $postRepository
    ) {}

    /**
     * Paginated list of all users with their posts count.
     */
    public function getUsersList(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = User::query()->withCount('posts');

        if (!empty($search)) {
            $operator = $query->getConnection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $query->where(function ($q) use ($search, $operator) {
                $q->where('name', $operator, "%{$search}%")
                    ->orWhere('username', $operator, "%{$search}%")
                    ->orWhere('email', $operator, "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Read-only paginated posts of a specific user.
     */
    public function getUserPosts(int $userId, ?string $status = null, ?string $search = null, int $perPage = 10, ?string $category = null): LengthAwarePaginator
    {
        return $this->postRepository->forUser($userId, $status, $search, $perPage, $category);
    }
}
