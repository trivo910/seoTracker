<?php

namespace App\Http\Controllers;

use App\Exports\RankingsExport;
use App\Jobs\RankCheckJob;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class RankingsController extends Controller
{
    /**
     * Manually trigger a rank check (admin/manager only).
     * In production the scheduler runs this automatically at midnight.
     */
    public function refresh(Request $request)
    {
        RankCheckJob::dispatch();

        return back()->with('success', 'Rank check job dispatched. Results will update in a few minutes.');
    }

    /**
     * Export rankings to Excel in the same format as your existing sheet.
     */
    public function export()
    {
        $filename = 'rankings-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new RankingsExport(), $filename);
    }
}
