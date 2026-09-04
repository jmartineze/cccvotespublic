<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    protected function makeTenantAdmin(): User
    {
        return User::factory()->create(['role' => 'tenant_admin']);
    }

    protected function makeJudge(User $tenant, ?string $username = null): User
    {
        return User::factory()->create([
            'role' => 'judge',
            'owner_id' => $tenant->id,
            'email' => null,
            'username' => $username ?? 'j_'.Str::lower(Str::random(8)),
        ]);
    }

    /** A judge who is co-admin of the given tenant. */
    protected function makeCoAdmin(User $tenant, ?string $username = null): User
    {
        $user = $this->makeJudge($tenant, $username);
        $user->memberships()->where('tenant_id', $tenant->id)->update(['role' => 'co_admin']);

        return $user->fresh();
    }
}
