# Contributing to SSO PIPP

Terima kasih atas minat Anda untuk berkontribusi pada proyek Single Sign-On Platform Informasi Pelabuhan Perikanan (SSO PIPP)! Panduan ini akan membantu Anda memahami cara berkontribusi secara efektif.

## 📋 Daftar Isi

- [Code of Conduct](#code-of-conduct)
- [Cara Berkontribusi](#cara-berkontribusi)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Testing Guidelines](#testing-guidelines)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Documentation](#documentation)
- [Community](#community)

---

## 🤝 Code of Conduct

Proyek ini mengadopsi [Contributor Covenant Code of Conduct](https://www.contributor-covenant.org/version/2/1/code_of_conduct/). Dengan berpartisipasi, Anda diharapkan untuk menjunjung tinggi kode etik ini.

### Perilaku yang Diharapkan

- Menggunakan bahasa yang ramah dan inklusif
- Menghormati sudut pandang dan pengalaman yang berbeda
- Menerima kritik konstruktif dengan baik
- Fokus pada apa yang terbaik untuk komunitas
- Menunjukkan empati terhadap anggota komunitas lainnya

### Perilaku yang Tidak Dapat Diterima

- Penggunaan bahasa atau gambar yang bersifat seksual
- Trolling, komentar yang menghina, atau serangan pribadi
- Pelecehan publik atau pribadi
- Mempublikasikan informasi pribadi orang lain tanpa izin
- Perilaku lain yang tidak pantas dalam lingkungan profesional

---

## 🚀 Cara Berkontribusi

### Jenis Kontribusi yang Diterima

1. **Bug Reports**: Melaporkan bug atau masalah yang ditemukan
2. **Feature Requests**: Mengusulkan fitur atau peningkatan baru
3. **Code Contributions**: Mengirimkan perbaikan bug atau fitur baru
4. **Documentation**: Memperbaiki atau menambah dokumentasi
5. **Testing**: Menambah atau memperbaiki test cases
6. **Security**: Melaporkan kerentanan keamanan

### Sebelum Memulai

1. **Cek Issue yang Ada**: Pastikan masalah atau fitur yang ingin Anda kerjakan belum ada
2. **Diskusi Terlebih Dahulu**: Untuk perubahan besar, buat issue untuk diskusi
3. **Fork Repository**: Buat fork dari repository utama
4. **Buat Branch**: Buat branch baru untuk setiap fitur atau perbaikan

---

## 💻 Development Setup

### Prerequisites

- PHP 8.1 atau lebih tinggi
- Composer 2.x
- Node.js 18.x atau lebih tinggi
- MySQL 8.0 atau PostgreSQL 13+
- Redis 6.0+

### Local Development Setup

```bash
# 1. Fork dan clone repository
git clone https://github.com/your-username/Single-Sign-On-PIPP.git
cd Single-Sign-On-PIPP

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Configure database
# Edit .env file dengan konfigurasi database Anda

# 5. Run migrations dan seeders
php artisan migrate
php artisan db:seed

# 6. Install Passport keys (jika menggunakan Laravel Passport)
php artisan passport:install

# 7. Start development server
php artisan serve
```

### Development Tools

```bash
# Install development tools
composer require --dev phpunit/phpunit
composer require --dev laravel/pint
composer require --dev phpstan/phpstan
composer require --dev psalm/plugin-laravel

# Setup pre-commit hooks
composer require --dev brianium/paratest
npm install --save-dev husky lint-staged
```

---

## 📝 Coding Standards

### PHP Coding Standards

Proyek ini mengikuti [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard.

#### Code Style

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    /**
     * Authenticate user with credentials.
     *
     * @param string $username
     * @param string $password
     * @return array
     */
    public function authenticate(string $username, string $password): array
    {
        $user = User::where('username', $username)->first();
        
        if (!$user || !Hash::check($password, $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid credentials',
            ];
        }
        
        return [
            'success' => true,
            'user' => $user,
            'token' => $this->generateToken($user),
        ];
    }
    
    private function generateToken(User $user): string
    {
        return $user->createToken('auth-token')->plainTextToken;
    }
}
```

#### Naming Conventions

- **Classes**: PascalCase (`UserService`, `AuthController`)
- **Methods**: camelCase (`getUserProfile`, `validateToken`)
- **Variables**: camelCase (`$userName`, `$authToken`)
- **Constants**: UPPER_SNAKE_CASE (`MAX_LOGIN_ATTEMPTS`)
- **Database Tables**: snake_case (`user_profiles`, `sso_sessions`)
- **Database Columns**: snake_case (`first_name`, `created_at`)

#### Documentation

```php
/**
 * Validate SSO token and return user information.
 *
 * @param string $token The SSO token to validate
 * @param string $service The requesting service name
 * @return array{success: bool, user?: User, message?: string}
 * 
 * @throws TokenExpiredException When token has expired
 * @throws InvalidTokenException When token is invalid
 */
public function validateSSOToken(string $token, string $service): array
{
    // Implementation
}
```

### Laravel Best Practices

#### Controllers

```php
// GOOD: Thin controllers
class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}
    
    public function login(LoginRequest $request)
    {
        $result = $this->authService->authenticate(
            $request->username,
            $request->password
        );
        
        return response()->json($result);
    }
}

// BAD: Fat controllers with business logic
class AuthController extends Controller
{
    public function login(Request $request)
    {
        // Don't put business logic here
        $user = User::where('username', $request->username)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }
        // ... more business logic
    }
}
```

#### Services

```php
// GOOD: Single responsibility
class UserService
{
    public function createUser(array $userData): User
    {
        return User::create($userData);
    }
    
    public function updateUserProfile(User $user, array $profileData): User
    {
        $user->update($profileData);
        return $user->fresh();
    }
}

// Use separate services for different concerns
class NotificationService
{
    public function sendWelcomeEmail(User $user): void
    {
        // Email logic
    }
}
```

#### Models

```php
// GOOD: Use accessors and mutators
class User extends Authenticatable
{
    protected $fillable = [
        'username', 'email', 'first_name', 'last_name'
    ];
    
    protected $hidden = [
        'password', 'remember_token'
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];
    
    // Accessor
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
    
    // Mutator
    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = strtolower($value);
    }
    
    // Relationships
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```

### JavaScript/TypeScript Standards

```javascript
// Use modern ES6+ syntax
const authenticateUser = async (credentials) => {
    try {
        const response = await fetch('/api/auth/login', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(credentials)
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('Authentication failed:', error);
        throw error;
    }
};
```

---

## 🧪 Testing Guidelines

### Test Structure

```
tests/
├── Unit/           # Unit tests for individual classes/methods
├── Feature/        # Feature tests for API endpoints
├── Integration/    # Integration tests for services
├── Security/       # Security-specific tests
└── Browser/        # End-to-end browser tests
```

### Writing Tests

#### Unit Tests

```php
// tests/Unit/Services/AuthServiceTest.php
class AuthServiceTest extends TestCase
{
    use RefreshDatabase;
    
    private AuthService $authService;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }
    
    public function test_can_authenticate_valid_user(): void
    {
        // Arrange
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password')
        ]);
        
        // Act
        $result = $this->authService->authenticate('testuser', 'password');
        
        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals($user->id, $result['user']->id);
    }
    
    public function test_cannot_authenticate_invalid_credentials(): void
    {
        // Arrange
        User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password')
        ]);
        
        // Act
        $result = $this->authService->authenticate('testuser', 'wrongpassword');
        
        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid credentials', $result['message']);
    }
}
```

#### Feature Tests

```php
// tests/Feature/Auth/LoginTest.php
class LoginTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_user_can_login_with_valid_credentials(): void
    {
        // Arrange
        $user = User::factory()->create([
            'username' => 'testuser',
            'password' => Hash::make('password')
        ]);
        
        // Act
        $response = $this->postJson('/api/auth/login', [
            'username' => 'testuser',
            'password' => 'password'
        ]);
        
        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'username', 'email'],
                    'token'
                ]
            ]);
    }
}
```

### Test Coverage

Pastikan test coverage minimal 80%:

```bash
# Run tests with coverage
php artisan test --coverage

# Generate HTML coverage report
php artisan test --coverage-html coverage-report
```

### Test Data

Gunakan factories untuk test data:

```php
// database/factories/UserFactory.php
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail,
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'password' => Hash::make('password'),
        ];
    }
    
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'username' => 'admin',
            'email' => 'admin@pipp.kkp.go.id',
        ]);
    }
}
```

---

## 🔄 Pull Request Process

### Before Submitting

1. **Update Documentation**: Pastikan dokumentasi diperbarui
2. **Add Tests**: Tambahkan tests untuk fitur atau perbaikan baru
3. **Run Tests**: Pastikan semua tests pass
4. **Code Style**: Jalankan code formatter dan linter
5. **Security Check**: Pastikan tidak ada kerentanan keamanan

### PR Checklist

```markdown
## Pull Request Checklist

- [ ] Kode mengikuti coding standards proyek
- [ ] Tests telah ditambahkan untuk perubahan baru
- [ ] Semua tests existing masih pass
- [ ] Dokumentasi telah diperbarui
- [ ] Tidak ada breaking changes (atau sudah didokumentasikan)
- [ ] Security review telah dilakukan
- [ ] Performance impact telah dipertimbangkan
```

### PR Template

```markdown
## Description
Brief description of changes made.

## Type of Change
- [ ] Bug fix (non-breaking change which fixes an issue)
- [ ] New feature (non-breaking change which adds functionality)
- [ ] Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] Documentation update

## Testing
- [ ] Unit tests added/updated
- [ ] Integration tests added/updated
- [ ] Manual testing completed

## Screenshots (if applicable)
Add screenshots to help explain your changes.

## Additional Notes
Any additional information that reviewers should know.
```

### Review Process

1. **Automated Checks**: CI/CD pipeline akan menjalankan tests otomatis
2. **Code Review**: Minimal 2 reviewer harus approve
3. **Security Review**: Untuk perubahan yang berkaitan dengan keamanan
4. **Documentation Review**: Pastikan dokumentasi lengkap dan akurat

---

## 🐛 Issue Reporting

### Bug Reports

Gunakan template berikut untuk melaporkan bug:

```markdown
## Bug Description
A clear and concise description of what the bug is.

## Steps to Reproduce
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

## Expected Behavior
A clear and concise description of what you expected to happen.

## Actual Behavior
A clear and concise description of what actually happened.

## Environment
- OS: [e.g. Ubuntu 20.04]
- PHP Version: [e.g. 8.1.0]
- Laravel Version: [e.g. 10.0.0]
- Browser: [e.g. Chrome 91.0]

## Additional Context
Add any other context about the problem here.

## Screenshots
If applicable, add screenshots to help explain your problem.
```

### Feature Requests

```markdown
## Feature Description
A clear and concise description of the feature you'd like to see.

## Problem Statement
What problem does this feature solve?

## Proposed Solution
Describe the solution you'd like to see implemented.

## Alternatives Considered
Describe any alternative solutions you've considered.

## Additional Context
Add any other context or screenshots about the feature request here.
```

---

## 🔒 Security Vulnerabilities

### Reporting Security Issues

**JANGAN** melaporkan kerentanan keamanan melalui public issues. Gunakan salah satu cara berikut:

1. **Email**: security@pipp.kkp.go.id
2. **Private Message**: Hubungi maintainer secara langsung
3. **Security Advisory**: Gunakan GitHub Security Advisory

### Security Report Template

```markdown
## Vulnerability Description
Brief description of the security vulnerability.

## Impact
What is the potential impact of this vulnerability?

## Steps to Reproduce
1. Step 1
2. Step 2
3. Step 3

## Proof of Concept
Include code or screenshots demonstrating the vulnerability.

## Suggested Fix
If you have suggestions for fixing the vulnerability.

## Disclosure Timeline
When do you plan to publicly disclose this vulnerability?
```

### Security Response Process

1. **Acknowledgment**: Dalam 24 jam
2. **Initial Assessment**: Dalam 72 jam
3. **Fix Development**: Tergantung severity
4. **Testing**: Comprehensive security testing
5. **Release**: Coordinated disclosure

---

## 📚 Documentation

### Documentation Standards

1. **Clear and Concise**: Gunakan bahasa yang mudah dipahami
2. **Examples**: Sertakan contoh kode yang praktis
3. **Up-to-date**: Pastikan dokumentasi selalu terkini
4. **Multilingual**: Dokumentasi dalam Bahasa Indonesia dan Inggris

### Documentation Types

- **API Documentation**: Menggunakan OpenAPI/Swagger
- **Code Documentation**: PHPDoc untuk PHP, JSDoc untuk JavaScript
- **User Guides**: Panduan penggunaan untuk end users
- **Developer Guides**: Panduan untuk developers
- **Architecture Documentation**: Dokumentasi arsitektur sistem

### Writing Guidelines

```php
/**
 * Authenticate user and generate access token.
 *
 * This method validates user credentials and generates a JWT token
 * for authenticated access to the system.
 *
 * @param string $username The user's username or email
 * @param string $password The user's password
 * @param bool $remember Whether to create a long-lived token
 * 
 * @return array{
 *     success: bool,
 *     user?: User,
 *     token?: string,
 *     expires_in?: int,
 *     message?: string
 * }
 * 
 * @throws AuthenticationException When credentials are invalid
 * @throws AccountLockedException When account is locked
 * 
 * @example
 * ```php
 * $result = $authService->authenticate('john.doe', 'password123');
 * if ($result['success']) {
 *     $token = $result['token'];
 *     // Use token for subsequent requests
 * }
 * ```
 */
public function authenticate(string $username, string $password, bool $remember = false): array
{
    // Implementation
}
```

---

## 🌟 Community

### Communication Channels

- **GitHub Discussions**: Untuk diskusi umum dan Q&A
- **Issues**: Untuk bug reports dan feature requests
- **Email**: support@pipp.kkp.go.id untuk support
- **Slack**: [PIPP Developers Slack](https://pipp-dev.slack.com)

### Getting Help

1. **Check Documentation**: Baca dokumentasi terlebih dahulu
2. **Search Issues**: Cari di existing issues
3. **Ask in Discussions**: Gunakan GitHub Discussions
4. **Contact Support**: Email untuk masalah urgent

### Recognition

Kontributor akan diakui dalam:
- **CONTRIBUTORS.md**: Daftar semua kontributor
- **Release Notes**: Mention dalam changelog
- **Hall of Fame**: Kontributor terbaik setiap bulan

---

## 📋 Development Workflow

### Git Workflow

```bash
# 1. Create feature branch
git checkout -b feature/user-authentication

# 2. Make changes and commit
git add .
git commit -m "feat: add user authentication endpoint"

# 3. Push to your fork
git push origin feature/user-authentication

# 4. Create pull request
# Use GitHub interface to create PR

# 5. Address review comments
git add .
git commit -m "fix: address review comments"
git push origin feature/user-authentication

# 6. Merge after approval
# Maintainer will merge the PR
```

### Commit Message Convention

Gunakan [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(auth): add SSO token validation
fix(api): resolve user profile endpoint error
docs(readme): update installation instructions
test(auth): add unit tests for login service
```

### Branch Naming

- `feature/feature-name`: New features
- `fix/bug-description`: Bug fixes
- `docs/documentation-update`: Documentation updates
- `refactor/component-name`: Code refactoring
- `test/test-description`: Test additions/updates

---

## 🎯 Quality Assurance

### Code Quality Tools

```bash
# PHP Code Sniffer
./vendor/bin/phpcs --standard=PSR12 app/

# PHP Stan (Static Analysis)
./vendor/bin/phpstan analyse app/ --level=8

# Psalm (Static Analysis)
./vendor/bin/psalm

# Laravel Pint (Code Formatter)
./vendor/bin/pint

# Security Checker
./vendor/bin/security-checker security:check
```

### Pre-commit Hooks

```json
// package.json
{
  "husky": {
    "hooks": {
      "pre-commit": "lint-staged"
    }
  },
  "lint-staged": {
    "*.php": [
      "./vendor/bin/pint",
      "./vendor/bin/phpstan analyse",
      "php artisan test"
    ],
    "*.js": [
      "eslint --fix",
      "prettier --write"
    ]
  }
}
```

### Continuous Integration

```yaml
# .github/workflows/ci.yml
name: CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
    - uses: actions/checkout@v3
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
    
    - name: Install dependencies
      run: composer install
    
    - name: Run tests
      run: php artisan test
    
    - name: Code style check
      run: ./vendor/bin/pint --test
    
    - name: Static analysis
      run: ./vendor/bin/phpstan analyse
```

---

## 🏆 Recognition & Rewards

### Contributor Levels

1. **First-time Contributor**: Kontribusi pertama
2. **Regular Contributor**: 5+ kontribusi
3. **Core Contributor**: 20+ kontribusi + review PRs
4. **Maintainer**: Akses write ke repository

### Rewards

- **Swag**: Sticker, t-shirt untuk kontributor aktif
- **Certificate**: Sertifikat kontribusi untuk portfolio
- **Reference**: Referensi untuk career development
- **Recognition**: Mention di conference dan blog posts

---

**Terima kasih telah berkontribusi pada SSO PIPP! Setiap kontribusi, sekecil apapun, sangat berarti untuk kemajuan proyek ini.**