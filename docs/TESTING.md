# Testing Guide - SSO PIPP

Panduan lengkap untuk testing sistem Single Sign-On Platform Informasi Pelabuhan Perikanan (PIPP).

## 🧪 Testing Strategy

### Testing Pyramid

```
    /\
   /  \     E2E Tests (10%)
  /____\    
 /      \   Integration Tests (20%)
/________\  Unit Tests (70%)
```

### Testing Types

1. **Unit Tests**: Test individual components/methods
2. **Feature Tests**: Test API endpoints and features
3. **Integration Tests**: Test service integrations
4. **Security Tests**: Test security vulnerabilities
5. **Performance Tests**: Test system performance
6. **E2E Tests**: Test complete user workflows

---

## 🔧 Test Environment Setup

### PHPUnit Configuration

#### phpunit.xml
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="./vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory suffix="Test.php">./tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory suffix="Test.php">./tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory suffix="Test.php">./tests/Integration</directory>
        </testsuite>
        <testsuite name="Security">
            <directory suffix="Test.php">./tests/Security</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">./app</directory>
        </include>
        <exclude>
            <directory>./app/Console</directory>
            <file>./app/Http/Kernel.php</file>
        </exclude>
    </coverage>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

### Test Database Setup

#### Database Factories
```php
// database/factories/UserFactory.php
class UserFactory extends Factory
{
    protected $model = User::class;
    
    public function definition()
    {
        return [
            'username' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'nip' => $this->faker->unique()->numerify('##########'),
            'phone' => $this->faker->phoneNumber,
            'department' => $this->faker->randomElement(['IT', 'HR', 'Finance', 'Operations']),
            'position' => $this->faker->jobTitle,
            'office_location' => $this->faker->city,
            'status' => 'active',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ];
    }
    
    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'inactive',
            ];
        });
    }
    
    public function admin()
    {
        return $this->state(function (array $attributes) {
            return [
                'username' => 'admin',
                'email' => 'admin@pipp.kkp.go.id',
            ];
        });
    }
}
```

#### Test Seeders
```php
// database/seeders/TestSeeder.php
class TestSeeder extends Seeder
{
    public function run()
    {
        // Create roles and permissions
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);
        
        $permissions = [
            'sahbandar.read', 'sahbandar.write',
            'spb.read', 'spb.write',
            'shti.read', 'shti.write',
            'epit.read', 'epit.write',
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }
        
        $adminRole->givePermissionTo($permissions);
        $userRole->givePermissionTo(['sahbandar.read', 'spb.read']);
        
        // Create test users
        $admin = User::factory()->admin()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create();
        $user->assignRole('user');
    }
}
```

---

## 🧪 Unit Tests

### Model Tests

#### User Model Test
```php
// tests/Unit/Models/UserTest.php
class UserTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_be_created()
    {
        $userData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'nip' => '1234567890',
            'password' => Hash::make('password'),
        ];
        
        $user = User::create($userData);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('test@example.com', $user->email);
    }
    
    public function test_user_password_is_hashed()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);
        
        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertNotEquals('password', $user->password);
    }
    
    public function test_user_has_full_name_attribute()
    {
        $user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        
        $this->assertEquals('John Doe', $user->full_name);
    }
    
    public function test_user_can_have_roles()
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'admin']);
        
        $user->assignRole($role);
        
        $this->assertTrue($user->hasRole('admin'));
        $this->assertCount(1, $user->roles);
    }
}
```

### Service Tests

#### AuthService Test
```php
// tests/Unit/Services/AuthServiceTest.php
class AuthServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected AuthService $authService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }
    
    public function test_can_authenticate_user_with_valid_credentials()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        
        $result = $this->authService->authenticate('testuser', 'password');
        
        $this->assertTrue($result['success']);
        $this->assertEquals($user->id, $result['user']->id);
        $this->assertArrayHasKey('token', $result);
    }
    
    public function test_cannot_authenticate_user_with_invalid_credentials()
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        
        $result = $this->authService->authenticate('testuser', 'wrongpassword');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['message']);
    }
    
    public function test_cannot_authenticate_inactive_user()
    {
        User::factory()->inactive()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        
        $result = $this->authService->authenticate('testuser', 'password');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Account is inactive', $result['message']);
    }
    
    public function test_can_refresh_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        
        $refreshedToken = $this->authService->refreshToken($token);
        
        $this->assertNotNull($refreshedToken);
        $this->assertNotEquals($token, $refreshedToken);
    }
}
```

### Middleware Tests

#### JWT Auth Middleware Test
```php
// tests/Unit/Middleware/JWTAuthMiddlewareTest.php
class JWTAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;
    
    protected JWTAuthMiddleware $middleware;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new JWTAuthMiddleware();
    }
    
    public function test_allows_request_with_valid_token()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);
        
        $request = Request::create('/api/profile', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $token);
        
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });
        
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function test_rejects_request_without_token()
    {
        $request = Request::create('/api/profile', 'GET');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });
        
        $this->assertEquals(401, $response->getStatusCode());
    }
    
    public function test_rejects_request_with_invalid_token()
    {
        $request = Request::create('/api/profile', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid_token');
        
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });
        
        $this->assertEquals(401, $response->getStatusCode());
    }
}
```

---

## 🔗 Feature Tests

### Authentication Tests

#### Login Test
```php
// tests/Feature/Auth/LoginTest.php
class LoginTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        
        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => [
                        'id', 'username', 'email', 'first_name', 'last_name'
                    ],
                    'token',
                    'expires_in'
                ]
            ]);
    }
    
    public function test_user_cannot_login_with_invalid_credentials()
    {
        User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        
        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
        ]);
        
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials'
            ]);
    }
    
    public function test_login_requires_username_and_password()
    {
        $response = $this->postJson('/api/auth/login', []);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username', 'password']);
    }
    
    public function test_login_is_rate_limited()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        
        // Make multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/api/auth/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
        }
        
        // Next attempt should be rate limited
        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);
        
        $response->assertStatus(429);
    }
}
```

### SSO Tests

#### SSO Login Test
```php
// tests/Feature/SSO/SSOLoginTest.php
class SSOLoginTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_initiate_sso_login()
    {
        $response = $this->postJson('/api/sso/login', [
            'service' => 'sahbandar',
            'callback_url' => 'https://sahbandar.pipp.kkp.go.id/auth/callback',
        ]);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'sso_url',
                    'session_id',
                    'expires_at'
                ]
            ]);
    }
    
    public function test_can_validate_sso_token()
    {
        $user = User::factory()->create();
        $ssoSession = SSOSession::create([
            'user_id' => $user->id,
            'service' => 'sahbandar',
            'session_id' => Str::uuid(),
            'token' => Str::random(64),
            'expires_at' => now()->addHour(),
            'is_active' => true,
        ]);
        
        $response = $this->postJson('/api/sso/validate', [
            'token' => $ssoSession->token,
            'service' => 'sahbandar',
        ]);
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'valid' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id', 'username', 'email'
                    ],
                    'permissions'
                ]
            ]);
    }
    
    public function test_cannot_validate_expired_sso_token()
    {
        $user = User::factory()->create();
        $ssoSession = SSOSession::create([
            'user_id' => $user->id,
            'service' => 'sahbandar',
            'session_id' => Str::uuid(),
            'token' => Str::random(64),
            'expires_at' => now()->subHour(), // Expired
            'is_active' => true,
        ]);
        
        $response = $this->postJson('/api/sso/validate', [
            'token' => $ssoSession->token,
            'service' => 'sahbandar',
        ]);
        
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'valid' => false,
                'message' => 'Token expired'
            ]);
    }
}
```

### Service Integration Tests

#### Sahbandar Service Test
```php
// tests/Feature/Services/SahbandarServiceTest.php
class SahbandarServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->user->givePermissionTo('sahbandar.read');
        
        $this->actingAs($this->user, 'api');
    }
    
    public function test_can_get_user_profile()
    {
        Http::fake([
            'sahbandar.pipp.kkp.go.id/api/profile/*' => Http::response([
                'success' => true,
                'data' => [
                    'user_id' => $this->user->id,
                    'profile_data' => ['key' => 'value'],
                ]
            ], 200)
        ]);
        
        $response = $this->getJson('/api/sahbandar/profile');
        
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
    
    public function test_requires_permission_to_access_sahbandar()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser, 'api');
        
        $response = $this->getJson('/api/sahbandar/profile');
        
        $response->assertStatus(403);
    }
    
    public function test_handles_sahbandar_service_unavailable()
    {
        Http::fake([
            'sahbandar.pipp.kkp.go.id/*' => Http::response([], 503)
        ]);
        
        $response = $this->getJson('/api/sahbandar/profile');
        
        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'message' => 'Sahbandar service unavailable'
            ]);
    }
}
```

---

## 🔒 Security Tests

### Authentication Security Tests

#### Brute Force Protection Test
```php
// tests/Security/BruteForceProtectionTest.php
class BruteForceProtectionTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_account_locked_after_multiple_failed_attempts()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        
        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
            ]);
        }
        
        // Account should be locked
        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'password', // Correct password
        ]);
        
        $response->assertStatus(423)
            ->assertJson([
                'success' => false,
                'message' => 'Account temporarily locked'
            ]);
    }
    
    public function test_ip_blocked_after_multiple_failed_attempts()
    {
        // Create multiple users
        $users = User::factory()->count(3)->create();
        
        // Try to login with different usernames from same IP
        foreach ($users as $user) {
            for ($i = 0; $i < 3; $i++) {
                $this->postJson('/api/auth/login', [
                    'username' => $user->username,
                    'password' => 'wrongpassword',
                ]);
            }
        }
        
        // IP should be blocked
        $response = $this->postJson('/api/auth/login', [
            'username' => $users[0]->username,
            'password' => 'password',
        ]);
        
        $response->assertStatus(429);
    }
}
```

### Input Validation Security Tests

#### SQL Injection Test
```php
// tests/Security/SQLInjectionTest.php
class SQLInjectionTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_login_protected_against_sql_injection()
    {
        $maliciousInputs = [
            "admin'; DROP TABLE users; --",
            "admin' OR '1'='1",
            "admin' UNION SELECT * FROM users --",
            "'; DELETE FROM users WHERE '1'='1",
        ];
        
        foreach ($maliciousInputs as $input) {
            $response = $this->postJson('/api/auth/login', [
                'username' => $input,
                'password' => 'password',
            ]);
            
            // Should return validation error or unauthorized, not 500
            $this->assertContains($response->getStatusCode(), [401, 422]);
        }
        
        // Verify users table still exists and has data
        $this->assertDatabaseHas('users', ['id' => 1]);
    }
    
    public function test_search_protected_against_sql_injection()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        
        $maliciousQuery = "'; DROP TABLE users; --";
        
        $response = $this->getJson('/api/users/search?q=' . urlencode($maliciousQuery));
        
        // Should handle gracefully
        $this->assertContains($response->getStatusCode(), [200, 422]);
        
        // Verify users table still exists
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
```

### XSS Protection Test

```php
// tests/Security/XSSProtectionTest.php
class XSSProtectionTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_profile_update_protected_against_xss()
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        
        $maliciousInputs = [
            '<script>alert("XSS")</script>',
            '<img src="x" onerror="alert(1)">',
            'javascript:alert("XSS")',
            '<svg onload="alert(1)">',
        ];
        
        foreach ($maliciousInputs as $input) {
            $response = $this->putJson('/api/profile', [
                'first_name' => $input,
            ]);
            
            // Should return validation error
            $response->assertStatus(422);
        }
    }
    
    public function test_response_headers_prevent_xss()
    {
        $response = $this->getJson('/api/health');
        
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }
}
```

---

## ⚡ Performance Tests

### Load Testing

#### API Performance Test
```php
// tests/Performance/APIPerformanceTest.php
class APIPerformanceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_login_endpoint_performance()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        
        $startTime = microtime(true);
        
        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);
        
        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        
        $response->assertStatus(200);
        
        // Assert response time is under 500ms
        $this->assertLessThan(500, $responseTime, 
            "Login endpoint took {$responseTime}ms, should be under 500ms");
    }
    
    public function test_concurrent_login_requests()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        
        $promises = [];
        $startTime = microtime(true);
        
        // Simulate 10 concurrent requests
        for ($i = 0; $i < 10; $i++) {
            $promises[] = $this->postJson('/api/auth/login', [
                'username' => 'testuser',
                'password' => 'password',
            ]);
        }
        
        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        
        // All requests should complete within reasonable time
        $this->assertLessThan(2000, $totalTime, 
            "10 concurrent requests took {$totalTime}ms, should be under 2000ms");
        
        // All requests should succeed
        foreach ($promises as $response) {
            $response->assertStatus(200);
        }
    }
}
```

### Database Performance Test

```php
// tests/Performance/DatabasePerformanceTest.php
class DatabasePerformanceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_query_performance_with_large_dataset()
    {
        // Create 1000 users
        User::factory()->count(1000)->create();
        
        $startTime = microtime(true);
        
        // Query users with pagination
        $users = User::with('roles')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        $endTime = microtime(true);
        $queryTime = ($endTime - $startTime) * 1000;
        
        // Query should complete within 100ms
        $this->assertLessThan(100, $queryTime, 
            "User query took {$queryTime}ms, should be under 100ms");
        
        $this->assertCount(20, $users->items());
    }
    
    public function test_audit_log_insertion_performance()
    {
        $startTime = microtime(true);
        
        // Insert 100 audit logs
        for ($i = 0; $i < 100; $i++) {
            AuditLog::create([
                'user_id' => 1,
                'action' => 'test_action',
                'service' => 'test',
                'description' => 'Test audit log entry',
                'ip_address' => '127.0.0.1',
            ]);
        }
        
        $endTime = microtime(true);
        $insertTime = ($endTime - $startTime) * 1000;
        
        // Bulk insert should complete within 500ms
        $this->assertLessThan(500, $insertTime, 
            "100 audit log insertions took {$insertTime}ms, should be under 500ms");
    }
}
```

---

## 🔄 Integration Tests

### Microservice Integration Tests

#### Service Communication Test
```php
// tests/Integration/MicroserviceIntegrationTest.php
class MicroserviceIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_sahbandar_service_integration()
    {
        Http::fake([
            'sahbandar.pipp.kkp.go.id/api/health' => Http::response([
                'status' => 'healthy',
                'timestamp' => now()->toISOString(),
            ], 200),
            'sahbandar.pipp.kkp.go.id/api/profile/*' => Http::response([
                'success' => true,
                'data' => [
                    'user_id' => 1,
                    'profile_data' => ['key' => 'value'],
                ]
            ], 200)
        ]);
        
        $sahbandarService = new SahbandarService();
        
        // Test health check
        $healthStatus = $sahbandarService->checkHealth();
        $this->assertTrue($healthStatus['healthy']);
        
        // Test data retrieval
        $profile = $sahbandarService->getUserProfile(1);
        $this->assertTrue($profile['success']);
        $this->assertArrayHasKey('profile_data', $profile['data']);
    }
    
    public function test_service_failover_mechanism()
    {
        // Simulate primary service failure
        Http::fake([
            'sahbandar.pipp.kkp.go.id/*' => Http::response([], 503),
            'sahbandar-backup.pipp.kkp.go.id/*' => Http::response([
                'success' => true,
                'data' => ['fallback' => true],
            ], 200)
        ]);
        
        $sahbandarService = new SahbandarService();
        $result = $sahbandarService->getUserProfile(1);
        
        // Should fallback to backup service
        $this->assertTrue($result['success']);
        $this->assertTrue($result['data']['fallback']);
    }
}
```

### Cache Integration Test

```php
// tests/Integration/CacheIntegrationTest.php
class CacheIntegrationTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_data_caching()
    {
        $user = User::factory()->create();
        
        // First call should hit database
        $startTime = microtime(true);
        $userData1 = app(UserService::class)->getUserWithCache($user->id);
        $firstCallTime = microtime(true) - $startTime;
        
        // Second call should hit cache
        $startTime = microtime(true);
        $userData2 = app(UserService::class)->getUserWithCache($user->id);
        $secondCallTime = microtime(true) - $startTime;
        
        // Cache hit should be significantly faster
        $this->assertLessThan($firstCallTime / 2, $secondCallTime);
        $this->assertEquals($userData1, $userData2);
    }
    
    public function test_cache_invalidation_on_user_update()
    {
        $user = User::factory()->create(['first_name' => 'John']);
        
        // Cache user data
        $cachedData = app(UserService::class)->getUserWithCache($user->id);
        $this->assertEquals('John', $cachedData['first_name']);
        
        // Update user
        $user->update(['first_name' => 'Jane']);
        
        // Cache should be invalidated
        $updatedData = app(UserService::class)->getUserWithCache($user->id);
        $this->assertEquals('Jane', $updatedData['first_name']);
    }
}
```

---

## 🎭 End-to-End Tests

### Browser Testing with Laravel Dusk

#### Installation
```bash
composer require --dev laravel/dusk
php artisan dusk:install
```

#### E2E Login Test
```php
// tests/Browser/LoginTest.php
class LoginTest extends DuskTestCase
{
    use DatabaseMigrations;
    
    public function test_user_can_login_through_browser()
    {
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password'),
        ]);
        
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->type('username', 'testuser')
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/dashboard')
                ->assertSee('Dashboard')
                ->assertSee('Welcome, testuser');
        });
    }
    
    public function test_sso_login_flow()
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) {
            $browser->visit('/sso/login?service=sahbandar')
                ->type('username', $user->username)
                ->type('password', 'password')
                ->press('Login')
                ->waitForLocation('/sso/authorize')
                ->assertSee('Authorize Sahbandar')
                ->press('Allow')
                ->waitUntilMissing('.loading')
                ->assertUrlContains('sahbandar.pipp.kkp.go.id');
        });
    }
}
```

### API E2E Tests

#### Complete User Journey Test
```php
// tests/Feature/E2E/UserJourneyTest.php
class UserJourneyTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_complete_user_authentication_journey()
    {
        // 1. Register new user
        $response = $this->postJson('/api/auth/register', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'first_name' => 'New',
            'last_name' => 'User',
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
        ]);
        
        $response->assertStatus(201);
        $userId = $response->json('data.user.id');
        
        // 2. Login with new user
        $loginResponse = $this->postJson('/api/auth/login', [
            'username' => 'newuser',
            'password' => 'SecurePassword123!',
        ]);
        
        $loginResponse->assertStatus(200);
        $token = $loginResponse->json('data.token');
        
        // 3. Access protected resource
        $profileResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/profile');
        
        $profileResponse->assertStatus(200)
            ->assertJson([
                'data' => [
                    'username' => 'newuser',
                    'email' => 'newuser@example.com',
                ]
            ]);
        
        // 4. Update profile
        $updateResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
        
        $updateResponse->assertStatus(200);
        
        // 5. Logout
        $logoutResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');
        
        $logoutResponse->assertStatus(200);
        
        // 6. Verify token is invalidated
        $protectedResponse = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/profile');
        
        $protectedResponse->assertStatus(401);
    }
}
```

---

## 📊 Test Coverage & Reporting

### Code Coverage Configuration

#### Generate Coverage Report
```bash
# Run tests with coverage
php artisan test --coverage

# Generate HTML coverage report
php artisan test --coverage-html coverage-report

# Generate Clover XML for CI/CD
php artisan test --coverage-clover coverage.xml
```

### Continuous Integration

#### GitHub Actions Workflow
```yaml
# .github/workflows/tests.yml
name: Tests

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: sso_pipp_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
      
      redis:
        image: redis:6.0
        ports:
          - 6379:6379
        options: --health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, dom, fileinfo, mysql, redis
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install --prefer-dist --no-progress
    
    - name: Copy environment file
      run: cp .env.testing .env
    
    - name: Generate application key
      run: php artisan key:generate
    
    - name: Run migrations
      run: php artisan migrate --force
    
    - name: Run tests
      run: php artisan test --coverage-clover coverage.xml
    
    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      with:
        file: ./coverage.xml
```

### Test Metrics

#### Quality Gates
```php
// tests/TestCase.php
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set quality gates
        $this->setUpQualityGates();
    }
    
    private function setUpQualityGates()
    {
        // Minimum code coverage: 80%
        if (env('CHECK_COVERAGE', false)) {
            $coverage = $this->getCoveragePercentage();
            $this->assertGreaterThanOrEqual(80, $coverage, 
                "Code coverage is {$coverage}%, minimum required is 80%");
        }
        
        // Maximum response time: 500ms for API endpoints
        $this->maxResponseTime = 500;
        
        // Maximum memory usage: 128MB
        $this->maxMemoryUsage = 128 * 1024 * 1024;
    }
}
```

---

## 🚀 Running Tests

### Local Development

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
php artisan test --testsuite=Security

# Run specific test file
php artisan test tests/Feature/Auth/LoginTest.php

# Run with coverage
php artisan test --coverage

# Run in parallel
php artisan test --parallel

# Run with specific filter
php artisan test --filter=test_user_can_login
```

### Production Testing

```bash
# Run smoke tests in production
php artisan test --testsuite=Smoke --env=production

# Run health checks
php artisan health:check

# Run performance benchmarks
php artisan benchmark:run
```

### Test Data Management

```bash
# Refresh test database
php artisan migrate:fresh --seed --env=testing

# Create test data
php artisan db:seed --class=TestSeeder --env=testing

# Clean up test data
php artisan test:cleanup
```

---

**Panduan testing ini memastikan kualitas dan keandalan sistem SSO PIPP melalui pengujian yang komprehensif di semua lapisan aplikasi.**