<?php

namespace Tests\Unit\Enums;

use App\Features\Post\Enums\PostCategory;
use PHPUnit\Framework\TestCase;

class PostCategoryTest extends TestCase
{
    public function test_has_the_six_categories_from_the_spec(): void
    {
        $this->assertSame(
            ['Teknologi', 'Pendidikan', 'Gaya Hidup', 'Bisnis', 'Opini', 'Umum'],
            PostCategory::values()
        );
    }

    public function test_labels_and_badge_classes(): void
    {
        $expectedLabels = [
            'Teknologi' => 'Teknologi & Koding',
            'Pendidikan' => 'Pendidikan & Tutorial',
            'Gaya Hidup' => 'Gaya Hidup & Produktivitas',
            'Bisnis' => 'Bisnis & Karir',
            'Opini' => 'Opini & Pemikiran',
            'Umum' => 'Umum',
        ];

        foreach (PostCategory::cases() as $case) {
            $this->assertSame($expectedLabels[$case->value], $case->label());
            $this->assertStringStartsWith('bg-', $case->badgeClass());
        }
    }

    public function test_options_for_frontend(): void
    {
        $options = PostCategory::options();

        $this->assertCount(6, $options);
        $this->assertSame(['value', 'label', 'badgeClass'], array_keys($options[0]));
        $this->assertSame('Gaya Hidup', $options[2]['value']);
    }

    public function test_try_from_unknown_value_is_null(): void
    {
        $this->assertNull(PostCategory::tryFrom('Olahraga'));
        $this->assertSame(PostCategory::GayaHidup, PostCategory::from('Gaya Hidup'));
    }
}
