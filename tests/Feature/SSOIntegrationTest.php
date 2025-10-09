<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SSOSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SSOIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function user_can_initiate_sso_login()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        $response = $this->postJson('/api/sso/initiate', [
            'service' => 'sahbandar',
            'redirect_url' => 'https://sahbandar.example.com/auth/callback'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'sso_token',
                        'redirect_url',
                        'expires_at'
                    ]
                ]);

        $ssoToken = $response->json('data.sso_token');
        
        // Verify SSO session was created
        $this->assertDatabaseHas('sso_sessions', [
            'token' => $ssoToken,
            'service' => 'sahbandar',
            'is_active' => true
        ]);
    }

    /** @test */
    public function user_can_authenticate_with_sso_token()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        // Create SSO session
        $ssoSession = SSOSession::create([
            'user_id' => $user->id,
            'token' => 'sso_' . bin2hex(random_bytes(32)),
            'service' => 'sahbandar',
            'expires_at' => now()->addMinutes(10),
            'is_active' => true,
            'metadata' => json_encode([
                'redirect_url' => 'https://sahbandar.example.com/auth/callback',
                'ip_address' => '127.0.0.1'
            ])
        ]);

        $response = $this->postJson('/api/sso/authenticate', [
            'sso_token' => $ssoSession->token,
            'service' => 'sahbandar'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'nip'
                        ]
                    ]
                ]);

        // Verify SSO session was deactivated after use
        $this->assertDatabaseHas('sso_sessions', [
            'id' => $ssoSession->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function sso_authentication_fails_with_invalid_token()
    {
        $response = $this->postJson('/api/sso/authenticate', [
            'sso_token' => 'invalid_token',
            'service' => 'sahbandar'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid SSO token'
                ]);
    }

    /** @test */
    public function sso_authentication_fails_with_expired_token()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        // Create expired SSO session
        $ssoSession = SSOSession::create([
            'user_id' => $user->id,
            'token' => 'sso_' . bin2hex(random_bytes(32)),
            'service' => 'sahbandar',
            'expires_at' => now()->subMinutes(5), // Expired
            'is_active' => true
        ]);

        $response = $this->postJson('/api/sso/authenticate', [
            'sso_token' => $ssoSession->token,
            'service' => 'sahbandar'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'SSO token has expired'
                ]);
    }

    /** @test */
    public function sso_authentication_fails_for_wrong_service()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        // Create SSO session for sahbandar service
        $ssoSession = SSOSession::create([
            'user_id' => $user->id,
            'token' => 'sso_' . bin2hex(random_bytes(32)),
            'service' => 'sahbandar',
            'expires_at' => now()->addMinutes(10),
            'is_active' => true
        ]);

        // Try to authenticate with different service
        $response = $this->postJson('/api/sso/authenticate', [
            'sso_token' => $ssoSession->token,
            'service' => 'spb' // Different service
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'SSO token not valid for this service'
                ]);
    }

    /** @test */
    public function user_can_logout_from_sso_session()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        $ssoSession = SSOSession::create([
            'user_id' => $user->id,
            'token' => 'sso_' . bin2hex(random_bytes(32)),
            'service' => 'sahbandar',
            'expires_at' => now()->addMinutes(10),
            'is_active' => true
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/sso/logout', [
            'sso_token' => $ssoSession->token
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'SSO session terminated successfully'
                ]);

        // Verify SSO session was deactivated
        $this->assertDatabaseHas('sso_sessions', [
            'id' => $ssoSession->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function sso_token_exchange_works_correctly()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        // Mock external service token validation
        Http::fake([
            'sahbandar.example.com/api/auth/validate' => Http::response([
                'valid' => true,
                'user_id' => $user->id,
                'permissions' => ['read', 'write']
            ], 200)
        ]);

        $response = $this->postJson('/api/sso/token-exchange', [
            'external_token' => 'external_service_token_123',
            'service' => 'sahbandar'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'access_token',
                        'token_type',
                        'expires_in',
                        'user'
                    ]
                ]);

        // Verify HTTP request was made to validate external token
        Http::assertSent(function ($request) {
            return $request->url() === 'https://sahbandar.example.com/api/auth/validate' &&
                   $request['token'] === 'external_service_token_123';
        });
    }

    /** @test */
    public function sso_session_can_be_extended()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        $ssoSession = SSOSession::create([
            'user_id' => $user->id,
            'token' => 'sso_' . bin2hex(random_bytes(32)),
            'service' => 'sahbandar',
            'expires_at' => now()->addMinutes(5),
            'is_active' => true
        ]);

        $originalExpiresAt = $ssoSession->expires_at;
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/sso/extend', [
            'sso_token' => $ssoSession->token,
            'extend_minutes' => 30
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'SSO session extended successfully'
                ]);

        // Verify session was extended
        $ssoSession->refresh();
        $this->assertTrue($ssoSession->expires_at->gt($originalExpiresAt));
    }

    /** @test */
    public function sso_supports_multiple_concurrent_sessions()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        // Create multiple SSO sessions for different services
        $services = ['sahbandar', 'spb', 'shti', 'epit'];
        $ssoTokens = [];

        foreach ($services as $service) {
            $response = $this->postJson('/api/sso/initiate', [
                'service' => $service,
                'redirect_url' => "https://{$service}.example.com/auth/callback"
            ]);

            $response->assertStatus(200);
            $ssoTokens[$service] = $response->json('data.sso_token');
        }

        // Verify all sessions exist and are active
        foreach ($services as $service) {
            $this->assertDatabaseHas('sso_sessions', [
                'token' => $ssoTokens[$service],
                'service' => $service,
                'is_active' => true
            ]);
        }

        // Authenticate with each token
        foreach ($services as $service) {
            $response = $this->postJson('/api/sso/authenticate', [
                'sso_token' => $ssoTokens[$service],
                'service' => $service
            ]);

            $response->assertStatus(200);
        }
    }

    /** @test */
    public function sso_session_metadata_is_stored_correctly()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        $metadata = [
            'redirect_url' => 'https://sahbandar.example.com/dashboard',
            'client_ip' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'requested_permissions' => ['read', 'write', 'admin']
        ];

        $response = $this->withHeaders([
            'X-Forwarded-For' => $metadata['client_ip'],
            'User-Agent' => $metadata['user_agent']
        ])->postJson('/api/sso/initiate', [
            'service' => 'sahbandar',
            'redirect_url' => $metadata['redirect_url'],
            'permissions' => $metadata['requested_permissions']
        ]);

        $response->assertStatus(200);
        $ssoToken = $response->json('data.sso_token');

        // Verify metadata was stored
        $ssoSession = SSOSession::where('token', $ssoToken)->first();
        $storedMetadata = json_decode($ssoSession->metadata, true);

        $this->assertEquals($metadata['redirect_url'], $storedMetadata['redirect_url']);
        $this->assertEquals($metadata['client_ip'], $storedMetadata['ip_address']);
        $this->assertEquals($metadata['user_agent'], $storedMetadata['user_agent']);
        $this->assertEquals($metadata['requested_permissions'], $storedMetadata['requested_permissions']);
    }

    /** @test */
    public function sso_rate_limiting_prevents_abuse()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        // Attempt to create many SSO sessions rapidly
        $successfulRequests = 0;
        $rateLimitedRequests = 0;

        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/sso/initiate', [
                'service' => 'sahbandar',
                'redirect_url' => 'https://sahbandar.example.com/auth/callback'
            ]);

            if ($response->status() === 200) {
                $successfulRequests++;
            } elseif ($response->status() === 429) {
                $rateLimitedRequests++;
            }
        }

        // Should have some successful requests but also rate limiting
        $this->assertGreaterThan(0, $successfulRequests);
        $this->assertGreaterThan(0, $rateLimitedRequests);
    }

    /** @test */
    public function sso_audit_logging_tracks_all_activities()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        // Initiate SSO
        $response = $this->postJson('/api/sso/initiate', [
            'service' => 'sahbandar',
            'redirect_url' => 'https://sahbandar.example.com/auth/callback'
        ]);

        $ssoToken = $response->json('data.sso_token');

        // Authenticate with SSO
        $this->postJson('/api/sso/authenticate', [
            'sso_token' => $ssoToken,
            'service' => 'sahbandar'
        ]);

        // Verify audit logs were created
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sso_initiated',
            'auditable_type' => 'App\Models\SSOSession'
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sso_authenticated',
            'auditable_type' => 'App\Models\SSOSession'
        ]);
    }

    /** @test */
    public function sso_works_with_service_specific_permissions()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        // Assign service-specific permissions
        $user->givePermissionTo('sahbandar.read');
        $user->givePermissionTo('sahbandar.write');

        $ssoSession = SSOSession::create([
            'user_id' => $user->id,
            'token' => 'sso_' . bin2hex(random_bytes(32)),
            'service' => 'sahbandar',
            'expires_at' => now()->addMinutes(10),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/sso/authenticate', [
            'sso_token' => $ssoSession->token,
            'service' => 'sahbandar'
        ]);

        $response->assertStatus(200);

        $userData = $response->json('data.user');
        $this->assertArrayHasKey('permissions', $userData);
        $this->assertContains('sahbandar.read', $userData['permissions']);
        $this->assertContains('sahbandar.write', $userData['permissions']);
    }

    /** @test */
    public function sso_cleanup_removes_expired_sessions()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        // Create expired SSO sessions
        SSOSession::create([
            'user_id' => $user->id,
            'token' => 'expired_token_1',
            'service' => 'sahbandar',
            'expires_at' => now()->subHours(2),
            'is_active' => true
        ]);

        SSOSession::create([
            'user_id' => $user->id,
            'token' => 'expired_token_2',
            'service' => 'spb',
            'expires_at' => now()->subHours(1),
            'is_active' => true
        ]);

        // Create active session
        SSOSession::create([
            'user_id' => $user->id,
            'token' => 'active_token',
            'service' => 'shti',
            'expires_at' => now()->addMinutes(10),
            'is_active' => true
        ]);

        // Run cleanup command
        $this->artisan('sso:cleanup-expired');

        // Verify expired sessions were removed or deactivated
        $this->assertDatabaseMissing('sso_sessions', [
            'token' => 'expired_token_1',
            'is_active' => true
        ]);

        $this->assertDatabaseMissing('sso_sessions', [
            'token' => 'expired_token_2',
            'is_active' => true
        ]);

        // Verify active session remains
        $this->assertDatabaseHas('sso_sessions', [
            'token' => 'active_token',
            'is_active' => true
        ]);
    }

    /** @test */
    public function sso_cross_service_logout_works()
    {
        $user = User::factory()->create([
            'is_active' => true
        ]);

        // Create multiple active SSO sessions
        $services = ['sahbandar', 'spb', 'shti'];
        $ssoSessions = [];

        foreach ($services as $service) {
            $ssoSessions[$service] = SSOSession::create([
                'user_id' => $user->id,
                'token' => 'sso_' . $service . '_' . bin2hex(random_bytes(16)),
                'service' => $service,
                'expires_at' => now()->addMinutes(10),
                'is_active' => true
            ]);
        }

        $token = JWTAuth::fromUser($user);

        // Perform global SSO logout
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/sso/logout-all');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'All SSO sessions terminated successfully'
                ]);

        // Verify all SSO sessions were deactivated
        foreach ($services as $service) {
            $this->assertDatabaseHas('sso_sessions', [
                'id' => $ssoSessions[$service]->id,
                'is_active' => false
            ]);
        }
    }
}