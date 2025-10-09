<?php

namespace Tests\Unit;

use App\Models\SSOSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SSOSessionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_sso_session()
    {
        $user = User::factory()->create();
        
        $session = SSOSession::create([
            'user_id' => $user->id,
            'session_id' => 'sess_123456789',
            'service_name' => 'sahbandar',
            'token' => 'token_abcdef123456',
            'expires_at' => now()->addHours(2),
            'is_active' => true,
        ]);

        $this->assertInstanceOf(SSOSession::class, $session);
        $this->assertEquals($user->id, $session->user_id);
        $this->assertEquals('sess_123456789', $session->session_id);
        $this->assertEquals('sahbandar', $session->service_name);
        $this->assertTrue($session->is_active);
    }

    /** @test */
    public function it_belongs_to_user()
    {
        $user = User::factory()->create();
        $session = SSOSession::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $session->user);
        $this->assertEquals($user->id, $session->user->id);
    }

    /** @test */
    public function it_can_check_if_session_is_expired()
    {
        $activeSession = SSOSession::factory()->create([
            'expires_at' => now()->addHour(),
            'is_active' => true
        ]);
        
        $expiredSession = SSOSession::factory()->create([
            'expires_at' => now()->subHour(),
            'is_active' => true
        ]);

        $this->assertFalse($activeSession->isExpired());
        $this->assertTrue($expiredSession->isExpired());
    }

    /** @test */
    public function it_can_check_if_session_is_valid()
    {
        $validSession = SSOSession::factory()->create([
            'expires_at' => now()->addHour(),
            'is_active' => true
        ]);
        
        $expiredSession = SSOSession::factory()->create([
            'expires_at' => now()->subHour(),
            'is_active' => true
        ]);
        
        $inactiveSession = SSOSession::factory()->create([
            'expires_at' => now()->addHour(),
            'is_active' => false
        ]);

        $this->assertTrue($validSession->isValid());
        $this->assertFalse($expiredSession->isValid());
        $this->assertFalse($inactiveSession->isValid());
    }

    /** @test */
    public function it_can_deactivate_session()
    {
        $session = SSOSession::factory()->create(['is_active' => true]);
        
        $this->assertTrue($session->is_active);
        
        $session->deactivate();
        
        $this->assertFalse($session->fresh()->is_active);
    }

    /** @test */
    public function it_can_extend_session()
    {
        $session = SSOSession::factory()->create([
            'expires_at' => now()->addHour()
        ]);
        
        $originalExpiry = $session->expires_at;
        
        $session->extend(2); // Extend by 2 hours
        
        $this->assertTrue($session->fresh()->expires_at->gt($originalExpiry));
        $this->assertEquals(
            now()->addHours(2)->format('Y-m-d H:i'),
            $session->fresh()->expires_at->format('Y-m-d H:i')
        );
    }

    /** @test */
    public function it_can_scope_active_sessions()
    {
        SSOSession::factory()->create(['is_active' => true]);
        SSOSession::factory()->create(['is_active' => true]);
        SSOSession::factory()->create(['is_active' => false]);

        $activeSessions = SSOSession::active()->get();

        $this->assertCount(2, $activeSessions);
        $activeSessions->each(function ($session) {
            $this->assertTrue($session->is_active);
        });
    }

    /** @test */
    public function it_can_scope_valid_sessions()
    {
        SSOSession::factory()->create([
            'expires_at' => now()->addHour(),
            'is_active' => true
        ]);
        
        SSOSession::factory()->create([
            'expires_at' => now()->subHour(), // Expired
            'is_active' => true
        ]);
        
        SSOSession::factory()->create([
            'expires_at' => now()->addHour(),
            'is_active' => false // Inactive
        ]);

        $validSessions = SSOSession::valid()->get();

        $this->assertCount(1, $validSessions);
        $this->assertTrue($validSessions->first()->isValid());
    }

    /** @test */
    public function it_can_scope_by_service()
    {
        SSOSession::factory()->create(['service_name' => 'sahbandar']);
        SSOSession::factory()->create(['service_name' => 'sahbandar']);
        SSOSession::factory()->create(['service_name' => 'spb']);

        $sahbandarSessions = SSOSession::forService('sahbandar')->get();

        $this->assertCount(2, $sahbandarSessions);
        $sahbandarSessions->each(function ($session) {
            $this->assertEquals('sahbandar', $session->service_name);
        });
    }

    /** @test */
    public function it_can_scope_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        SSOSession::factory()->create(['user_id' => $user1->id]);
        SSOSession::factory()->create(['user_id' => $user1->id]);
        SSOSession::factory()->create(['user_id' => $user2->id]);

        $user1Sessions = SSOSession::forUser($user1->id)->get();

        $this->assertCount(2, $user1Sessions);
        $user1Sessions->each(function ($session) use ($user1) {
            $this->assertEquals($user1->id, $session->user_id);
        });
    }

    /** @test */
    public function it_can_find_session_by_token()
    {
        $session = SSOSession::factory()->create(['token' => 'unique_token_123']);
        
        $foundSession = SSOSession::byToken('unique_token_123')->first();
        
        $this->assertNotNull($foundSession);
        $this->assertEquals($session->id, $foundSession->id);
        $this->assertEquals('unique_token_123', $foundSession->token);
    }

    /** @test */
    public function it_stores_session_metadata()
    {
        $metadata = [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'login_method' => 'password'
        ];
        
        $session = SSOSession::factory()->create([
            'metadata' => json_encode($metadata)
        ]);

        $decodedMetadata = json_decode($session->metadata, true);
        
        $this->assertEquals($metadata, $decodedMetadata);
        $this->assertEquals('192.168.1.1', $decodedMetadata['ip_address']);
        $this->assertEquals('password', $decodedMetadata['login_method']);
    }

    /** @test */
    public function it_can_cleanup_expired_sessions()
    {
        // Create active sessions
        SSOSession::factory()->create([
            'expires_at' => now()->addHour(),
            'is_active' => true
        ]);
        
        // Create expired sessions
        SSOSession::factory()->create([
            'expires_at' => now()->subHour(),
            'is_active' => true
        ]);
        
        SSOSession::factory()->create([
            'expires_at' => now()->subDay(),
            'is_active' => true
        ]);

        $this->assertCount(3, SSOSession::all());
        
        SSOSession::cleanupExpired();
        
        $this->assertCount(1, SSOSession::all());
        $this->assertTrue(SSOSession::first()->isValid());
    }
}