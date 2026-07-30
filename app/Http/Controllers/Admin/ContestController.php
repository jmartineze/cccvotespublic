<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContestRequest;
use App\Http\Requests\UpdateContestRequest;
use App\Models\Contest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ContestController extends Controller
{
    public function index(): View
    {
        $contests = Contest::withCount('submissions')->latest()->get();

        return view('admin.contests.index', compact('contests'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->isTenantAdmin(), 403, 'Only tenant admins create contests.');

        return view('admin.contests.create');
    }

    public function store(StoreContestRequest $request): RedirectResponse
    {
        abort_unless(auth()->user()->isTenantAdmin(), 403, 'Only tenant admins create contests.');

        $data = $request->safe()->except('criteria');
        $criteria = $request->validated('criteria');

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('contests', 'public');
        }

        DB::transaction(function () use ($data, $criteria) {
            $contest = Contest::create($data);
            $this->syncCriteria($contest, $criteria);
        });

        return redirect()->route('admin.contests.index')
            ->with('success', 'Contest created successfully.');
    }

    private function syncCriteria(Contest $contest, array $criteria): void
    {
        foreach ($criteria as $index => $criterion) {
            $contest->criteria()->create([
                'name' => $criterion['name'],
                'description' => $criterion['description'] ?? null,
                'max_score' => $criterion['max_score'],
                'sort_order' => $index,
                'tiebreak_order' => $criterion['tiebreak_order'] ?? null,
            ]);
        }
    }

    public function edit(Contest $contest): View
    {
        return view('admin.contests.edit', compact('contest'));
    }

    public function update(UpdateContestRequest $request, Contest $contest): RedirectResponse
    {
        $criteria = $request->validated('criteria');
        $data = $request->safe()->except('criteria');

        if ($request->hasFile('cover_image')) {
            if ($contest->cover_image) {
                Storage::disk('public')->delete($contest->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('contests', 'public');
        }

        DB::transaction(function () use ($data, $criteria, $contest) {
            $contest->update($data);

            if ($criteria !== null && ! $contest->hasVotes()) {
                $contest->criteria()->delete();
                $this->syncCriteria($contest, $criteria);
            }
        });

        return redirect()->route('admin.contests.index')
            ->with('success', 'Contest updated successfully.');
    }

    public function destroy(Contest $contest): RedirectResponse
    {
        if ($contest->cover_image) {
            Storage::disk('public')->delete($contest->cover_image);
        }

        $contest->delete();

        return redirect()->route('admin.contests.index')
            ->with('success', 'Contest deleted.');
    }
}
