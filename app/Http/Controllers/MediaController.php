<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\SubmissionImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    /**
     * Stream a submission image/video only to viewers allowed to see its contest.
     * Files live on the "public" disk but are no longer linked directly, so the
     * tenant scope (and the draft rule for judges) is actually enforced.
     */
    public function submissionImage(Request $request, SubmissionImage $image): StreamedResponse|Response
    {
        $submission = $image->submission()->firstOrFail();

        // contest() carries the TenantScope global scope — a foreign tenant's
        // contest resolves to null here.
        $contest = $submission->contest()->first();
        abort_unless($contest, 404);

        abort_if($contest->status === 'draft' && ! $request->user()->actingAsAdmin(), 404);

        return $this->stream($image->image_path);
    }

    /**
     * Stream a contest cover image. Contest binding already applies the
     * TenantScope, so only the owning tenant (or a super-admin) reaches here.
     */
    public function contestCover(Contest $contest): StreamedResponse|Response
    {
        abort_if(blank($contest->cover_image), 404);

        return $this->stream($contest->cover_image);
    }

    private function stream(string $path): StreamedResponse|Response
    {
        $disk = Storage::disk('public');

        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
