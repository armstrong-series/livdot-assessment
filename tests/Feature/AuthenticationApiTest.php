<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_returns_a_jwt_for_a_valid_json_api_payload(): void
    {
        $response = $this->postJson('/api/v1/auth/signup', [
            'data' => [
                'type' => 'registration',
                'attributes' => [
                    'name' => 'LIV Host',
                    'email' => 'host@example.test',
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.type', 'signup')
            ->assertJsonPath('data.attributes.user.email', 'host@example.test');

        $this->assertDatabaseHas('users', ['email' => 'host@example.test']);
    }

    public function test_authentication_returns_a_jwt_for_valid_credentials(): void
    {
        User::factory()->create(['email' => 'viewer@example.test', 'password' => 'password123']);

        $response = $this->postJson('/api/v1/auth/login', [
            'data' => [
                'type' => 'authenticate',
                'attributes' => ['email' => 'viewer@example.test', 'password' => 'password123'],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.type', 'authenticate')
            ->assertJsonStructure(['data' => ['attributes' => ['token', 'token_type', 'expires_in', 'user']]]);
    }
}
