<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class TurnCredentialsTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/call/turn-credentials');

        $response->assertStatus(401);
    }

    public function test_returns_stun_only_when_turn_not_configured(): void
    {
        config(['services.turn.server_url' => null]);
        config(['services.turn.secret' => null]);
        config(['services.turn.username' => null]);
        config(['services.turn.credential' => null]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/call/turn-credentials');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'iceServers' => [
                    '*' => ['urls'],
                ],
                'ttl',
            ],
        ]);

        $this->assertCount(1, $response->json('data.iceServers'));
        $this->assertStringStartsWith('stun:', $response->json('data.iceServers.0.urls'));
    }

    public function test_returns_time_limited_turn_credentials_when_secret_configured(): void
    {
        config(['services.turn.server_url' => 'turn:turn.example.com:3478']);
        config(['services.turn.secret' => 'test_secret_2024']);
        config(['services.turn.ttl' => 3600]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/call/turn-credentials');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'iceServers' => [
                    '*' => ['urls', 'username', 'credential'],
                ],
                'ttl',
            ],
        ]);

        $iceServers = $response->json('data.iceServers');

        $this->assertCount(2, $iceServers);

        $turnServer = collect($iceServers)->firstWhere('urls', 'turn:turn.example.com:3478');
        $this->assertNotNull($turnServer);

        $this->assertStringContainsString(':', $turnServer['username']);

        [$expires, $sessionId] = explode(':', $turnServer['username'], 2);
        $this->assertNotEmpty($sessionId);
        $this->assertGreaterThan(now()->unix(), (int) $expires);

        $expected = base64_encode(hash_hmac('sha1', $turnServer['username'], 'test_secret_2024', binary: true));
        $this->assertSame($expected, $turnServer['credential']);

        $this->assertSame(3600, $response->json('data.ttl'));
    }

    public function test_returns_static_turn_credentials_when_legacy_config_used(): void
    {
        config(['services.turn.server_url' => 'turn:turn.example.com:3478']);
        config(['services.turn.secret' => null]);
        config(['services.turn.username' => 'static_user']);
        config(['services.turn.credential' => 'static_pass']);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/call/turn-credentials');

        $response->assertStatus(200);

        $iceServers = $response->json('data.iceServers');
        $this->assertCount(2, $iceServers);

        $turnServer = collect($iceServers)->firstWhere('urls', 'turn:turn.example.com:3478');
        $this->assertNotNull($turnServer);
        $this->assertSame('static_user', $turnServer['username']);
        $this->assertSame('static_pass', $turnServer['credential']);
    }

    public function test_credential_username_changes_on_each_request(): void
    {
        config(['services.turn.server_url' => 'turn:turn.example.com:3478']);
        config(['services.turn.secret' => 'test_secret_2024']);

        $user = User::factory()->create();

        $first = $this->actingAs($user)->getJson('/api/v1/call/turn-credentials');
        $this->travel(config('services.turn.ttl') + 60)->seconds();
        $second = $this->actingAs($user)->getJson('/api/v1/call/turn-credentials');

        $firstUser = $first->json('data.iceServers.1.username');
        $secondUser = $second->json('data.iceServers.1.username');

        $this->assertNotSame($firstUser, $secondUser);
    }

    public function test_returns_default_ttl_when_not_configured(): void
    {
        config(['services.turn.ttl' => null]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/v1/call/turn-credentials');

        $response->assertStatus(200);
        $this->assertSame(86400, $response->json('data.ttl'));
    }
}
