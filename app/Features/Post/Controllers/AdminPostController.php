<?php

namespace App\Features\Post\Controllers;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminPostController
{
    /**
     * Read-only monitoring of every post in the system.
     */
    public function index(Request $request, PostService $service): Response
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $category = $request->query('category');

        return Inertia::render('Admin/PostList', [
            'posts' => $service->getAllPosts($status ?: null, $search ?: null, 10, $category ?: null),
            'filters' => [
                'search' => $search ?? '',
                'status' => $status ?? '',
                'category' => $category ?? '',
            ],
            'categories' => PostCategory::options(),
        ]);
    }

    /**
     * Export all posts in the system to Excel.
     */
    public function export(PostService $service, PostExcelService $excelService): StreamedResponse
    {
        return $excelService->exportToResponse($service->getEveryPost(),'semua-artikel-'.now()->format('Ymd-His').'.xlsx', true);
    }
}
