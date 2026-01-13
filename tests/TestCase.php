<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Act as an authenticated user for testing protected endpoints.
     */
    protected function actingAsUser(?User $user = null): static
    {
        $user = $user ?? User::factory()->create();
        $this->actingAs($user, 'sanctum');

        return $this;
    }
}
