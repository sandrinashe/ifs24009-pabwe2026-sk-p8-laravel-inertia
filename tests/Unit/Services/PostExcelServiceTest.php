<?php

namespace Tests\Unit\Services;

use App\Features\Post\Models\Post;
use App\Features\Post\Repositories\PostRepository;
use App\Features\Post\Services\PostExcelService;
use App\Features\Post\Services\PostService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class PostExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PostExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PostExcelService(new PostService(new PostRepository()));
    }

    public function test_build_spreadsheet_without_author(): void
    {
        $posts = Post::factory()->count(2)->create(['excerpt' => null]);
        Post::factory()->published()->create();

        $sheet = $this->service->buildSpreadsheet(Post::with('user')->get())->getActiveSheet();

        $this->assertSame('Judul', $sheet->getCell('B1')->getValue());
        $this->assertSame('-', $sheet->getCell('D2')->getValue());
        $this->assertSame('', (string) $sheet->getCell('I1')->getValue());
        $this->assertContains($sheet->getCell('F2')->getValue(), ['Draft', 'Published']);
        $this->assertCount(2, $posts);
    }

    public function test_build_spreadsheet_with_author_and_missing_user(): void
    {
        $user = User::factory()->create(['name' => 'Penulis Satu']);
        Post::factory()->published()->create(['user_id' => $user->id]);

        $sheet = $this->service->buildSpreadsheet(Post::with('user')->get(), 'Semua', true)->getActiveSheet();
        $this->assertSame('Penulis', $sheet->getCell('I1')->getValue());
        $this->assertSame('Penulis Satu', $sheet->getCell('I2')->getValue());

        $orphan = Post::factory()->make(['published_at' => null, 'created_at' => null]);
        $orphan->setRelation('user', null);
        $sheet = $this->service->buildSpreadsheet(new \Illuminate\Database\Eloquent\Collection([$orphan]), 'Kosong', true)->getActiveSheet();
        $this->assertSame('-', $sheet->getCell('I2')->getValue());
        $this->assertSame('-', $sheet->getCell('G2')->getValue());
        $this->assertSame('-', $sheet->getCell('H2')->getValue());
    }

    public function test_export_to_response_streams_xlsx(): void
    {
        Post::factory()->count(2)->create();

        $response = $this->service->exportToResponse(Post::with('user')->get(), 'artikel.xlsx', true);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('PK', $content); // file xlsx adalah zip
    }

    public function test_import_creates_posts_and_skips_invalid_rows(): void
    {
        $user = User::factory()->create();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        foreach (PostExcelService::HEADERS as $i => $header) {
            $sheet->setCellValue(chr(65 + $i).'1', $header);
        }
        $sheet->fromArray([1, 'Artikel Satu', 'Teknologi', '-', 'Isi satu', 'Published'], null, 'A2');
        $sheet->fromArray([2, 'Artikel Dua', 'Kategori Palsu', 'Ringkasan dua', 'Isi dua', 'Draft'], null, 'A3');
        $sheet->fromArray([3, '', 'Umum', '', 'Tanpa judul', 'Draft'], null, 'A4');
        $sheet->fromArray([4, 'Tanpa isi', 'Umum', '', '', 'Draft'], null, 'A5');

        $path = tempnam(sys_get_temp_dir(), 'post').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $result = $this->service->import($path, $user->id);
        @unlink($path);

        $this->assertSame(['imported' => 2, 'skipped' => 2], $result);

        $first = Post::where('title', 'Artikel Satu')->first();
        $this->assertTrue($first->is_published);
        $this->assertNull($first->excerpt);
        $this->assertSame('Teknologi', $first->category);

        $second = Post::where('title', 'Artikel Dua')->first();
        $this->assertFalse($second->is_published);
        $this->assertSame('Umum', $second->category);
        $this->assertSame('Ringkasan dua', $second->excerpt);
    }
}
