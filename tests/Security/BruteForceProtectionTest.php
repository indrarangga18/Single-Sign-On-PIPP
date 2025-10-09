<?php

namespace Tests\Security;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class BruteForceProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear rate limiter before each test
        RateLimiter::clear('login');
        RateLimiter::clear('api');
        RateLimiter::clear('sso');
    }

    /** @test */
    public function login_rate_limiting_blocks_excessive_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        // Make 5 failed attempts (should be allowed)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/login', $loginData);
            $response->assertStatus(401);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/auth/login', $loginData);
        $response->assertStatus(429)
                ->assertJson([
                    'success' => false,
                    'message' => 'Too many login attempts'
                ]);

        // Check that failed attempts are logged
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'failed_login_attempt'
        ]);

        $failedAttempts = AuditLog::where('action', 'failed_login_attempt')->count();
        $this->assertEquals(5, $failedAttempts); // Only 5 attempts logged, 6th was blocked
    }

    /** @test */
    public function successful_login_resets_rate_limit()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        $wrongLoginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $correctLoginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        // Make 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/auth/login', $wrongLoginData);
            $response->assertStatus(401);
        }

        // Successful login should reset the counter
        $response = $this->postJson('/api/auth/login', $correctLoginData);
        $response->assertStatus(200);

        // Should be able to make failed attempts again
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/auth/login', $wrongLoginData);
            $response->assertStatus(401);
        }

        // 6th attempt should be rate limited again
        $response = $this->postJson('/api/auth/login', $wrongLoginData);
        $response->assertStatus(429);
    }

    /** @test */
    public function sso_login_rate_limiting_works()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $ssoData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'service' => 'sahbandar',
            'callback_url' => 'https://sahbandar.example.com/callback'
        ];

        // Make multiple failed SSO login attempts
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/sso/login', $ssoData);
            
            if ($i < 5) {
                $response->assertStatus(401); // Invalid credentials
            } else {
                $response->assertStatus(429); // Rate limited
                break;
            }
        }

        // Check that SSO failed attempts are logged
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'sso_failed_login_attempt'
        ]);
    }

    /** @test */
    public function api_rate_limiting_works_for_general_endpoints()
    {
        $user = User::factory()->create();
        
        // Make requests without authentication (should be rate limited)
        for ($i = 0; $i < 100; $i++) {
            $response = $this->getJson('/api/auth/me');
            
            if ($i < 60) {
                $response->assertStatus(401); // Unauthorized
            } else {
                $response->assertStatus(429); // Rate limited
                break;
            }
        }
    }

    /** @test */
    public function rate_limiting_is_per_ip_address()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        // Make failed attempts from first IP
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeaders([
                'X-Forwarded-For' => '192.168.1.1'
            ])->postJson('/api/auth/login', $loginData);
            $response->assertStatus(401);
        }

        // 6th attempt from first IP should be blocked
        $response = $this->withHeaders([
            'X-Forwarded-For' => '192.168.1.1'
        ])->postJson('/api/auth/login', $loginData);
        $response->assertStatus(429);

        // But attempts from different IP should still work
        $response = $this->withHeaders([
            'X-Forwarded-For' => '192.168.1.2'
        ])->postJson('/api/auth/login', $loginData);
        $response->assertStatus(401); // Invalid credentials, not rate limited
    }

    /** @test */
    public function account_lockout_after_multiple_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        // Make 10 failed attempts to trigger account lockout
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/auth/login', $loginData);
        }

        // Check if account lockout audit log is created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'account_locked'
        ]);

        // Even with correct password, login should fail if account is locked
        $correctLoginData = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];

        $response = $this->postJson('/api/auth/login', $correctLoginData);
        $response->assertStatus(423) // Locked
                ->assertJson([
                    'success' => false,
                    'message' => 'Account is temporarily locked due to multiple failed login attempts'
                ]);
    }

    /** @test */
    public function suspicious_activity_detection()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        // Simulate rapid login attempts from different IPs (suspicious behavior)
        $ips = ['192.168.1.1', '192.168.1.2', '192.168.1.3', '192.168.1.4', '192.168.1.5'];
        
        foreach ($ips as $ip) {
            for ($i = 0; $i < 3; $i++) {
                $this->withHeaders([
                    'X-Forwarded-For' => $ip
                ])->postJson('/api/auth/login', [
                    'email' => 'test@example.com',
                    'password' => 'wrongpassword'
                ]);
            }
        }

        // Check if suspicious activity is logged
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'suspicious_activity_detected'
        ]);
    }

    /** @test */
    public function password_spray_attack_detection()
    {
        // Create multiple users
        $users = User::factory()->count(5)->create();
        
        $commonPasswords = ['password', '123456', 'admin', 'test', 'qwerty'];

        // Simulate password spray attack (same password against multiple accounts)
        foreach ($users as $index => $user) {
            $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => $commonPasswords[$index % count($commonPasswords)]
            ]);
        }

        // Check if password spray attack is detected and logged
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_spray_detected'
        ]);
    }

    /** @test */
    public function rate_limit_headers_are_present()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        // Check rate limit headers
        $response->assertHeader('X-RateLimit-Limit')
                ->assertHeader('X-RateLimit-Remaining');
    }

    /** @test */
    public function captcha_required_after_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        // Make 3 failed attempts
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/auth/login', $loginData);
            $response->assertStatus(401);
        }

        // 4th attempt should require captcha
        $response = $this->postJson('/api/auth/login', $loginData);
        $response->assertStatus(422)
                ->assertJsonValidationErrors(['captcha'])
                ->assertJson([
                    'message' => 'Captcha verification required'
                ]);
    }

    /** @test */
    public function progressive_delay_increases_with_failed_attempts()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123')
        ]);

        $loginData = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ];

        $startTime = microtime(true);
        
        // First attempt should be fast
        $this->postJson('/api/auth/login', $loginData);
        $firstAttemptTime = microtime(true) - $startTime;

        // Make more failed attempts
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/auth/login', $loginData);
        }

        $delayStartTime = microtime(true);
        
        // This attempt should have progressive delay
        $this->postJson('/api/auth/login', $loginData);
        $delayedAttemptTime = microtime(true) - $delayStartTime;

        // Delayed attempt should take longer than first attempt
        $this->assertGreaterThan($firstAttemptTime, $delayedAttemptTime);
    }
}