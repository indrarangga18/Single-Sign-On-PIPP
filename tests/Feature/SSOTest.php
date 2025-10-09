<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\SSOSession;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SSOTest extends TestCase
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
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        $ssoData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'service' => 'sahbandar',
            'callback_url' => 'https://sahbandar.example.com/sso/callback'
        ];

        $response = $this->postJson('/api/sso/login', $ssoData);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'sso_token',
                        'session_id',
                        'service',
                        'callback_url',
                        'expires_at',
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'nip',
                            'jabatan',
                            'unit_kerja'
                        ]
                    ]
                ]);

        // Check SSO session created
        $this->assertDatabaseHas('sso_sessions', [
            'user_id' => $user->id,
            'service_name' => 'sahbandar',
            'is_active' => true
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'sso_login',
            'model_type' => 'SSOSession'
        ]);
    }

    /** @test */
    public function sso_login_fails_with_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $ssoData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'service' => 'sahbandar',
            'callback_url' => 'https://sahbandar.example.com/sso/callback'
        ];

        $response = $this->postJson('/api/sso/login', $ssoData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);

        // Check no SSO session created
        $this->assertDatabaseMissing('sso_sessions', [
            'service_name' => 'sahbandar'
        ]);
    }

    /** @test */
    public function sso_login_fails_with_inactive_user()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => false
        ]);

        $ssoData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'service' => 'sahbandar',
            'callback_url' => 'https://sahbandar.example.com/sso/callback'
        ];

        $response = $this->postJson('/api/sso/login', $ssoData);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Account is inactive'
                ]);
    }

    /** @test */
    public function sso_login_fails_with_invalid_service()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        $ssoData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'service' => 'invalid_service',
            'callback_url' => 'https://invalid.example.com/sso/callback'
        ];

        $response = $this->postJson('/api/sso/login', $ssoData);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['service']);
    }

    /** @test */
    public function can_validate_sso_token()
    {
        $user = User::factory()->create();
        $session = SSOSession::factory()->create([
            'user_id' => $user->id,
            'service_name' => 'sahbandar',
            'token' => 'valid_sso_token_123',
            'expires_at' => now()->addHours(2),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/sso/validate', [
            'token' => 'valid_sso_token_123',
            'service' => 'sahbandar'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'valid',
                        'user' => [
                            'id',
                            'name',
                            'email',
                            'nip',
                            'jabatan',
                            'unit_kerja',
                            'roles',
                            'permissions'
                        ],
                        'session' => [
                            'id',
                            'session_id',
                            'service_name',
                            'expires_at',
                            'is_active'
                        ]
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'valid' => true
                    ]
                ]);
    }

    /** @test */
    public function sso_token_validation_fails_with_invalid_token()
    {
        $response = $this->postJson('/api/sso/validate', [
            'token' => 'invalid_token_123',
            'service' => 'sahbandar'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'data' => [
                        'valid' => false,
                        'reason' => 'Token not found'
                    ]
                ]);
    }

    /** @test */
    public function sso_token_validation_fails_with_expired_token()
    {
        $user = User::factory()->create();
        SSOSession::factory()->create([
            'user_id' => $user->id,
            'service_name' => 'sahbandar',
            'token' => 'expired_token_123',
            'expires_at' => now()->subHour(),
            'is_active' => true
        ]);

        $response = $this->postJson('/api/sso/validate', [
            'token' => 'expired_token_123',
            'service' => 'sahbandar'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'data' => [
                        'valid' => false,
                        'reason' => 'Token expired'
                    ]
                ]);
    }

    /** @test */
    public function can_get_user_sso_sessions()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create multiple SSO sessions
        SSOSession::factory()->create([
            'user_id' => $user->id,
            'service_name' => 'sahbandar',
            'is_active' => true
        ]);

        SSOSession::factory()->create([
            'user_id' => $user->id,
            'service_name' => 'spb',
            'is_active' => true
        ]);

        SSOSession::factory()->create([
            'user_id' => $user->id,
            'service_name' => 'shti',
            'is_active' => false
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/sso/sessions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'sessions' => [
                            '*' => [
                                'id',
                                'session_id',
                                'service_name',
                                'expires_at',
                                'is_active',
                                'created_at'
                            ]
                        ]
                    ]
                ]);

        $sessions = $response->json('data.sessions');
        $this->assertCount(3, $sessions);
    }

    /** @test */
    public function can_terminate_sso_session()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $session = SSOSession::factory()->create([
            'user_id' => $user->id,
            'service_name' => 'sahbandar',
            'is_active' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/sso/sessions/{$session->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'SSO session terminated successfully'
                ]);

        // Check session is deactivated
        $this->assertDatabaseHas('sso_sessions', [
            'id' => $session->id,
            'is_active' => false
        ]);

        // Check audit log
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'sso_session_terminated',
            'model_type' => 'SSOSession',
            'model_id' => $session->id
        ]);
    }

    /** @test */
    public function cannot_terminate_other_users_sso_session()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $token = JWTAuth::fromUser($user1);

        $session = SSOSession::factory()->create([
            'user_id' => $user2->id,
            'service_name' => 'sahbandar',
            'is_active' => true
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->deleteJson("/api/sso/sessions/{$session->id}");

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'Unauthorized to terminate this session'
                ]);

        // Check session is still active
        $this->assertDatabaseHas('sso_sessions', [
            'id' => $session->id,
            'is_active' => true
        ]);
    }

    /** @test */
    public function can_extend_sso_session()
    {
        $user = User::factory()->create();
        $session = SSOSession::factory()->create([
            'user_id' => $user->id,
            'service_name' => 'sahbandar',
            'token' => 'extend_token_123',
            'expires_at' => now()->addHour(),
            'is_active' => true
        ]);

        $originalExpiry = $session->expires_at;

        $response = $this->postJson('/api/sso/extend', [
            'token' => 'extend_token_123',
            'service' => 'sahbandar',
            'extend_hours' => 2
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'SSO session extended successfully'
                ]);

        $session->refresh();
        $this->assertTrue($session->expires_at->gt($originalExpiry));
    }

    /** @test */
    public function sso_session_cleanup_removes_expired_sessions()
    {
        $user = User::factory()->create();

        // Create active session
        SSOSession::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->addHour(),
            'is_active' => true
        ]);

        // Create expired sessions
        SSOSession::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->subHour(),
            'is_active' => true
        ]);

        SSOSession::factory()->create([
            'user_id' => $user->id,
            'expires_at' => now()->subDay(),
            'is_active' => true
        ]);

        $this->assertCount(3, SSOSession::all());

        $response = $this->postJson('/api/sso/cleanup');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'cleaned_sessions_count'
                    ]
                ]);

        $this->assertCount(1, SSOSession::all());
        $this->assertTrue(SSOSession::first()->isValid());
    }

    /** @test */
    public function sso_login_creates_proper_session_metadata()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        $ssoData = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'service' => 'sahbandar',
            'callback_url' => 'https://sahbandar.example.com/sso/callback'
        ];

        $response = $this->withHeaders([
            'User-Agent' => 'Test Browser 1.0',
            'X-Forwarded-For' => '192.168.1.100'
        ])->postJson('/api/sso/login', $ssoData);

        $response->assertStatus(200);

        $session = SSOSession::where('user_id', $user->id)->first();
        $metadata = json_decode($session->metadata, true);

        $this->assertArrayHasKey('ip_address', $metadata);
        $this->assertArrayHasKey('user_agent', $metadata);
        $this->assertEquals('Test Browser 1.0', $metadata['user_agent']);
    }
}