<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    /**
     * A basic test example.
     *
     * Tests that the application returns a successful response for the home page.
     */
    public function test_the_application_returns_a_successful_response(): void
    {

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
