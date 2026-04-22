<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppTest extends TestCase
{
    /**
     * A basic test.
     */
    public function test_application_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
