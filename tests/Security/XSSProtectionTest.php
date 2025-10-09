<?php

namespace Tests\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class XSSProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles and permissions
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function registration_sanitizes_malicious_input()
    {
        $maliciousData = [
            'name' => '<script>alert("XSS")</script>John Doe',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'nip' => '123456789',
            'jabatan' => '<img src=x onerror=alert("XSS")>Staff',
            'unit_kerja' => 'IT Department<script>document.cookie="stolen"</script>'
        ];

        $response = $this->postJson('/api/auth/register', $maliciousData);

        $response->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        
        // Check that malicious scripts are sanitized
        $this->assertStringNotContainsString('<script>', $user->name);
        $this->assertStringNotContainsString('alert', $user->name);
        $this->assertStringNotContainsString('<img', $user->jabatan);
        $this->assertStringNotContainsString('onerror', $user->jabatan);
        $this->assertStringNotContainsString('<script>', $user->unit_kerja);
        $this->assertStringNotContainsString('document.cookie', $user->unit_kerja);
        
        // But legitimate content should remain
        $this->assertStringContainsString('John Doe', $user->name);
        $this->assertStringContainsString('Staff', $user->jabatan);
        $this->assertStringContainsString('IT Department', $user->unit_kerja);
    }

    /** @test */
    public function profile_update_sanitizes_malicious_input()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $maliciousData = [
            'name' => 'John<script>alert("XSS")</script>Doe',
            'jabatan' => 'Manager<iframe src="javascript:alert(1)">',
            'unit_kerja' => 'HR<svg onload=alert("XSS")>'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $maliciousData);

        $response->assertStatus(200);

        $user->refresh();
        
        // Check that malicious scripts are sanitized
        $this->assertStringNotContainsString('<script>', $user->name);
        $this->assertStringNotContainsString('<iframe>', $user->jabatan);
        $this->assertStringNotContainsString('<svg', $user->unit_kerja);
        $this->assertStringNotContainsString('onload', $user->unit_kerja);
        $this->assertStringNotContainsString('javascript:', $user->jabatan);
    }

    /** @test */
    public function api_responses_escape_html_content()
    {
        $user = User::factory()->create([
            'name' => 'John<script>alert("XSS")</script>Doe',
            'jabatan' => 'Staff<img src=x onerror=alert(1)>',
            'unit_kerja' => 'IT<svg onload=alert("XSS")>Department'
        ]);
        
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200);
        
        $responseContent = $response->getContent();
        
        // Check that HTML is properly escaped in JSON response
        $this->assertStringNotContainsString('<script>', $responseContent);
        $this->assertStringNotContainsString('<img', $responseContent);
        $this->assertStringNotContainsString('<svg', $responseContent);
        $this->assertStringNotContainsString('onerror', $responseContent);
        $this->assertStringNotContainsString('onload', $responseContent);
        
        // Should contain escaped versions or sanitized content
        $userData = $response->json('data.user');
        $this->assertStringNotContainsString('<script>', $userData['name']);
        $this->assertStringNotContainsString('<img', $userData['jabatan']);
        $this->assertStringNotContainsString('<svg', $userData['unit_kerja']);
    }

    /** @test */
    public function search_parameters_are_sanitized()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create some test users
        User::factory()->create(['name' => 'Alice Johnson']);
        User::factory()->create(['name' => 'Bob Smith']);

        $maliciousSearch = '<script>alert("XSS")</script>Alice';

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/users?search=' . urlencode($maliciousSearch));

        // Response should not contain the malicious script
        $responseContent = $response->getContent();
        $this->assertStringNotContainsString('<script>', $responseContent);
        $this->assertStringNotContainsString('alert("XSS")', $responseContent);
    }

    /** @test */
    public function file_upload_names_are_sanitized()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        // Create a test file with malicious name
        $maliciousFileName = 'test<script>alert("XSS")</script>.jpg';
        $file = \Illuminate\Http\Testing\File::create($maliciousFileName, 100);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/upload/avatar', [
            'avatar' => $file
        ]);

        if ($response->status() === 200) {
            $responseData = $response->json();
            
            // Check that filename in response is sanitized
            if (isset($responseData['data']['filename'])) {
                $this->assertStringNotContainsString('<script>', $responseData['data']['filename']);
                $this->assertStringNotContainsString('alert', $responseData['data']['filename']);
            }
        }
    }

    /** @test */
    public function error_messages_escape_user_input()
    {
        $maliciousEmail = 'test<script>alert("XSS")</script>@example.com';

        $response = $this->postJson('/api/auth/login', [
            'email' => $maliciousEmail,
            'password' => 'wrongpassword'
        ]);

        $responseContent = $response->getContent();
        
        // Error messages should not contain unescaped malicious content
        $this->assertStringNotContainsString('<script>', $responseContent);
        $this->assertStringNotContainsString('alert("XSS")', $responseContent);
    }

    /** @test */
    public function audit_log_entries_are_sanitized()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        // Login with malicious user agent
        $maliciousUserAgent = 'Mozilla/5.0<script>alert("XSS")</script>';

        $response = $this->withHeaders([
            'User-Agent' => $maliciousUserAgent
        ])->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);

        $response->assertStatus(200);

        // Check audit log entry
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'login'
        ]);

        $auditLog = \App\Models\AuditLog::where('user_id', $user->id)
                                       ->where('action', 'login')
                                       ->first();

        // User agent should be sanitized in audit log
        $this->assertStringNotContainsString('<script>', $auditLog->user_agent);
        $this->assertStringNotContainsString('alert("XSS")', $auditLog->user_agent);
    }

    /** @test */
    public function content_security_policy_headers_are_present()
    {
        $response = $this->getJson('/api/auth/me');

        // Check for CSP headers that prevent XSS
        $response->assertHeader('Content-Security-Policy');
        
        $cspHeader = $response->headers->get('Content-Security-Policy');
        
        // Should contain directives that prevent inline scripts
        $this->assertStringContainsString("script-src", $cspHeader);
        $this->assertStringContainsString("object-src 'none'", $cspHeader);
    }

    /** @test */
    public function x_xss_protection_header_is_present()
    {
        $response = $this->getJson('/api/auth/me');

        // Check for X-XSS-Protection header
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    /** @test */
    public function x_content_type_options_header_prevents_mime_sniffing()
    {
        $response = $this->getJson('/api/auth/me');

        // Check for X-Content-Type-Options header
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    /** @test */
    public function html_entities_are_properly_encoded()
    {
        $user = User::factory()->create([
            'name' => 'John & Jane <Doe>',
            'jabatan' => 'Manager "Special" Projects',
            'unit_kerja' => "IT Department's Security Team"
        ]);
        
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200);
        
        $userData = $response->json('data.user');
        
        // Check that HTML entities are properly handled
        // The API should return clean data, not HTML-encoded
        $this->assertEquals('John & Jane <Doe>', $userData['name']);
        $this->assertEquals('Manager "Special" Projects', $userData['jabatan']);
        $this->assertEquals("IT Department's Security Team", $userData['unit_kerja']);
    }

    /** @test */
    public function javascript_protocol_urls_are_blocked()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $maliciousData = [
            'website' => 'javascript:alert("XSS")',
            'profile_url' => 'javascript:void(0);alert(document.cookie)'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $maliciousData);

        // Should either reject the request or sanitize the URLs
        if ($response->status() === 200) {
            $user->refresh();
            
            // URLs should be sanitized or removed
            $this->assertStringNotContainsString('javascript:', $user->website ?? '');
            $this->assertStringNotContainsString('javascript:', $user->profile_url ?? '');
        } else {
            // Request should be rejected with validation error
            $response->assertStatus(422);
        }
    }

    /** @test */
    public function data_urls_with_scripts_are_blocked()
    {
        $user = User::factory()->create();
        $token = JWTAuth::fromUser($user);

        $maliciousData = [
            'avatar_url' => 'data:text/html,<script>alert("XSS")</script>'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->putJson('/api/auth/profile', $maliciousData);

        // Should either reject the request or sanitize the URL
        if ($response->status() === 200) {
            $user->refresh();
            
            // Data URL should be sanitized or removed
            $this->assertStringNotContainsString('<script>', $user->avatar_url ?? '');
            $this->assertStringNotContainsString('alert("XSS")', $user->avatar_url ?? '');
        } else {
            // Request should be rejected with validation error
            $response->assertStatus(422);
        }
    }
}