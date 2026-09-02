<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Active contests are always shown in full (there are only ever a few).
        $activeContests = Contest::withCount('submissions')
            ->where('status', 'active')
            ->latest()
            ->get();

        // Everything else (draft/closed) is paginated.
        $contests = Contest::withCount('submissions')
            ->where('status', '!=', 'active')
            ->orderByRaw("CASE status WHEN 'draft' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(15);

        return view('dashboard', compact('activeContests', 'contests'));
    }
}
