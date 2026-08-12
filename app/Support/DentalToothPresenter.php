<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\DentalChartEntry;

final class DentalToothPresenter
{
    private const PERMANENT_NAMES = [
        1 => 'ثنایای مرکزی',
        2 => 'ثنایای جانبی',
        3 => 'نیش',
        4 => 'آسیای کوچک اول',
        5 => 'آسیای کوچک دوم',
        6 => 'آسیای بزرگ اول',
        7 => 'آسیای بزرگ دوم',
        8 => 'آسیای بزرگ سوم',
    ];

    private const PRIMARY_NAMES = [
        1 => 'ثنایای مرکزی شیری',
        2 => 'ثنایای جانبی شیری',
        3 => 'نیش شیری',
        4 => 'آسیای شیری اول',
        5 => 'آسیای شیری دوم',
    ];

    private const SURFACE_LABELS = [
        'all' => 'کل دندان',
        'O' => 'سطح جونده',
        'M' => 'مزیال',
        'D' => 'دیستال',
        'B' => 'گونه‌ای',
        'L' => 'زبانی',
        'I' => 'برشی',
    ];

    private const FDI_QUADRANTS = [
        1 => ['arch' => 'فک بالا', 'side' => 'سمت راست بیمار'],
        2 => ['arch' => 'فک بالا', 'side' => 'سمت چپ بیمار'],
        3 => ['arch' => 'فک پایین', 'side' => 'سمت چپ بیمار'],
        4 => ['arch' => 'فک پایین', 'side' => 'سمت راست بیمار'],
        5 => ['arch' => 'فک بالا', 'side' => 'سمت راست بیمار'],
        6 => ['arch' => 'فک بالا', 'side' => 'سمت چپ بیمار'],
        7 => ['arch' => 'فک پایین', 'side' => 'سمت چپ بیمار'],
        8 => ['arch' => 'فک پایین', 'side' => 'سمت راست بیمار'],
    ];

    public static function present(string $toothCode): array
    {
        $normalized = trim($toothCode);
        $quadrant = (int) ($normalized[0] ?? 0);
        $position = (int) ($normalized[1] ?? 0);
        $isPrimary = in_array($quadrant, [5, 6, 7, 8], true);
        $quadrantData = self::FDI_QUADRANTS[$quadrant] ?? ['arch' => 'فک', 'side' => 'نامشخص'];
        $name = ($isPrimary ? self::PRIMARY_NAMES : self::PERMANENT_NAMES)[$position] ?? 'دندان';
        $arch = $quadrantData['arch'];
        $side = $quadrantData['side'];

        return [
            'code' => $normalized,
            'fdi' => 'FDI '.$normalized,
            'name' => $name,
            'short_name' => $name.'، '.$arch,
            'display_name' => $name.'، '.$arch.'، '.$side,
            'arch' => $arch,
            'side' => $side,
            'quadrant' => $quadrant,
            'position' => $position,
            'is_primary' => $isPrimary,
            'type' => $isPrimary ? 'شیری' : 'دائمی',
            'quadrant_label' => $arch.' · '.$side,
            'surface_labels' => self::SURFACE_LABELS,
            'placement' => self::placement($quadrant, $position, $isPrimary),
        ];
    }

    /** @return array{x: float, y: float, rotation: float, family: string} */
    private static function placement(int $quadrant, int $position, bool $isPrimary): array
    {
        $isUpper = in_array($quadrant, [1, 2, 5, 6], true);
        $isRightHalf = in_array($quadrant, [1, 4, 5, 8], true);
        $maxPosition = $isPrimary ? 5 : 8;
        $index = $isRightHalf ? $maxPosition - $position : $maxPosition + $position - 1;
        $centerIndex = ($maxPosition * 2 - 1) / 2;
        $distanceFromCenter = abs($index - $centerIndex);
        $xStart = $isPrimary ? 27.5 : 12.5;
        $xStep = $isPrimary ? 5.625 : 5.35;
        $x = $xStart + ($index * $xStep);
        $y = $isUpper
            ? ($isPrimary ? 36 : 28) + ($distanceFromCenter * ($isPrimary ? 1.3 : 1.55))
            : ($isPrimary ? 64 : 72) - ($distanceFromCenter * ($isPrimary ? 1.3 : 1.55));
        $family = match (true) {
            $position <= 2 => 'incisor',
            $position === 3 => 'canine',
            $isPrimary && $position >= 4 => 'molar',
            $position <= 5 => 'premolar',
            default => 'molar',
        };

        return [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'rotation' => round(($index - $centerIndex) * ($isUpper ? -3.2 : 3.2), 2),
            'family' => $family,
        ];
    }

    public static function all(): array
    {
        return array_map(static fn (string $code): array => self::present($code), DentalChartEntry::allToothCodes());
    }

    public static function surfaceLabel(string $surface): string
    {
        return self::SURFACE_LABELS[$surface] ?? $surface;
    }
}
