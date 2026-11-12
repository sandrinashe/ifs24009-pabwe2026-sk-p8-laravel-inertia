<?php

namespace App\Features\Post\Enums;

enum PostCategory: string
{
    case Teknologi = 'Teknologi';
    case Pendidikan = 'Pendidikan';
    case GayaHidup = 'Gaya Hidup';
    case Bisnis = 'Bisnis';
    case Opini = 'Opini';
    case Umum = 'Umum';

    /**
     * Human readable description of the category.
     */
    public function label(): string
    {
        return match ($this) {
            self::Teknologi => 'Teknologi & Koding',
            self::Pendidikan => 'Pendidikan & Tutorial',
            self::GayaHidup => 'Gaya Hidup & Produktivitas',
            self::Bisnis => 'Bisnis & Karir',
            self::Opini => 'Opini & Pemikiran',
            self::Umum => 'Umum',
        };
    }

    /**
     * Tailwind classes used for the category badge.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Teknologi => 'bg-blue-100 text-blue-800',
            self::Pendidikan => 'bg-emerald-100 text-emerald-800',
            self::GayaHidup => 'bg-pink-100 text-pink-800',
            self::Bisnis => 'bg-amber-100 text-amber-800',
            self::Opini => 'bg-purple-100 text-purple-800',
            self::Umum => 'bg-slate-100 text-slate-700',
        };
    }

    /**
     * All category values as a plain list of strings.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Options formatted for the frontend.
     *
     * @return list<array{value: string, label: string, badgeClass: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'badgeClass' => $case->badgeClass(),
        ], self::cases());
    }
}
