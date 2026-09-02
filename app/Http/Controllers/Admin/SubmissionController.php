<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSubmissionRequest;
use App\Http\Requests\UpdateSubmissionRequest;
use App\Models\Contest;
use App\Models\Submission;
use App\Models\SubmissionImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $contests = Contest::all();
        // Contest carries the TenantScope global scope, so whereHas('contest')
        // limits submissions to the current tenant's contests (super_admin sees all).
        $query = Submission::whereHas('contest')->with(['contest', 'images'])->latest();

        if ($request->filled('contest_id')) {
            $query->where('contest_id', $request->contest_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('character_name', 'like', "%{$search}%")
                    ->orWhere('discord_user', 'like', "%{$search}%");
            });
        }

        $submissions = $query->paginate(20)->withQueryString();

        return view('admin.submissions.index', compact('submissions', 'contests'));
    }

    public function create(): View
    {
        $contests = Contest::orderBy('name')->get();

        return view('admin.submissions.create', compact('contests'));
    }

    public function store(StoreSubmissionRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->safe()->except('images');
            $data['backstory'] = mb_substr($data['backstory'], 0, 1000);
            $submission = Submission::create($data);

            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('submissions', 'public');
                SubmissionImage::create([
                    'submission_id' => $submission->id,
                    'image_path' => $path,
                    'sort_order' => $index,
                ]);
            }
        });

        return redirect()->route('admin.submissions.index')
            ->with('success', 'Submission created successfully.');
    }

    public function edit(Submission $submission): View
    {
        $this->authorizeTenant($submission);

        $contests = Contest::orderBy('name')->get();
        $submission->load('images');

        return view('admin.submissions.edit', compact('submission', 'contests'));
    }

    public function update(UpdateSubmissionRequest $request, Submission $submission): RedirectResponse
    {
        $this->authorizeTenant($submission);

        DB::transaction(function () use ($request, $submission) {
            $data = $request->safe()->except('images');
            $data['backstory'] = mb_substr($data['backstory'], 0, 1000);
            $submission->update($data);

            // Delete images marked for removal
            $deleteIds = $request->input('delete_images', []);
            if ($deleteIds) {
                $toDelete = SubmissionImage::whereIn('id', $deleteIds)
                    ->where('submission_id', $submission->id)
                    ->get();
                foreach ($toDelete as $image) {
                    Storage::disk('public')->delete($image->image_path);
                    $image->delete();
                }
            }

            // Add new images
            if ($request->hasFile('images')) {
                $currentCount = $submission->images()->count();
                $remaining = 12 - $currentCount;
                $nextOrder = $submission->images()->max('sort_order') + 1;

                foreach (array_slice($request->file('images'), 0, $remaining) as $index => $file) {
                    $path = $file->store('submissions', 'public');
                    SubmissionImage::create([
                        'submission_id' => $submission->id,
                        'image_path' => $path,
                        'sort_order' => $nextOrder + $index,
                    ]);
                }
            }
        });

        return redirect()->route('admin.submissions.edit', $submission)
            ->with('success', 'Submission updated successfully.');
    }

    public function destroy(Submission $submission): RedirectResponse
    {
        $this->authorizeTenant($submission);

        foreach ($submission->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $submission->delete();

        return redirect()->route('admin.submissions.index')
            ->with('success', 'Submission deleted.');
    }

    /**
     * Ensure the submission belongs to a contest visible to the current tenant.
     * Contest carries the TenantScope, so a hidden contest means another tenant owns it.
     */
    private function authorizeTenant(Submission $submission): void
    {
        abort_unless($submission->contest()->exists(), 404);
    }
}
