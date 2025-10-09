# Security Guide - SSO PIPP

Panduan keamanan komprehensif untuk sistem Single Sign-On Platform Informasi Pelabuhan Perikanan (PIPP).

## 🔒 Arsitektur Keamanan

### Security Layers

1. **Network Security**: Firewall, VPN, SSL/TLS
2. **Application Security**: Authentication, Authorization, Input validation
3. **Data Security**: Encryption, Hashing, Secure storage
4. **Infrastructure Security**: Server hardening, Monitoring
5. **Operational Security**: Audit logging, Incident response

### Security Principles

- **Defense in Depth**: Multiple layers of security
- **Least Privilege**: Minimal access rights
- **Zero Trust**: Never trust, always verify
- **Security by Design**: Built-in security from the start
- **Continuous Monitoring**: Real-time threat detection

---

## 🔐 Authentication & Authorization

### JWT Token Security

#### Token Configuration
```env
# Strong JWT secret (minimum 256 bits)
JWT_SECRET=your_very_long_and_random_jwt_secret_key_here_minimum_32_characters

# Short token lifetime for security
JWT_TTL=60  # 1 hour

# Reasonable refresh token lifetime
JWT_REFRESH_TTL=20160  # 2 weeks

# Secure algorithm
JWT_ALGO=HS256
```

#### Token Validation
```php
// app/Http/Middleware/JWTAuthMiddleware.php
class JWTAuthMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            // Validate token
            $token = JWTAuth::parseToken();
            $user = $token->authenticate();
            
            // Check token blacklist
            if ($this->isTokenBlacklisted($token)) {
                throw new TokenBlacklistedException();
            }
            
            // Check user status
            if (!$user || $user->status !== 'active') {
                throw new UserNotActiveException();
            }
            
            // Log successful authentication
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'token_validated',
                'service' => 'sso',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            
        } catch (Exception $e) {
            // Log failed authentication
            AuditLog::create([
                'action' => 'token_validation_failed',
                'service' => 'sso',
                'description' => $e->getMessage(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'severity' => 'high',
            ]);
            
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        return $next($request);
    }
}
```

### Password Security

#### Password Policy
```php
// app/Rules/StrongPassword.php
class StrongPassword implements Rule
{
    public function passes($attribute, $value)
    {
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $value);
    }
    
    public function message()
    {
        return 'Password must be at least 8 characters and contain uppercase, lowercase, number, and special character.';
    }
}
```

#### Password Hashing
```php
// Use bcrypt with high cost
$hashedPassword = Hash::make($password, [
    'rounds' => 12, // High cost for security
]);

// Verify password
if (Hash::check($password, $hashedPassword)) {
    // Password is correct
}
```

### Multi-Factor Authentication (MFA)

#### TOTP Implementation
```php
// app/Services/TOTPService.php
class TOTPService
{
    public function generateSecret(): string
    {
        return Base32::encodeUpper(random_bytes(20));
    }
    
    public function generateQRCode(User $user, string $secret): string
    {
        $issuer = config('app.name');
        $label = $user->email;
        
        $url = "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}";
        
        return QrCode::format('svg')->size(200)->generate($url);
    }
    
    public function verifyCode(string $secret, string $code): bool
    {
        $totp = new TOTP($secret);
        return $totp->verify($code, null, 1); // Allow 1 window tolerance
    }
}
```

### Session Security

#### Secure Session Configuration
```php
// config/session.php
return [
    'lifetime' => 120, // 2 hours
    'expire_on_close' => true,
    'encrypt' => true,
    'files' => storage_path('framework/sessions'),
    'connection' => 'redis',
    'table' => 'sessions',
    'store' => 'redis',
    'lottery' => [2, 100],
    'cookie' => 'sso_session',
    'path' => '/',
    'domain' => '.pipp.kkp.go.id',
    'secure' => true, // HTTPS only
    'http_only' => true, // No JavaScript access
    'same_site' => 'strict', // CSRF protection
];
```

---

## 🛡️ Input Validation & Sanitization

### Request Validation

#### Form Requests
```php
// app/Http/Requests/LoginRequest.php
class LoginRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    
    public function rules()
    {
        return [
            'username' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9._-]+$/', // Alphanumeric and safe chars only
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
            ],
            'remember' => 'boolean',
        ];
    }
    
    protected function prepareForValidation()
    {
        $this->merge([
            'username' => strtolower(trim($this->username)),
            'remember' => $this->boolean('remember'),
        ]);
    }
}
```

### SQL Injection Prevention

#### Eloquent ORM Usage
```php
// GOOD: Using Eloquent ORM (automatically escaped)
$users = User::where('username', $username)->get();

// GOOD: Using parameter binding
$users = DB::select('SELECT * FROM users WHERE username = ?', [$username]);

// BAD: Direct string concatenation (vulnerable to SQL injection)
$users = DB::select("SELECT * FROM users WHERE username = '$username'");
```

### XSS Prevention

#### Output Escaping
```php
// Blade templates automatically escape output
{{ $user->name }} // Automatically escaped

// Raw output (use with caution)
{!! $trustedHtml !!} // Not escaped

// Manual escaping
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
```

#### Content Security Policy
```php
// app/Http/Middleware/SecurityHeadersMiddleware.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('Content-Security-Policy', 
        "default-src 'self'; " .
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'; " .
        "style-src 'self' 'unsafe-inline'; " .
        "img-src 'self' data: https:; " .
        "font-src 'self' data:; " .
        "connect-src 'self'; " .
        "frame-ancestors 'none';"
    );
    
    return $response;
}
```

---

## 🔍 Security Monitoring & Logging

### Audit Logging

#### Comprehensive Audit Trail
```php
// app/Models/AuditLog.php
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'service',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'severity',
        'additional_data',
    ];
    
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'additional_data' => 'array',
    ];
    
    public static function logActivity(array $data)
    {
        // Sanitize sensitive data
        $data = self::sanitizeSensitiveData($data);
        
        // Add metadata
        $data['ip_address'] = request()->ip();
        $data['user_agent'] = request()->userAgent();
        $data['created_at'] = now();
        
        // Store in database
        self::create($data);
        
        // Also log to file for backup
        Log::channel('audit')->info('Audit Log', $data);
    }
    
    private static function sanitizeSensitiveData(array $data): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key'];
        
        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }
        
        return $data;
    }
}
```

### Security Event Detection

#### Suspicious Activity Detection
```php
// app/Services/SecurityMonitorService.php
class SecurityMonitorService
{
    public function detectSuspiciousLogin(User $user, Request $request)
    {
        $suspiciousFactors = [];
        
        // Check for unusual location
        if ($this->isUnusualLocation($user, $request->ip())) {
            $suspiciousFactors[] = 'unusual_location';
        }
        
        // Check for unusual time
        if ($this->isUnusualTime($user)) {
            $suspiciousFactors[] = 'unusual_time';
        }
        
        // Check for multiple failed attempts
        if ($this->hasRecentFailedAttempts($user)) {
            $suspiciousFactors[] = 'recent_failed_attempts';
        }
        
        // Check for unusual user agent
        if ($this->isUnusualUserAgent($user, $request->userAgent())) {
            $suspiciousFactors[] = 'unusual_user_agent';
        }
        
        if (!empty($suspiciousFactors)) {
            $this->handleSuspiciousActivity($user, $suspiciousFactors, $request);
        }
    }
    
    private function handleSuspiciousActivity(User $user, array $factors, Request $request)
    {
        // Log security event
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'suspicious_login_detected',
            'service' => 'sso',
            'description' => 'Suspicious login activity detected',
            'additional_data' => [
                'factors' => $factors,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
            'severity' => 'high',
        ]);
        
        // Send notification to user
        $user->notify(new SuspiciousActivityNotification($factors));
        
        // Send alert to security team
        $this->alertSecurityTeam($user, $factors);
        
        // Consider additional security measures
        if (count($factors) >= 3) {
            $this->lockUserAccount($user);
        }
    }
}
```

### Rate Limiting

#### Advanced Rate Limiting
```php
// app/Http/Middleware/AdvancedRateLimitMiddleware.php
class AdvancedRateLimitMiddleware
{
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1)
    {
        $key = $this->resolveRequestSignature($request);
        
        // Check if rate limit exceeded
        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            // Log rate limit violation
            AuditLog::create([
                'action' => 'rate_limit_exceeded',
                'service' => 'sso',
                'description' => "Rate limit exceeded for {$request->path()}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'severity' => 'medium',
                'additional_data' => [
                    'max_attempts' => $maxAttempts,
                    'decay_minutes' => $decayMinutes,
                    'endpoint' => $request->path(),
                ],
            ]);
            
            return $this->buildRateLimitResponse($key, $maxAttempts);
        }
        
        $this->limiter->hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }
    
    protected function resolveRequestSignature($request)
    {
        // Use different keys for different types of requests
        if ($request->user()) {
            return 'user:' . $request->user()->id . ':' . $request->path();
        }
        
        return 'ip:' . $request->ip() . ':' . $request->path();
    }
}
```

---

## 🚨 Incident Response

### Security Incident Classification

#### Severity Levels
1. **Critical**: System compromise, data breach
2. **High**: Unauthorized access, privilege escalation
3. **Medium**: Suspicious activity, failed attacks
4. **Low**: Policy violations, minor security events

### Incident Response Procedures

#### Automated Response
```php
// app/Services/IncidentResponseService.php
class IncidentResponseService
{
    public function handleSecurityIncident(string $type, array $data, string $severity = 'medium')
    {
        // Create incident record
        $incident = SecurityIncident::create([
            'type' => $type,
            'severity' => $severity,
            'data' => $data,
            'status' => 'open',
            'detected_at' => now(),
        ]);
        
        // Automated response based on severity
        switch ($severity) {
            case 'critical':
                $this->handleCriticalIncident($incident);
                break;
            case 'high':
                $this->handleHighIncident($incident);
                break;
            case 'medium':
                $this->handleMediumIncident($incident);
                break;
            case 'low':
                $this->handleLowIncident($incident);
                break;
        }
        
        return $incident;
    }
    
    private function handleCriticalIncident(SecurityIncident $incident)
    {
        // Immediate actions for critical incidents
        
        // 1. Alert security team immediately
        $this->alertSecurityTeam($incident, true);
        
        // 2. Lock affected accounts
        if (isset($incident->data['user_id'])) {
            $this->lockUserAccount($incident->data['user_id']);
        }
        
        // 3. Invalidate all sessions for affected users
        $this->invalidateUserSessions($incident->data['user_id'] ?? null);
        
        // 4. Block suspicious IP addresses
        if (isset($incident->data['ip_address'])) {
            $this->blockIPAddress($incident->data['ip_address']);
        }
        
        // 5. Enable enhanced monitoring
        $this->enableEnhancedMonitoring();
    }
}
```

### Breach Notification

#### Data Breach Response
```php
// app/Services/BreachNotificationService.php
class BreachNotificationService
{
    public function handleDataBreach(array $affectedData, string $breachType)
    {
        // Create breach record
        $breach = DataBreach::create([
            'type' => $breachType,
            'affected_records' => count($affectedData),
            'detected_at' => now(),
            'status' => 'investigating',
        ]);
        
        // Immediate containment
        $this->containBreach($breach);
        
        // Notify affected users
        $this->notifyAffectedUsers($affectedData);
        
        // Notify authorities (if required)
        $this->notifyAuthorities($breach);
        
        // Document incident
        $this->documentIncident($breach);
        
        return $breach;
    }
    
    private function notifyAffectedUsers(array $affectedData)
    {
        foreach ($affectedData as $userData) {
            $user = User::find($userData['user_id']);
            if ($user) {
                $user->notify(new DataBreachNotification($userData));
            }
        }
    }
}
```

---

## 🔧 Security Configuration

### Environment Security

#### Production Environment Variables
```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your_32_character_random_string_here

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sso_pipp
DB_USERNAME=sso_user
DB_PASSWORD=very_secure_database_password_here

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=very_secure_redis_password_here
REDIS_PORT=6379

# JWT
JWT_SECRET=your_very_long_and_random_jwt_secret_key_here_minimum_32_characters
JWT_TTL=60
JWT_REFRESH_TTL=20160

# Security
SESSION_SECURE_COOKIES=true
SESSION_SAME_SITE=strict
SANCTUM_STATEFUL_DOMAINS=sso.pipp.kkp.go.id

# Rate Limiting
RATE_LIMIT_LOGIN=5
RATE_LIMIT_API=60
RATE_LIMIT_SSO=100

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=error
```

### Server Security Configuration

#### Nginx Security Configuration
```nginx
# Security headers
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

# Hide server information
server_tokens off;
more_clear_headers Server;

# Limit request size
client_max_body_size 20M;

# Timeout settings
client_body_timeout 12;
client_header_timeout 12;
keepalive_timeout 15;
send_timeout 10;

# Rate limiting
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;

# Block common attack patterns
location ~* \.(php|jsp|cgi)$ {
    if ($request_uri !~ "^/index\.php") {
        return 444;
    }
}

# Block access to sensitive files
location ~ /\. {
    deny all;
    access_log off;
    log_not_found off;
}

location ~ ~$ {
    deny all;
    access_log off;
    log_not_found off;
}
```

#### PHP Security Configuration
```ini
; php.ini security settings
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; File upload security
file_uploads = On
upload_max_filesize = 20M
max_file_uploads = 20
upload_tmp_dir = /tmp

; Session security
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = "Strict"
session.use_strict_mode = 1
session.use_only_cookies = 1

; Disable dangerous functions
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

---

## 🔐 Encryption & Data Protection

### Data Encryption

#### Database Encryption
```php
// app/Models/User.php
class User extends Authenticatable
{
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone' => 'encrypted', // Encrypt sensitive data
        'nip' => 'encrypted',
    ];
    
    // Custom encryption for specific fields
    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = encrypt($value);
    }
    
    public function getPhoneAttribute($value)
    {
        return decrypt($value);
    }
}
```

#### File Encryption
```php
// app/Services/FileEncryptionService.php
class FileEncryptionService
{
    public function encryptFile(string $filePath): string
    {
        $content = file_get_contents($filePath);
        $encrypted = encrypt($content);
        
        $encryptedPath = $filePath . '.encrypted';
        file_put_contents($encryptedPath, $encrypted);
        
        // Remove original file
        unlink($filePath);
        
        return $encryptedPath;
    }
    
    public function decryptFile(string $encryptedPath): string
    {
        $encrypted = file_get_contents($encryptedPath);
        $decrypted = decrypt($encrypted);
        
        $originalPath = str_replace('.encrypted', '', $encryptedPath);
        file_put_contents($originalPath, $decrypted);
        
        return $originalPath;
    }
}
```

### Key Management

#### Encryption Key Rotation
```php
// app/Console/Commands/RotateEncryptionKeys.php
class RotateEncryptionKeys extends Command
{
    protected $signature = 'keys:rotate';
    protected $description = 'Rotate encryption keys';
    
    public function handle()
    {
        // Generate new key
        $newKey = base64_encode(random_bytes(32));
        
        // Re-encrypt data with new key
        $this->reEncryptData($newKey);
        
        // Update environment file
        $this->updateEnvironmentFile($newKey);
        
        // Clear caches
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        
        $this->info('Encryption keys rotated successfully');
    }
    
    private function reEncryptData(string $newKey)
    {
        // Re-encrypt sensitive user data
        User::chunk(100, function ($users) use ($newKey) {
            foreach ($users as $user) {
                // Decrypt with old key and encrypt with new key
                $phone = decrypt($user->getRawOriginal('phone'));
                $nip = decrypt($user->getRawOriginal('nip'));
                
                $user->phone = $phone;
                $user->nip = $nip;
                $user->save();
            }
        });
    }
}
```

---

## 🛡️ Security Testing

### Automated Security Testing

#### Security Test Suite
```php
// tests/Feature/SecurityTest.php
class SecurityTest extends TestCase
{
    public function test_sql_injection_protection()
    {
        $maliciousInput = "'; DROP TABLE users; --";
        
        $response = $this->postJson('/api/auth/login', [
            'username' => $maliciousInput,
            'password' => 'password',
        ]);
        
        $response->assertStatus(422);
        
        // Verify users table still exists
        $this->assertDatabaseHas('users', ['id' => 1]);
    }
    
    public function test_xss_protection()
    {
        $maliciousScript = '<script>alert("XSS")</script>';
        
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->putJson('/api/profile', [
                'first_name' => $maliciousScript,
            ]);
        
        $response->assertStatus(422);
    }
    
    public function test_csrf_protection()
    {
        $response = $this->postJson('/api/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ], [
            'X-CSRF-TOKEN' => 'invalid-token',
        ]);
        
        $response->assertStatus(419);
    }
    
    public function test_rate_limiting()
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'username' => 'admin',
                'password' => 'wrong-password',
            ]);
        }
        
        $response->assertStatus(429);
    }
}
```

### Penetration Testing

#### Security Checklist
- [ ] SQL Injection testing
- [ ] XSS vulnerability testing
- [ ] CSRF protection testing
- [ ] Authentication bypass testing
- [ ] Authorization testing
- [ ] Session management testing
- [ ] Input validation testing
- [ ] File upload security testing
- [ ] API security testing
- [ ] Infrastructure security testing

### Vulnerability Scanning

#### Automated Vulnerability Scanning
```bash
#!/bin/bash
# security-scan.sh

echo "Starting security scan..."

# Check for known vulnerabilities in dependencies
composer audit

# Static code analysis
./vendor/bin/phpstan analyse --level=8 app/

# Security-focused static analysis
./vendor/bin/psalm --show-info=false

# Check for common security issues
./vendor/bin/security-checker security:check

echo "Security scan completed"
```

---

## 📋 Security Compliance

### Compliance Standards

#### ISO 27001 Compliance
- Information Security Management System (ISMS)
- Risk assessment and treatment
- Security controls implementation
- Continuous monitoring and improvement

#### GDPR Compliance
- Data protection by design and by default
- User consent management
- Right to be forgotten
- Data breach notification

### Security Policies

#### Password Policy
```php
// config/security.php
return [
    'password_policy' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special_chars' => true,
        'max_age_days' => 90,
        'history_count' => 5,
        'lockout_attempts' => 5,
        'lockout_duration' => 30, // minutes
    ],
    
    'session_policy' => [
        'timeout_minutes' => 120,
        'max_concurrent_sessions' => 3,
        'require_https' => true,
        'secure_cookies' => true,
    ],
    
    'audit_policy' => [
        'log_all_access' => true,
        'log_failed_attempts' => true,
        'retention_days' => 365,
        'real_time_alerts' => true,
    ],
];
```

### Data Classification

#### Data Sensitivity Levels
1. **Public**: General information
2. **Internal**: Internal use only
3. **Confidential**: Sensitive business information
4. **Restricted**: Highly sensitive data

#### Data Handling Requirements
```php
// app/Models/Traits/DataClassification.php
trait DataClassification
{
    protected $dataClassification = [
        'public' => ['username', 'first_name', 'last_name'],
        'internal' => ['email', 'department', 'position'],
        'confidential' => ['phone', 'office_location'],
        'restricted' => ['nip', 'password'],
    ];
    
    public function getDataClassification(string $field): string
    {
        foreach ($this->dataClassification as $level => $fields) {
            if (in_array($field, $fields)) {
                return $level;
            }
        }
        
        return 'internal'; // Default classification
    }
    
    public function canAccess(string $field, User $user): bool
    {
        $classification = $this->getDataClassification($field);
        
        return match ($classification) {
            'public' => true,
            'internal' => $user->hasRole(['employee', 'admin']),
            'confidential' => $user->hasRole(['manager', 'admin']),
            'restricted' => $user->hasRole(['admin']),
            default => false,
        };
    }
}
```

---

## 🚨 Security Alerts & Notifications

### Real-time Security Alerts

#### Security Alert System
```php
// app/Services/SecurityAlertService.php
class SecurityAlertService
{
    public function sendSecurityAlert(string $type, array $data, string $severity = 'medium')
    {
        $alert = SecurityAlert::create([
            'type' => $type,
            'data' => $data,
            'severity' => $severity,
            'status' => 'active',
        ]);
        
        // Send to appropriate channels based on severity
        switch ($severity) {
            case 'critical':
                $this->sendToAllChannels($alert);
                break;
            case 'high':
                $this->sendToSecurityTeam($alert);
                $this->sendToManagement($alert);
                break;
            case 'medium':
                $this->sendToSecurityTeam($alert);
                break;
            case 'low':
                $this->logAlert($alert);
                break;
        }
        
        return $alert;
    }
    
    private function sendToAllChannels(SecurityAlert $alert)
    {
        // Email
        Mail::to(config('security.alert_emails'))->send(new SecurityAlertMail($alert));
        
        // SMS
        $this->sendSMS($alert);
        
        // Slack
        $this->sendToSlack($alert);
        
        // Push notification
        $this->sendPushNotification($alert);
    }
}
```

### Security Dashboard

#### Real-time Security Monitoring
```php
// app/Http/Controllers/SecurityDashboardController.php
class SecurityDashboardController extends Controller
{
    public function index()
    {
        $data = [
            'active_threats' => $this->getActiveThreats(),
            'recent_incidents' => $this->getRecentIncidents(),
            'security_metrics' => $this->getSecurityMetrics(),
            'system_health' => $this->getSystemHealth(),
        ];
        
        return view('security.dashboard', $data);
    }
    
    private function getSecurityMetrics()
    {
        return [
            'failed_logins_24h' => AuditLog::where('action', 'login_failed')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'suspicious_activities' => AuditLog::where('severity', 'high')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'blocked_ips' => BlockedIP::where('is_active', true)->count(),
            'active_sessions' => SSOSession::where('is_active', true)->count(),
        ];
    }
}
```

---

**Panduan keamanan ini harus diimplementasikan secara menyeluruh dan diperbarui secara berkala sesuai dengan perkembangan ancaman keamanan terbaru.**