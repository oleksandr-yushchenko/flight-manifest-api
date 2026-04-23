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

    public function test_mock_airline_status_route_returns_next_valid_status_payload(): void
    {
        $response = $this->getJson('/mock-airline/flights/PS321/status?current_status=scheduled&status=boarding&gate=A12');

        $response
            ->assertOk()
            ->assertJsonPath('flight_number', 'PS321')
            ->assertJsonPath('current_status', 'scheduled')
            ->assertJsonPath('status', 'boarding')
            ->assertJsonPath('gate', 'A12')
            ->assertJsonMissingPath('departure_at')
            ->assertJsonPath('departed_at', null);
    }

    public function test_mock_airline_status_route_returns_departed_at_for_departed_status(): void
    {
        $response = $this->getJson('/mock-airline/flights/PS321/status?current_status=boarding&status=departed');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'departed')
            ->assertJsonPath('gate', null)
            ->assertJsonMissingPath('departure_at');

        $this->assertNotNull($response->json('departed_at'));
    }

    public function test_mock_airline_status_route_does_not_change_departed_or_cancelled_flights(): void
    {
        $departedResponse = $this->getJson('/mock-airline/flights/PS321/status?current_status=departed&status=boarding');
        $cancelledResponse = $this->getJson('/mock-airline/flights/PS321/status?current_status=cancelled&status=boarding');

        $departedResponse
            ->assertOk()
            ->assertJsonPath('current_status', 'departed')
            ->assertJsonPath('status', 'departed')
            ->assertJsonPath('gate', null);

        $cancelledResponse
            ->assertOk()
            ->assertJsonPath('current_status', 'cancelled')
            ->assertJsonPath('status', 'cancelled')
            ->assertJsonPath('gate', null)
            ->assertJsonPath('departed_at', null);
    }
}
