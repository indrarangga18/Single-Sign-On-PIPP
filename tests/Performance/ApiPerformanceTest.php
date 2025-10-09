<?php

namespace Tests\Performance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ApiPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function login_endpoint_responds_within_acceptable_time()
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);

        $startTime = microtime(true);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123'
        ]);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        $response->assertStatus(200);
        
        // Assert response time is under 500ms
        $this->assertLessThan(500, $responseTime, 
            "Login endpoint took {$responseTime}ms, which exceeds 500ms threshold");
    }

    /** @test */
    public function user_profile_endpoint_responds_within_acceptable_time()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $startTime = microtime(true);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user/profile');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);
        
        // Assert response time is under 200ms
        $this->assertLessThan(200, $responseTime, 
            "Profile endpoint took {$responseTime}ms, which exceeds 200ms threshold");
    }

    /** @test */
    public function api_can_handle_concurrent_login_requests()
    {
        $users = User::factory()->count(10)->create([
            'password' => bcrypt('password123'),
            'is_active' => true
        ]);

        $startTime = microtime(true);
        $responses = [];

        // Simulate concurrent requests
        foreach ($users as $user) {
            $responses[] = $this->postJson('/api/auth/login', [
                'email' => $user->email,
                'password' => 'password123'
            ]);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }

        // Total time for 10 concurrent logins should be under 2 seconds
        $this->assertLessThan(2000, $totalTime, 
            "10 concurrent logins took {$totalTime}ms, which exceeds 2000ms threshold");
    }

    /** @test */
    public function database_queries_are_optimized_for_user_listing()
    {
        // Create test data
        User::factory()->count(100)->create();

        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Enable query logging
        DB::enableQueryLog();

        $startTime = microtime(true);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/users?per_page=20');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertStatus(200);

        // Should not exceed 5 queries for pagination
        $this->assertLessThanOrEqual(5, count($queries), 
            'User listing generated ' . count($queries) . ' queries, should be 5 or fewer');

        // Response time should be under 300ms
        $this->assertLessThan(300, $responseTime, 
            "User listing took {$responseTime}ms, which exceeds 300ms threshold");
    }

    /** @test */
    public function cache_improves_repeated_requests_performance()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // First request (no cache)
        $startTime1 = microtime(true);
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user/profile');
        $endTime1 = microtime(true);
        $firstRequestTime = ($endTime1 - $startTime1) * 1000;

        $response1->assertStatus(200);

        // Second request (should use cache)
        $startTime2 = microtime(true);
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/user/profile');
        $endTime2 = microtime(true);
        $secondRequestTime = ($endTime2 - $startTime2) * 1000;

        $response2->assertStatus(200);

        // Second request should be significantly faster (at least 50% faster)
        $this->assertLessThan($firstRequestTime * 0.5, $secondRequestTime, 
            "Cached request ({$secondRequestTime}ms) should be faster than first request ({$firstRequestTime}ms)");
    }

    /** @test */
    public function jwt_token_validation_is_performant()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $responseTimes = [];

        // Test JWT validation performance over multiple requests
        for ($i = 0; $i < 20; $i++) {
            $startTime = microtime(true);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/user/profile');

            $endTime = microtime(true);
            $responseTimes[] = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
        }

        $averageTime = array_sum($responseTimes) / count($responseTimes);
        $maxTime = max($responseTimes);

        // Average JWT validation should be under 100ms
        $this->assertLessThan(100, $averageTime, 
            "Average JWT validation time ({$averageTime}ms) exceeds 100ms threshold");

        // No single request should exceed 200ms
        $this->assertLessThan(200, $maxTime, 
            "Maximum JWT validation time ({$maxTime}ms) exceeds 200ms threshold");
    }

    /** @test */
    public function audit_logging_does_not_significantly_impact_performance()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Measure performance with audit logging enabled
        $startTime = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->putJson('/api/user/profile', [
                'name' => "Updated Name {$i}",
                'phone' => "08123456789{$i}"
            ]);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        // 10 profile updates with audit logging should complete under 2 seconds
        $this->assertLessThan(2000, $totalTime, 
            "10 profile updates with audit logging took {$totalTime}ms, exceeds 2000ms threshold");

        // Verify audit logs were created
        $this->assertDatabaseCount('audit_logs', 10);
    }

    /** @test */
    public function rate_limiting_does_not_add_significant_overhead()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $responseTimes = [];

        // Make requests within rate limit
        for ($i = 0; $i < 5; $i++) {
            $startTime = microtime(true);

            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/user/profile');

            $endTime = microtime(true);
            $responseTimes[] = ($endTime - $startTime) * 1000;

            $response->assertStatus(200);
        }

        $averageTime = array_sum($responseTimes) / count($responseTimes);

        // Rate limiting overhead should not add more than 50ms on average
        $this->assertLessThan(250, $averageTime, 
            "Average response time with rate limiting ({$averageTime}ms) exceeds 250ms threshold");
    }

    /** @test */
    public function memory_usage_stays_within_acceptable_limits()
    {
        $initialMemory = memory_get_usage(true);

        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Perform multiple operations
        for ($i = 0; $i < 50; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/user/profile');
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;
        $memoryIncreaseMB = $memoryIncrease / 1024 / 1024;

        // Memory increase should not exceed 10MB for 50 requests
        $this->assertLessThan(10, $memoryIncreaseMB, 
            "Memory usage increased by {$memoryIncreaseMB}MB, which exceeds 10MB threshold");
    }

    /** @test */
    public function database_connection_pooling_is_efficient()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        DB::enableQueryLog();

        $startTime = microtime(true);

        // Perform multiple database operations
        for ($i = 0; $i < 20; $i++) {
            $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson('/api/users?page=' . ($i + 1));
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // 20 paginated requests should complete under 3 seconds
        $this->assertLessThan(3000, $totalTime, 
            "20 paginated requests took {$totalTime}ms, which exceeds 3000ms threshold");

        // Should not have excessive queries
        $this->assertLessThan(100, count($queries), 
            'Generated ' . count($queries) . ' queries, should be under 100');
    }

    /** @test */
    public function api_response_size_is_optimized()
    {
        $users = User::factory()->count(20)->create();
        $user = $users->first();
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/users?per_page=20');

        $response->assertStatus(200);

        $responseSize = strlen($response->getContent());
        $responseSizeKB = $responseSize / 1024;

        // Response size for 20 users should not exceed 50KB
        $this->assertLessThan(50, $responseSizeKB, 
            "Response size ({$responseSizeKB}KB) exceeds 50KB threshold");

        // Verify response contains expected data structure
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'email',
                    'nip'
                    // Should not include sensitive data like password
                ]
            ],
            'meta' => [
                'current_page',
                'per_page',
                'total'
            ]
        ]);
    }

    /** @test */
    public function search_functionality_is_performant()
    {
        // Create test data with searchable content
        User::factory()->count(100)->create();

        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $startTime = microtime(true);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/users?search=test&per_page=10');

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        $response->assertStatus(200);

        // Search should complete under 400ms
        $this->assertLessThan(400, $responseTime, 
            "Search took {$responseTime}ms, which exceeds 400ms threshold");
    }

    /** @test */
    public function file_upload_performance_is_acceptable()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create a test file (1KB)
        $fileContent = str_repeat('a', 1024);
        $tempFile = tmpfile();
        fwrite($tempFile, $fileContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        $startTime = microtime(true);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->post('/api/user/avatar', [
            'avatar' => new \Illuminate\Http\UploadedFile(
                $tempPath,
                'test-avatar.jpg',
                'image/jpeg',
                null,
                true
            )
        ]);

        $endTime = microtime(true);
        $responseTime = ($endTime - $startTime) * 1000;

        // File upload should complete under 1 second
        $this->assertLessThan(1000, $responseTime, 
            "File upload took {$responseTime}ms, which exceeds 1000ms threshold");

        fclose($tempFile);
    }
}