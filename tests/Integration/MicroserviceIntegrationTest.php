<?php

namespace Tests\Integration;

use App\Models\User;
use App\Services\Microservices\SahbandarService;
use App\Services\Microservices\SPBService;
use App\Services\Microservices\SHTIService;
use App\Services\Microservices\EPITService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class MicroserviceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function sahbandar_service_can_fetch_user_profile()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Mock HTTP response from Sahbandar service
        Http::fake([
            'sahbandar.example.com/api/profile/*' => Http::response([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'profile' => [
                        'company_name' => 'PT. Shipping Indonesia',
                        'license_number' => 'SHB-2024-001',
                        'status' => 'active',
                        'vessels_count' => 5
                    ]
                ]
            ], 200)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/services/sahbandar/profile/{$user->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'profile' => [
                            'company_name',
                            'license_number',
                            'status',
                            'vessels_count'
                        ]
                    ]
                ]);

        // Verify HTTP request was made
        Http::assertSent(function ($request) use ($user) {
            return $request->url() === "https://sahbandar.example.com/api/profile/{$user->id}" &&
                   $request->hasHeader('Authorization') &&
                   $request->hasHeader('X-API-Key');
        });
    }

    /** @test */
    public function sahbandar_service_handles_api_errors_gracefully()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Mock HTTP error response
        Http::fake([
            'sahbandar.example.com/api/profile/*' => Http::response([
                'success' => false,
                'message' => 'User not found'
            ], 404)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/services/sahbandar/profile/{$user->id}");

        $response->assertStatus(404)
                ->assertJson([
                    'success' => false,
                    'message' => 'User not found in Sahbandar system'
                ]);
    }

    /** @test */
    public function spb_service_can_fetch_applications()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Mock HTTP response from SPB service
        Http::fake([
            'spb.example.com/api/applications' => Http::response([
                'success' => true,
                'data' => [
                    'applications' => [
                        [
                            'id' => 1,
                            'type' => 'fishing_license',
                            'status' => 'approved',
                            'submitted_at' => '2024-01-15T10:00:00Z'
                        ],
                        [
                            'id' => 2,
                            'type' => 'vessel_registration',
                            'status' => 'pending',
                            'submitted_at' => '2024-01-16T14:30:00Z'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/services/spb/applications');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'applications' => [
                            '*' => [
                                'id',
                                'type',
                                'status',
                                'submitted_at'
                            ]
                        ]
                    ]
                ]);

        // Verify correct API endpoint was called
        Http::assertSent(function ($request) {
            return $request->url() === 'https://spb.example.com/api/applications' &&
                   $request->method() === 'GET';
        });
    }

    /** @test */
    public function shti_service_can_submit_catch_report()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $catchData = [
            'vessel_id' => 'VSL-001',
            'catch_date' => '2024-01-15',
            'location' => 'Fishing Zone A',
            'species' => [
                ['name' => 'Tuna', 'weight' => 150.5],
                ['name' => 'Mackerel', 'weight' => 75.2]
            ]
        ];

        // Mock HTTP response from SHTI service
        Http::fake([
            'shti.example.com/api/catch-reports' => Http::response([
                'success' => true,
                'data' => [
                    'report_id' => 'CR-2024-001',
                    'status' => 'submitted',
                    'submitted_at' => '2024-01-15T15:30:00Z'
                ]
            ], 201)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/services/shti/catch-reports', $catchData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'report_id',
                        'status',
                        'submitted_at'
                    ]
                ]);

        // Verify POST request was made with correct data
        Http::assertSent(function ($request) use ($catchData) {
            return $request->url() === 'https://shti.example.com/api/catch-reports' &&
                   $request->method() === 'POST' &&
                   $request->data() === $catchData;
        });
    }

    /** @test */
    public function epit_service_can_check_berth_availability()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Mock HTTP response from EPIT service
        Http::fake([
            'epit.example.com/api/berths/availability*' => Http::response([
                'success' => true,
                'data' => [
                    'available_berths' => [
                        [
                            'berth_id' => 'B001',
                            'port' => 'Tanjung Priok',
                            'type' => 'container',
                            'available_from' => '2024-01-20T08:00:00Z',
                            'available_until' => '2024-01-25T18:00:00Z'
                        ],
                        [
                            'berth_id' => 'B002',
                            'port' => 'Tanjung Priok',
                            'type' => 'general_cargo',
                            'available_from' => '2024-01-18T06:00:00Z',
                            'available_until' => '2024-01-22T20:00:00Z'
                        ]
                    ]
                ]
            ], 200)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/services/epit/berths/availability?port=tanjung_priok&date=2024-01-20');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'available_berths' => [
                            '*' => [
                                'berth_id',
                                'port',
                                'type',
                                'available_from',
                                'available_until'
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function microservice_responses_are_cached()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Mock HTTP response
        Http::fake([
            'sahbandar.example.com/api/profile/*' => Http::response([
                'success' => true,
                'data' => ['profile' => ['company_name' => 'Test Company']]
            ], 200)
        ]);

        // First request
        $response1 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/services/sahbandar/profile/{$user->id}");

        $response1->assertStatus(200);

        // Second request should use cache
        $response2 = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/services/sahbandar/profile/{$user->id}");

        $response2->assertStatus(200);

        // Verify only one HTTP request was made (second was cached)
        Http::assertSentCount(1);

        // Verify cache key exists
        $cacheKey = "sahbandar_profile_{$user->id}";
        $this->assertTrue(Cache::has($cacheKey));
    }

    /** @test */
    public function microservice_timeout_is_handled()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Mock timeout response
        Http::fake([
            'sahbandar.example.com/api/profile/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timeout');
            }
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/services/sahbandar/profile/{$user->id}");

        $response->assertStatus(503)
                ->assertJson([
                    'success' => false,
                    'message' => 'Service temporarily unavailable'
                ]);
    }

    /** @test */
    public function microservice_retry_mechanism_works()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Mock responses: first two fail, third succeeds
        Http::fake([
            'sahbandar.example.com/api/profile/*' => Http::sequence()
                ->push(['error' => 'Server error'], 500)
                ->push(['error' => 'Server error'], 500)
                ->push(['success' => true, 'data' => ['profile' => ['company_name' => 'Test Company']]], 200)
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/services/sahbandar/profile/{$user->id}");

        $response->assertStatus(200);

        // Verify 3 requests were made (2 retries + 1 success)
        Http::assertSentCount(3);
    }

    /** @test */
    public function microservice_health_check_works()
    {
        // Mock health check responses
        Http::fake([
            'sahbandar.example.com/health' => Http::response(['status' => 'healthy'], 200),
            'spb.example.com/health' => Http::response(['status' => 'healthy'], 200),
            'shti.example.com/health' => Http::response(['status' => 'degraded'], 200),
            'epit.example.com/health' => Http::response(['error' => 'Service unavailable'], 503)
        ]);

        $response = $this->getJson('/api/services/health');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'services' => [
                            'sahbandar' => ['status', 'response_time'],
                            'spb' => ['status', 'response_time'],
                            'shti' => ['status', 'response_time'],
                            'epit' => ['status', 'response_time']
                        ],
                        'overall_status'
                    ]
                ]);

        $services = $response->json('data.services');
        $this->assertEquals('healthy', $services['sahbandar']['status']);
        $this->assertEquals('healthy', $services['spb']['status']);
        $this->assertEquals('degraded', $services['shti']['status']);
        $this->assertEquals('unhealthy', $services['epit']['status']);
    }

    /** @test */
    public function microservice_authentication_headers_are_included()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        Http::fake([
            'sahbandar.example.com/api/profile/*' => Http::response(['success' => true], 200)
        ]);

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/services/sahbandar/profile/{$user->id}");

        // Verify authentication headers were included
        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization') &&
                   $request->hasHeader('X-API-Key') &&
                   $request->hasHeader('X-Service-Name', 'sso-pipp') &&
                   $request->hasHeader('Content-Type', 'application/json');
        });
    }

    /** @test */
    public function microservice_rate_limiting_is_respected()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        Http::fake([
            'sahbandar.example.com/api/profile/*' => Http::response(['success' => true], 200)
        ]);

        // Make multiple rapid requests
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson("/api/services/sahbandar/profile/{$user->id}");

            if ($i < 5) {
                $response->assertStatus(200);
            } else {
                // Should be rate limited after 5 requests
                $response->assertStatus(429);
                break;
            }
        }
    }

    /** @test */
    public function microservice_circuit_breaker_opens_on_failures()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Mock consecutive failures
        Http::fake([
            'sahbandar.example.com/api/profile/*' => Http::response(['error' => 'Server error'], 500)
        ]);

        // Make multiple requests to trigger circuit breaker
        for ($i = 0; $i < 6; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->getJson("/api/services/sahbandar/profile/{$user->id}");

            if ($i < 5) {
                $response->assertStatus(500);
            } else {
                // Circuit breaker should open and return 503
                $response->assertStatus(503)
                        ->assertJson([
                            'success' => false,
                            'message' => 'Service circuit breaker is open'
                        ]);
            }
        }
    }
}