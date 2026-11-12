<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    public function test_handle_inertia_requests_share_data(): void
    {
        $middleware = new HandleInertiaRequests();
        $request = Request::create('/dashboard/posts', 'GET');

        // Without user
        $data = $middleware->share($request);
        $this->assertNull($data['auth']['user']);

        // With user
        $user = User::factory()->make([
            'id' => 99,
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'role' => 'member',
            'profile_photo_path' => 'photos/test.png',
        ]);
        $request->setUserResolver(fn () => $user);

        $session = app('session.store');
        $session->put('success', 'Berhasil!');
        $session->put('error', 'Gagal!');
        $session->put('aiGeneratedItems', [['title' => 'AI']]);
        $request->setLaravelSession($session);

        $shared = $middleware->share($request);
        $this->assertEquals(99, $shared['auth']['user']['id']);
        $this->assertEquals('Test User', $shared['auth']['user']['name']);
        $this->assertEquals('Berhasil!', $shared['flash']['success']());
        $this->assertEquals('Gagal!', $shared['flash']['error']());
        $this->assertCount(1, $shared['flash']['aiGeneratedItems']());

        // Versi aset tidak boleh bergantung pada hasil `npm run build`:
        // sediakan manifest sementara bila belum ada, lalu bersihkan kembali.
        $manifest = public_path('build/manifest.json');
        $createdManifest = false;
        $createdDir = false;

        if (!file_exists($manifest)) {
            if (!is_dir(dirname($manifest))) {
                mkdir(dirname($manifest), 0777, true);
                $createdDir = true;
            }
            file_put_contents($manifest, '{}');
            $createdManifest = true;
        }

        try {
            $this->assertNotNull($middleware->version($request));
        } finally {
            if ($createdManifest) {
                @unlink($manifest);
            }
            if ($createdDir) {
                @rmdir(dirname($manifest));
            }
        }
    }
}
