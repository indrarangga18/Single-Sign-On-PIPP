<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\AuditLog;
use App\Models\SSOSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function it_can_create_a_user()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'nip' => '123456789',
            'jabatan' => 'Staff',
            'unit_kerja' => 'IT Department',
            'is_active' => true,
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('Test User', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('123456789', $user->nip);
        $this->assertTrue($user->is_active);
    }

    /** @test */
    public function it_hashes_password_when_creating_user()
    {
        $user = User::factory()->create([
            'password' => 'plaintext_password'
        ]);

        $this->assertTrue(Hash::check('plaintext_password', $user->password));
        $this->assertNotEquals('plaintext_password', $user->password);
    }

    /** @test */
    public function it_can_assign_roles_to_user()
    {
        $user = User::factory()->create();
        $role = Role::findByName('user');

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('user'));
        $this->assertContains('user', $user->getRoleNames()->toArray());
    }

    /** @test */
    public function it_can_assign_permissions_to_user()
    {
        $user = User::factory()->create();
        $permission = Permission::findByName('view_dashboard');

        $user->givePermissionTo($permission);

        $this->assertTrue($user->hasPermissionTo('view_dashboard'));
        $this->assertContains('view_dashboard', $user->getPermissionNames()->toArray());
    }

    /** @test */
    public function it_has_audit_logs_relationship()
    {
        $user = User::factory()->create();
        
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'login',
            'model_type' => 'User',
            'model_id' => $user->id,
            'changes' => json_encode(['status' => 'logged_in']),
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Test Agent',
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->auditLogs);
        $this->assertCount(1, $user->auditLogs);
        $this->assertEquals('login', $user->auditLogs->first()->action);
    }

    /** @test */
    public function it_has_sso_sessions_relationship()
    {
        $user = User::factory()->create();
        
        SSOSession::create([
            'user_id' => $user->id,
            'session_id' => 'test_session_123',
            'service_name' => 'sahbandar',
            'token' => 'test_token_123',
            'expires_at' => now()->addHours(2),
            'is_active' => true,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->ssoSessions);
        $this->assertCount(1, $user->ssoSessions);
        $this->assertEquals('sahbandar', $user->ssoSessions->first()->service_name);
    }

    /** @test */
    public function it_can_check_if_user_is_active()
    {
        $activeUser = User::factory()->create(['is_active' => true]);
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $this->assertTrue($activeUser->is_active);
        $this->assertFalse($inactiveUser->is_active);
    }

    /** @test */
    public function it_can_get_user_full_name_with_nip()
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'nip' => '123456789'
        ]);

        $this->assertEquals('John Doe (123456789)', $user->getFullNameWithNip());
    }

    /** @test */
    public function it_can_scope_active_users()
    {
        User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => true]);
        User::factory()->create(['is_active' => false]);

        $activeUsers = User::active()->get();

        $this->assertCount(2, $activeUsers);
        $activeUsers->each(function ($user) {
            $this->assertTrue($user->is_active);
        });
    }

    /** @test */
    public function it_validates_email_uniqueness()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create(['email' => 'test@example.com']);
    }

    /** @test */
    public function it_validates_nip_uniqueness()
    {
        User::factory()->create(['nip' => '123456789']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create(['nip' => '123456789']);
    }
}