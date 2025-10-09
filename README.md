# Single Sign-On (SSO) System - PIPP

Sistem Single Sign-On (SSO) untuk Platform Informasi Pelabuhan Perikanan (PIPP) yang mengintegrasikan berbagai layanan seperti Sahbandar, SPB, SHTI, dan EPIT.

## 🚀 Fitur Utama

- **Single Sign-On Authentication**: Login sekali untuk mengakses semua layanan
- **Multi-Service Integration**: Integrasi dengan Sahbandar, SPB, SHTI, dan EPIT
- **Role-Based Access Control**: Sistem otorisasi berbasis peran dan permission
- **JWT Token Management**: Keamanan dengan JSON Web Token
- **Audit Logging**: Pencatatan aktivitas sistem untuk keamanan
- **Rate Limiting**: Pembatasan request untuk mencegah abuse
- **Security Headers**: Header keamanan untuk melindungi dari serangan web
- **CORS Configuration**: Konfigurasi Cross-Origin Resource Sharing

## 📋 Persyaratan Sistem

- PHP >= 8.1
- Laravel >= 10.0
- MySQL >= 8.0 atau PostgreSQL >= 13
- Redis (untuk caching dan session)
- Composer
- Node.js & NPM (untuk frontend assets)

## 🛠️ Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/your-org/single-sign-on-pipp.git
cd single-sign-on-pipp
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

### 4. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 5. Storage Link

```bash
php artisan storage:link
```

## ⚙️ Konfigurasi Environment

### Database Configuration

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sso_pipp
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### JWT Configuration

```env
JWT_SECRET=your_jwt_secret_key
JWT_TTL=60
JWT_REFRESH_TTL=20160
JWT_ALGO=HS256
```

### Microservices Configuration

```env
# Sahbandar Service
SAHBANDAR_SERVICE_URL=http://localhost:8001
SAHBANDAR_API_KEY=your_sahbandar_api_key
SAHBANDAR_CALLBACK_URL=http://localhost:8001/auth/sso/callback

# SPB Service
SPB_SERVICE_URL=http://localhost:8002
SPB_API_KEY=your_spb_api_key
SPB_CALLBACK_URL=http://localhost:8002/auth/sso/callback

# SHTI Service
SHTI_SERVICE_URL=http://localhost:8003
SHTI_API_KEY=your_shti_api_key
SHTI_CALLBACK_URL=http://localhost:8003/auth/sso/callback

# EPIT Service
EPIT_SERVICE_URL=http://localhost:8004
EPIT_API_KEY=your_epit_api_key
EPIT_CALLBACK_URL=http://localhost:8004/auth/sso/callback
```

### SSO Configuration

```env
SSO_DOMAIN=pipp.kkp.go.id
SSO_TOKEN_TTL=3600
SSO_SESSION_TTL=86400
SSO_MAX_SESSIONS=5
SSO_CLEANUP_EXPIRED=true
```

### Security Configuration

```env
RATE_LIMIT_LOGIN=5
RATE_LIMIT_API=60
RATE_LIMIT_SSO=100
PASSWORD_MIN_LENGTH=8
SESSION_SECURE_COOKIES=true
SESSION_SAME_SITE=strict
```

## 🏃‍♂️ Menjalankan Aplikasi

### Development Server

```bash
php artisan serve
```

### Queue Worker (untuk background jobs)

```bash
php artisan queue:work
```

### Schedule Runner (untuk cleanup tasks)

```bash
php artisan schedule:work
```

## 📚 API Documentation

### Authentication Endpoints

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "username": "admin",
    "password": "password123",
    "remember": false
}
```

#### Register
```http
POST /api/auth/register
Content-Type: application/json

{
    "username": "newuser",
    "email": "user@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "081234567890",
    "nip": "123456789",
    "position": "Staff",
    "department": "sahbandar",
    "office_location": "Jakarta"
}
```

#### Get User Profile
```http
GET /api/me
Authorization: Bearer {jwt_token}
```

#### Logout
```http
POST /api/logout
Authorization: Bearer {jwt_token}
```

### SSO Endpoints

#### SSO Login
```http
POST /api/sso/login
Content-Type: application/json

{
    "service": "sahbandar",
    "redirect_url": "http://localhost:8001/dashboard"
}
```

#### Validate SSO Token
```http
POST /api/sso/validate
Content-Type: application/json
X-SSO-Token: {sso_token}

{
    "service": "sahbandar"
}
```

#### Get SSO Sessions
```http
GET /api/sso/sessions
Authorization: Bearer {jwt_token}
```

### Service Integration Endpoints

#### Sahbandar Service
```http
GET /api/sahbandar/dashboard
Authorization: Bearer {jwt_token}

GET /api/sahbandar/vessels
Authorization: Bearer {jwt_token}

POST /api/sahbandar/clearances
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

#### SPB Service
```http
GET /api/spb/applications
Authorization: Bearer {jwt_token}

POST /api/spb/applications
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

#### SHTI Service
```http
GET /api/shti/catch-reports
Authorization: Bearer {jwt_token}

POST /api/shti/catch-reports
Authorization: Bearer {jwt_token}
Content-Type: application/json
```

#### EPIT Service
```http
GET /api/epit/port-systems
Authorization: Bearer {jwt_token}

GET /api/epit/vessel-tracking
Authorization: Bearer {jwt_token}
```

## 🔐 Sistem Keamanan

### Middleware Security

1. **SecurityHeadersMiddleware**: Menambahkan security headers
2. **RateLimitMiddleware**: Membatasi jumlah request
3. **SSOTokenMiddleware**: Validasi SSO token
4. **ServiceAccessMiddleware**: Kontrol akses layanan
5. **AuditLogMiddleware**: Pencatatan aktivitas

### Role dan Permission

#### Default Roles
- **super-admin**: Akses penuh ke semua sistem
- **admin**: Akses administratif
- **sahbandar-officer**: Akses ke layanan Sahbandar
- **spb-officer**: Akses ke layanan SPB
- **shti-officer**: Akses ke layanan SHTI
- **epit-officer**: Akses ke layanan EPIT
- **user**: Akses dasar

#### Permission Structure
```
service.action
├── sahbandar.view
├── sahbandar.create
├── sahbandar.update
├── sahbandar.delete
├── sahbandar.admin
└── ...
```

## 🗄️ Database Schema

### Users Table
- `id`: Primary key
- `username`: Unique username
- `email`: Email address
- `password`: Hashed password
- `first_name`, `last_name`: User names
- `phone`: Phone number
- `nip`: Employee ID
- `position`: Job position
- `department`: Department
- `office_location`: Office location
- `status`: User status (active/inactive)
- `last_login_at`: Last login timestamp

### SSO Sessions Table
- `id`: Primary key
- `user_id`: Foreign key to users
- `service`: Service name
- `session_token`: SSO token
- `session_data`: Session metadata
- `last_activity`: Last activity timestamp
- `expires_at`: Expiration timestamp
- `is_active`: Session status

### Audit Logs Table
- `id`: Primary key
- `user_id`: Foreign key to users
- `action`: Action performed
- `service`: Service name
- `description`: Action description
- `old_values`, `new_values`: Data changes
- `ip_address`: Client IP
- `user_agent`: Client user agent
- `severity`: Log severity level

## 🔧 Maintenance

### Cleanup Expired Sessions
```bash
php artisan sso:cleanup-sessions
```

### Generate Reports
```bash
php artisan sso:generate-report --service=sahbandar --date=2024-01-01
```

### Health Check
```bash
curl http://localhost:8000/api/health
```

## 🚀 Deployment

### Production Environment

1. **Server Requirements**
   - Ubuntu 20.04+ atau CentOS 8+
   - Nginx atau Apache
   - PHP 8.1+ dengan extensions: mbstring, xml, bcmath, pdo_mysql
   - MySQL 8.0+ atau PostgreSQL 13+
   - Redis 6.0+

2. **Deployment Steps**
   ```bash
   # Clone dan setup
   git clone https://github.com/your-org/single-sign-on-pipp.git
   cd single-sign-on-pipp
   composer install --no-dev --optimize-autoloader
   
   # Environment
   cp .env.production .env
   php artisan key:generate
   php artisan jwt:secret
   
   # Database
   php artisan migrate --force
   php artisan db:seed --force
   
   # Optimize
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

3. **Web Server Configuration**
   
   **Nginx Configuration:**
   ```nginx
   server {
       listen 80;
       server_name sso.pipp.kkp.go.id;
       root /var/www/sso-pipp/public;
       
       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";
       
       index index.php;
       
       charset utf-8;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location = /favicon.ico { access_log off; log_not_found off; }
       location = /robots.txt  { access_log off; log_not_found off; }
       
       error_page 404 /index.php;
       
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }
       
       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

## 🧪 Testing

### Unit Tests
```bash
php artisan test
```

### Feature Tests
```bash
php artisan test --testsuite=Feature
```

### API Testing dengan Postman
Import collection dari `docs/postman/SSO-PIPP.postman_collection.json`

## 📊 Monitoring

### Log Files
- `storage/logs/laravel.log`: Application logs
- `storage/logs/audit.log`: Audit logs
- `storage/logs/security.log`: Security events

### Metrics Endpoints
```http
GET /api/health
GET /api/metrics (admin only)
```

## 🤝 Contributing

1. Fork repository
2. Create feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

Untuk dukungan teknis, hubungi:
- Email: support@pipp.kkp.go.id
- Phone: +62-21-1234-5678
- Documentation: https://docs.pipp.kkp.go.id

## 🔄 Changelog

### v1.0.0 (2024-01-15)
- Initial release
- SSO authentication system
- Multi-service integration
- Role-based access control
- Audit logging
- Security middleware

---

**Dikembangkan oleh Tim IT PIPP - Kementerian Kelautan dan Perikanan**