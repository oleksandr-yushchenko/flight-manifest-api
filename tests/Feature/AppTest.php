<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppTest extends TestCase
{
    /**
     * A basic test.
     */
    public function test_health_endpoint_returns_successful_response(): void
    {
        $response = $this->getJson('/api/health');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
            ]);
    }

    public function test_swagger_ui_route_returns_successful_response(): void
    {
        $response = $this->get('/swagger');

        $response
            ->assertOk()
            ->assertSee('SwaggerUIBundle', escape: false)
            ->assertSee('/openapi.yaml', escape: false);
    }

    public function test_openapi_spec_route_returns_successful_response(): void
    {
        $response = $this->get('/openapi.yaml');

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/yaml; charset=UTF-8')
            ->assertSee('openapi: 3.0.3', escape: false)
            ->assertSee('/api/flights', escape: false);
    }
}
