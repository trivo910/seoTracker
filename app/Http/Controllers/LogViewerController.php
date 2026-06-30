<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogViewerController extends Controller
{
    /** Max entries rendered per request, newest first. */
    private const MAX_ENTRIES = 500;

    public function index(Request $request)
    {
        $logPath = storage_path('logs');

        $dates = collect(glob($logPath . '/jobs-*.log'))
            ->map(fn($path) => basename($path))
            ->filter(fn($name) => preg_match('/^jobs-(\d{4}-\d{2}-\d{2})\.log$/', $name))
            ->map(fn($name) => preg_replace('/^jobs-(.+)\.log$/', '$1', $name))
            ->sortDesc()
            ->values();

        $selectedDate = $request->get('date', $dates->first());

        // Strict whitelist — never build a path from unvalidated input
        if (! $selectedDate || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = $dates->first();
        }

        $entries = collect();
        $file    = $selectedDate ? $logPath . "/jobs-{$selectedDate}.log" : null;

        if ($file && is_file($file) && dirname($file) === $logPath) {
            $entries = $this->parseLog(file_get_contents($file));
        }

        $q = trim((string) $request->get('q', ''));
        if ($q !== '') {
            $entries = $entries->filter(fn($e) =>
                str_contains(strtolower($e['message']), strtolower($q)) ||
                str_contains(strtolower($e['context_raw']), strtolower($q))
            );
        }

        // Counted on the search-filtered set, before the level filter narrows
        // it further — otherwise every non-selected level pill would show 0.
        $counts = $entries->countBy('level');

        $level = $request->get('level');
        if ($level) {
            $entries = $entries->where('level', strtoupper($level));
        }

        $entries = $entries->reverse()->take(self::MAX_ENTRIES)->values();

        return view('logs.index', compact('entries', 'dates', 'selectedDate', 'level', 'q', 'counts'));
    }

    /**
     * Parse Monolog single-line entries:
     * [2026-06-29 13:11:36] local.INFO: RankCheckJob started {"provider":"Serper.dev",...}
     */
    private function parseLog(string $contents)
    {
        preg_match_all(
            '/^\[(?<date>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (?<channel>\w+)\.(?<level>\w+): (?<message>.*?)(?: (?<context>\{.*\}))?\s*$/m',
            $contents,
            $matches,
            PREG_SET_ORDER
        );

        return collect($matches)->map(function ($m) {
            $context = [];
            if (! empty($m['context'])) {
                $decoded = json_decode($m['context'], true);
                if (is_array($decoded)) {
                    $context = $decoded;
                }
            }

            return [
                'date'        => $m['date'],
                'level'       => $m['level'],
                'message'     => $m['message'],
                'context'     => $context,
                'context_raw' => $m['context'] ?? '',
            ];
        });
    }
}
