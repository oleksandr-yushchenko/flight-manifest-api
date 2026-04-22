<?php

namespace Tests\Feature;

use App\Models\Passenger;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PassengersApiTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function validPayload(): array
    {
        return [
            'first_name' => 'Iryna',
            'last_name' => 'Melnyk',
            'email' => 'iryna.melnyk@example.test',
            'birthday' => '1995-08-14',
            'document_number' => 'AB123456',
        ];
    }

    public function test_create_passenger(): void
    {
        $response = $this->postJson('/api/passengers', $this->validPayload());

        $response
            ->assertCreated()
            ->assertJsonPath('data.first_name', 'Iryna')
            ->assertJsonPath('data.document_number', 'AB123456');

        $passenger = Passenger::query()->firstWhere('document_number', 'AB123456');

        $this->assertNotNull($passenger);
        $this->assertSame('iryna.melnyk@example.test', $passenger->email);
    }

    public function test_get_passenger(): void
    {
        $passenger = Passenger::factory()->create([
            'document_number' => 'CD987654',
        ]);

        $response = $this->getJson("/api/passengers/{$passenger->id}");

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $passenger->id)
            ->assertJsonPath('data.document_number', 'CD987654');
    }

    public function test_validation_error_on_invalid_payload(): void
    {
        $response = $this->postJson('/api/passengers', [
            'first_name' => '',
            'last_name' => '',
            'email' => 'invalid-email',
            'birthday' => 'invalid-date',
            'document_number' => '',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'first_name',
                'last_name',
                'email',
                'birthday',
                'document_number',
            ]);
    }

    public function test_uniqueness_error_on_duplicate_document_number(): void
    {
        Passenger::factory()->create([
            'document_number' => 'AB123456',
        ]);

        $response = $this->postJson('/api/passengers', $this->validPayload());

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['document_number']);
    }
}
