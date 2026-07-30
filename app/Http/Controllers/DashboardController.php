<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $contests = Contest::withCount('submissions')
            ->orderByRaw("FIELD(status, 'active', 'draft', 'closed')")
            ->latest()
            ->get();

        return view('dashboard', compact('contests'));
    }
}
