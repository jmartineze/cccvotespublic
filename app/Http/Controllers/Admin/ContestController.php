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
        abort_if(auth()->user()->isSuperAdmin(), 403, 'Super-admins do not run contests.');

        return view('admin.contests.create');
    }

    public function store(StoreContestRequest $request): RedirectResponse
    {
        abort_if(auth()->user()->isSuperAdmin(), 403, 'Super-admins do not run contests.');

        $data = $request->safe()->except(['criteria', 'special_prizes']);
        $criteria = $request->validated('criteria');
        $specialPrizes = $request->validated('special_prizes') ?? [];

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('contests', 'public');
        }

        DB::transaction(function () use ($data, $criteria, $specialPrizes) {
            $contest = Contest::create($data);
            $this->syncCriteria($contest, $criteria);
            $this->syncSpecialPrizes($contest, $specialPrizes);
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

    /**
     * Id-aware upsert so existing prizes keep their votes when the contest is
     * edited mid-run; prizes dropped from the form are removed (cascade).
     */
    private function syncSpecialPrizes(Contest $contest, array $prizes): void
    {
        $keep = [];

        foreach (array_values($prizes) as $index => $prize) {
            $attrs = [
                'name' => $prize['name'],
                'description' => $prize['description'] ?? null,
                'sort_order' => $index,
            ];

            $existing = ! empty($prize['id'])
                ? $contest->specialPrizes()->whereKey($prize['id'])->first()
                : null;

            if ($existing) {
                $existing->update($attrs);
                $keep[] = $existing->id;
            } else {
                $keep[] = $contest->specialPrizes()->create($attrs)->id;
            }
        }

        $contest->specialPrizes()->whereKeyNot($keep)->delete();
    }

    public function edit(Contest $contest): View
    {
        return view('admin.contests.edit', compact('contest'));
    }

    public function update(UpdateContestRequest $request, Contest $contest): RedirectResponse
    {
        $criteria = $request->validated('criteria');
        $specialPrizes = $request->validated('special_prizes') ?? [];
        $data = $request->safe()->except(['criteria', 'special_prizes']);

        if ($request->hasFile('cover_image')) {
            if ($contest->cover_image) {
                Storage::disk('public')->delete($contest->cover_image);
            }
            $data['cover_image'] = $request->file('cover_image')->store('contests', 'public');
        }

        DB::transaction(function () use ($data, $criteria, $specialPrizes, $contest) {
            $contest->update($data);

            if ($criteria !== null && ! $contest->hasVotes()) {
                $contest->criteria()->delete();
                $this->syncCriteria($contest, $criteria);
            }

            $this->syncSpecialPrizes($contest, $specialPrizes);
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
