<?php

namespace App\Features\Post\Policies;

use App\Features\Post\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Admin can view any post (read-only); members only their own.
     */
    public function view(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->id === $post->user_id;
    }

    /**
     * Any authenticated user may write posts.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the owner can update a post (admin is read-only).
     */
    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }

    /**
     * Only the owner can delete a post (admin is read-only).
     */
    public function delete(User $user, Post $post): bool
    {
        return $user->id === $post->user_id;
    }
}
