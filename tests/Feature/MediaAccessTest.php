<?php

namespace Tests\Feature;

use App\Models\Contest;
use App\Models\Submission;
use App\Models\SubmissionImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function contestWithImage(string $status = 'active', ?User $tenant = null): array
    {
        $tenant ??= User::factory()->create(['role' => 'tenant_admin']);
        $contest = Contest::create([
            'owner_id' => $tenant->id,
            'name' => 'C',
            'status' => $status,
            'contest_type' => 'image',
        ]);
        $submission = Submission::create([
            'contest_id' => $contest->id,
            'character_name' => 'A',
            'discord_user' => 'a#1',
            'gender' => 'Female',
            'country' => 'JP',
            'style' => 'Anime',
            'backstory' => 'b',
        ]);
        $path = 'submissions/'.uniqid().'.jpg';
        Storage::disk('public')->put($path, 'fake-jpeg-bytes');
        $image = SubmissionImage::create([
            'submission_id' => $submission->id,
            'image_path' => $path,
            'sort_order' => 0,
        ]);

        return [$tenant, $contest, $submission, $image];
    }

    public function test_guest_cannot_fetch_a_submission_image(): void
    {
        [, , , $image] = $this->contestWithImage();

        $this->get("/media/submission-image/{$image->id}")->assertRedirect(route('login'));
    }

    public function test_judge_of_the_owning_tenant_can_fetch_the_image(): void
    {
        [$tenant, , , $image] = $this->contestWithImage();
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenant->id, 'email' => null, 'username' => 'j']);

        $this->actingAs($judge)->get("/media/submission-image/{$image->id}")->assertOk();
    }

    public function test_judge_from_another_tenant_gets_404(): void
    {
        [, , , $image] = $this->contestWithImage();
        $otherTenant = User::factory()->create(['role' => 'tenant_admin']);
        $outsider = User::factory()->create(['role' => 'judge', 'owner_id' => $otherTenant->id, 'email' => null, 'username' => 'o']);

        $this->actingAs($outsider)->get("/media/submission-image/{$image->id}")->assertNotFound();
    }

    public function test_draft_contest_image_is_hidden_from_judges_but_visible_to_admin(): void
    {
        [$tenant, , , $image] = $this->contestWithImage('draft');
        $judge = User::factory()->create(['role' => 'judge', 'owner_id' => $tenant->id, 'email' => null, 'username' => 'j']);

        $this->actingAs($judge)->get("/media/submission-image/{$image->id}")->assertNotFound();
        $this->actingAs($tenant)->get("/media/submission-image/{$image->id}")->assertOk();
    }

    public function test_image_url_accessor_points_at_the_gated_route(): void
    {
        [, , , $image] = $this->contestWithImage();

        $this->assertSame(route('media.submission-image', $image), $image->url);
        $this->assertStringNotContainsString('/storage/', $image->url);
    }
}
