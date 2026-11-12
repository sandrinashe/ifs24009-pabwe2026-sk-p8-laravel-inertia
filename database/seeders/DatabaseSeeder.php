<?php

namespace Database\Seeders;

use App\Features\Post\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('admin'),
                'role' => 'admin',
            ]
        );

        $member = User::firstOrCreate(
            ['email' => 'member@example.com'],
            [
                'name' => 'Member User',
                'username' => 'member',
                'password' => Hash::make('member'),
                'role' => 'member',
            ]
        );

        $posts = [
            [
                'title' => 'Mengenal Arsitektur Feature-First di Laravel 13',
                'slug' => 'mengenal-arsitektur-feature-first-di-laravel-13',
                'category' => 'Teknologi',
                'excerpt' => 'Mengapa mengelompokkan kode per fitur membuat proyek Laravel lebih mudah dirawat.',
                'content' => "## Apa itu Feature-First?\n\nAlih-alih memisahkan kode berdasarkan jenis (controller, model, service), kita mengelompokkannya **berdasarkan fitur**.\n\n- Lebih mudah menemukan kode terkait\n- Tim dapat bekerja paralel pada fitur berbeda\n- Dependensi antar-fitur lebih jelas",
                'is_published' => true,
                'published_at' => now()->subDays(2),
            ],
            [
                'title' => 'Tips Produktif Menulis Setiap Hari',
                'slug' => 'tips-produktif-menulis-setiap-hari',
                'category' => 'Gaya Hidup',
                'excerpt' => 'Kebiasaan kecil yang membantu menjaga konsistensi menulis.',
                'content' => "## Mulai dari yang kecil\n\nTargetkan **200 kata** per hari, bukan satu artikel sempurna.\n\n1. Tentukan jam tetap\n2. Matikan notifikasi\n3. Simpan ide di catatan",
                'is_published' => true,
                'published_at' => now()->subDay(),
            ],
            [
                'title' => 'Draf: Membangun Bisnis Konten dari Nol',
                'slug' => 'draf-membangun-bisnis-konten-dari-nol',
                'category' => 'Bisnis',
                'excerpt' => null,
                'content' => "## Catatan awal\n\nArtikel ini masih berupa draf.",
                'is_published' => false,
                'published_at' => null,
            ],
        ];

        foreach ($posts as $data) {
            Post::firstOrCreate(
                ['slug' => $data['slug']],
                $data + ['user_id' => $member->id]
            );
        }
    }
}
