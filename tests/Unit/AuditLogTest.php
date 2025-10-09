<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_audit_log()
    {
        $user = User::factory()->create();
        
        $auditLog = AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'model_type' => 'User',
            'model_id' => $user->id,
            'changes' => json_encode(['status' => 'logged_in']),
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 Test Browser',
        ]);

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals('login', $auditLog->action);
        $this->assertEquals('User', $auditLog->model_type);
        $this->assertEquals('192.168.1.1', $auditLog->ip_address);
    }

    /** @test */
    public function it_belongs_to_user()
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $auditLog->user);
        $this->assertEquals($user->id, $auditLog->user->id);
    }

    /** @test */
    public function it_can_decode_changes_json()
    {
        $changes = ['old_value' => 'test', 'new_value' => 'updated'];
        $auditLog = AuditLog::factory()->create([
            'changes' => json_encode($changes)
        ]);

        $decodedChanges = json_decode($auditLog->changes, true);
        
        $this->assertEquals($changes, $decodedChanges);
        $this->assertEquals('test', $decodedChanges['old_value']);
        $this->assertEquals('updated', $decodedChanges['new_value']);
    }

    /** @test */
    public function it_can_scope_by_action()
    {
        AuditLog::factory()->create(['action' => 'login']);
        AuditLog::factory()->create(['action' => 'login']);
        AuditLog::factory()->create(['action' => 'logout']);

        $loginLogs = AuditLog::where('action', 'login')->get();
        
        $this->assertCount(2, $loginLogs);
        $loginLogs->each(function ($log) {
            $this->assertEquals('login', $log->action);
        });
    }

    /** @test */
    public function it_can_scope_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        
        AuditLog::factory()->create(['user_id' => $user1->id]);
        AuditLog::factory()->create(['user_id' => $user1->id]);
        AuditLog::factory()->create(['user_id' => $user2->id]);

        $user1Logs = AuditLog::where('user_id', $user1->id)->get();
        
        $this->assertCount(2, $user1Logs);
        $user1Logs->each(function ($log) use ($user1) {
            $this->assertEquals($user1->id, $log->user_id);
        });
    }

    /** @test */
    public function it_can_scope_by_date_range()
    {
        $yesterday = now()->subDay();
        $today = now();
        $tomorrow = now()->addDay();

        AuditLog::factory()->create(['created_at' => $yesterday]);
        AuditLog::factory()->create(['created_at' => $today]);
        AuditLog::factory()->create(['created_at' => $tomorrow]);

        $todayLogs = AuditLog::whereDate('created_at', $today->toDateString())->get();
        
        $this->assertCount(1, $todayLogs);
    }

    /** @test */
    public function it_stores_ip_address_and_user_agent()
    {
        $auditLog = AuditLog::factory()->create([
            'ip_address' => '203.0.113.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);

        $this->assertEquals('203.0.113.1', $auditLog->ip_address);
        $this->assertStringContains('Mozilla/5.0', $auditLog->user_agent);
    }

    /** @test */
    public function it_can_log_different_model_types()
    {
        $user = User::factory()->create();
        
        $userLog = AuditLog::factory()->create([
            'model_type' => 'User',
            'model_id' => $user->id
        ]);
        
        $sessionLog = AuditLog::factory()->create([
            'model_type' => 'SSOSession',
            'model_id' => 123
        ]);

        $this->assertEquals('User', $userLog->model_type);
        $this->assertEquals('SSOSession', $sessionLog->model_type);
        $this->assertEquals($user->id, $userLog->model_id);
        $this->assertEquals(123, $sessionLog->model_id);
    }

    /** @test */
    public function it_can_log_security_events()
    {
        $securityLog = AuditLog::factory()->create([
            'action' => 'failed_login_attempt',
            'changes' => json_encode([
                'email' => 'test@example.com',
                'reason' => 'invalid_password',
                'attempts' => 3
            ])
        ]);

        $changes = json_decode($securityLog->changes, true);
        
        $this->assertEquals('failed_login_attempt', $securityLog->action);
        $this->assertEquals('test@example.com', $changes['email']);
        $this->assertEquals('invalid_password', $changes['reason']);
        $this->assertEquals(3, $changes['attempts']);
    }
}