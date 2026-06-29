<?php

namespace App\Exports;

use App\Models\Keyword;
use App\Models\KeywordRanking;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RankingsExport implements FromArray, WithStyles, WithColumnWidths, WithTitle
{
    private int $days;
    private array $dates;

    public function __construct(int $days = 30)
    {
        $this->days  = $days;
        // Build array of the last N date strings (oldest first)
        $this->dates = collect(range($days - 1, 0))
            ->map(fn($d) => Carbon::today()->subDays($d)->toDateString())
            ->values()
            ->toArray();
    }

    public function array(): array
    {
        $keywords = Keyword::with([
            'rankings' => fn($q) => $q
                ->where('checked_date', '>=', Carbon::today()->subDays($this->days))
                ->orderBy('checked_date'),
            'website',
        ])
        ->where('is_active', true)
        ->orderBy('id')
        ->get();

        // ── Build rank map per keyword ──────────────────────────
        $rows = [];

        // Row 1: empty merge row (matches your sheet style)
        $rows[] = [];

        // Row 2: source labels (Google, Semrush, Google)
        $dateLabels = array_map(
            fn($d) => Carbon::parse($d)->format('d-m-y'),
            $this->dates
        );
        $rows[] = array_merge(
            ['', '', '', '', 'Google', 'Semrush', 'Google', '', '', ''],
            $dateLabels
        );

        // Row 3: main headers
        $headers = [
            'Sr. No.', 'Keyword', 'Currency',
            'Avg. monthly searches', 'Volume', 'Competition',
            'Intent', 'KD', 'URL',
        ];
        foreach ($this->dates as $date) {
            $headers[] = 'Rank ' . Carbon::parse($date)->format('d-m-y');
        }
        $rows[] = $headers;

        // ── Data rows ──────────────────────────────────────────
        foreach ($keywords as $i => $kw) {
            $rankMap = $kw->rankings
                ->keyBy(fn($r) => $r->checked_date->format('Y-m-d'))
                ->map(fn($r) => $r->rank_position)
                ->toArray();

            $row = [
                $i + 1,
                $kw->keyword,
                $kw->currency ?? 'INR',
                $kw->monthly_searches,
                $kw->semrush_volume,
                $kw->competition,
                $kw->intent,
                $kw->kd,
                $kw->target_url,
            ];

            foreach ($this->dates as $date) {
                $row[] = $rankMap[$date] ?? null;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(9 + count($this->dates));
        $headerRow  = 3;
        $lastRow    = $sheet->getHighestRow();

        // ── Header row styling ──────────────────────────────────
        $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF15803D']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFBBBBBB']]],
        ]);

        // ── Source row styling (row 2) ──────────────────────────
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font' => ['bold' => true, 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFF0FDF4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Alternate data row colors ───────────────────────────
        for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
            $color = ($row % 2 === 0) ? 'FFF9FAF8' : 'FFFFFFFF';
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $color]],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFEEEEEE']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // ── Freeze header rows ──────────────────────────────────
        $sheet->freezePane("A" . ($headerRow + 1));

        // ── Auto-filter on header row ───────────────────────────
        $sheet->setAutoFilter("A{$headerRow}:{$lastCol}{$headerRow}");

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,   // Sr. No.
            'B' => 35,  // Keyword
            'C' => 8,   // Currency
            'D' => 18,  // Avg monthly searches
            'E' => 12,  // Volume
            'F' => 14,  // Competition
            'G' => 10,  // Intent
            'H' => 8,   // KD
            'I' => 40,  // URL
        ];
    }

    public function title(): string
    {
        return 'Rankings ' . now()->format('Y-m-d');
    }
}
