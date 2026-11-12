<?php

namespace App\Features\Post\Services;

use App\Features\Post\Enums\PostCategory;
use App\Features\Post\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostExcelService
{
    /**
     * Export / import column layout (A..H).
     *
     * @var list<string>
     */
    public const HEADERS = ['No', 'Judul', 'Kategori', 'Ringkasan', 'Konten', 'Status', 'Dipublikasikan', 'Dibuat'];

    public function __construct(
        protected PostService $postService
    ) {}

    /**
     * Build a Spreadsheet containing post data. When $withAuthor is true an extra "Penulis" column is appended.
     *
     * @param Collection<int, Post> $posts
     */
    public function buildSpreadsheet(Collection $posts, string $sheetTitle = 'Artikel', bool $withAuthor = false): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        $headers = self::HEADERS;
        if ($withAuthor) {
            $headers[] = 'Penulis';
        }

        $lastColumn = chr(ord('A') + count($headers) - 1);

        foreach ($headers as $index => $header) {
            $sheet->setCellValue(chr(ord('A') + $index).'1', $header);
        }

        $headerRange = "A1:{$lastColumn}1";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('4F46E5');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 2;
        foreach ($posts as $index => $post) {
            $sheet->setCellValue("A{$row}", $index + 1);
            $sheet->setCellValue("B{$row}", $post->title);
            $sheet->setCellValue("C{$row}", $post->category);
            $sheet->setCellValue("D{$row}", $post->excerpt ?? '-');
            $sheet->setCellValue("E{$row}", $post->content);
            $sheet->setCellValue("F{$row}", $post->is_published ? 'Published' : 'Draft');
            $sheet->setCellValue("G{$row}", $post->published_at ? $post->published_at->format('Y-m-d H:i') : '-');
            $sheet->setCellValue("H{$row}", $post->created_at ? $post->created_at->format('Y-m-d H:i') : '-');

            if ($withAuthor) {
                $sheet->setCellValue("I{$row}", $post->user?->name ?? '-');
            }

            $row++;
        }

        $lastRow = max(2, $row - 1);
        $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach (range('A', $lastColumn) as $column) {
            // Content may be very long, so cap it instead of auto-sizing.
            if ($column === 'E') {
                $sheet->getColumnDimension($column)->setWidth(60);
                continue;
            }
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Export posts as a downloadable streamed response.
     *
     * @param Collection<int, Post> $posts
     */
    public function exportToResponse(Collection $posts, string $filename = 'artikel.xlsx', bool $withAuthor = false): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($posts, 'Artikel', $withAuthor);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import posts from a spreadsheet. Each imported post is created as a draft unless the Status column says "published".
     *
     * @return array{imported: int, skipped: int}
     */
    public function import(string $filePath, int $userId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $imported = 0;
        $skipped = 0;
        $isHeader = true;

        foreach ($rows as $row) {
            if ($isHeader) {
                $isHeader = false;
                continue;
            }

            $title = trim((string) ($row['B'] ?? ''));
            $content = trim((string) ($row['E'] ?? ''));

            if ($title === '' || $content === '') {
                $skipped++;
                continue;
            }

            $category = trim((string) ($row['C'] ?? ''));
            $excerpt = trim((string) ($row['D'] ?? ''));
            $status = strtolower(trim((string) ($row['F'] ?? '')));

            $this->postService->createPost($userId, [
                'title' => mb_substr($title, 0, 255),
                'category' => PostCategory::tryFrom($category)?->value ?? PostCategory::Umum->value,
                'excerpt' => ($excerpt === '' || $excerpt === '-') ? null : $excerpt,
                'content' => $content,
                'is_published' => $status === 'published',
            ]);

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }
}
