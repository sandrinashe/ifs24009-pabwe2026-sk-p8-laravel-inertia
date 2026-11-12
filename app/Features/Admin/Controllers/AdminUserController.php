<?php

namespace App\Features\Admin\Controllers;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Services\PostExcelService;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUserController
{
    public function index(Request $request, AdminUserService $adminService): Response
    {
        $search = $request->query('search');

        return Inertia::render('Admin/UserList', [
            'users' => $adminService->getUsersList($search ?: null, 10),
            'filters' => [
                'search' => $search ?? '',
            ],
        ]);
    }

    public function posts(Request $request, User $user, AdminUserService $adminService): Response
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $category = $request->query('category');

        return Inertia::render('Admin/UserPostList', [
            'targetUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'posts' => $adminService->getUserPosts($user->id, $status ?: null, $search ?: null, 10, $category ?: null),
            'filters' => [
                'search' => $search ?? '',
                'status' => $status ?? '',
                'category' => $category ?? '',
            ],
            'categories' => PostCategory::options(),
        ]);
    }

    public function exportUserPosts(User $user, PostExcelService $excelService): StreamedResponse
    {
        $posts = $user->posts()->latest('id')->get();
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $user->name);

        return $excelService->exportToResponse($posts, "artikel-{$safeName}-".now()->format('Ymd-His').'.xlsx');
    }
}
