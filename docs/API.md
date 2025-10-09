# API Documentation - SSO PIPP

Dokumentasi lengkap API untuk sistem Single Sign-On Platform Informasi Pelabuhan Perikanan (PIPP).

## Base URL

```
Development: http://localhost:8000/api
Production: https://sso.pipp.kkp.go.id/api
```

## Authentication

Sistem menggunakan JWT (JSON Web Token) untuk autentikasi. Token harus disertakan dalam header `Authorization` dengan format:

```
Authorization: Bearer {jwt_token}
```

## Rate Limiting

- **Login**: 5 requests per minute
- **API**: 60 requests per minute
- **SSO**: 100 requests per minute

Rate limit headers:
- `X-RateLimit-Limit`: Batas maksimum
- `X-RateLimit-Remaining`: Sisa request
- `X-RateLimit-Reset`: Waktu reset (Unix timestamp)

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Success message",
    "data": {
        // Response data
    },
    "meta": {
        "timestamp": "2024-01-15T10:30:00Z",
        "version": "1.0.0"
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        "field": ["Validation error message"]
    },
    "meta": {
        "timestamp": "2024-01-15T10:30:00Z",
        "version": "1.0.0"
    }
}
```

## HTTP Status Codes

- `200` - OK
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Unprocessable Entity
- `429` - Too Many Requests
- `500` - Internal Server Error

---

# Authentication Endpoints

## POST /auth/login

Login pengguna dan mendapatkan JWT token.

### Request Body
```json
{
    "username": "admin",
    "password": "password123",
    "remember": false
}
```

### Response
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600,
        "user": {
            "id": 1,
            "username": "admin",
            "email": "admin@pipp.kkp.go.id",
            "first_name": "Admin",
            "last_name": "System",
            "roles": ["super-admin"],
            "permissions": ["*"]
        }
    }
}
```

## POST /auth/register

Registrasi pengguna baru.

### Request Body
```json
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

### Response
```json
{
    "success": true,
    "message": "User registered successfully",
    "data": {
        "user": {
            "id": 2,
            "username": "newuser",
            "email": "user@example.com",
            "first_name": "John",
            "last_name": "Doe",
            "status": "active"
        }
    }
}
```

## GET /me

Mendapatkan profil pengguna yang sedang login.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "data": {
        "id": 1,
        "username": "admin",
        "email": "admin@pipp.kkp.go.id",
        "first_name": "Admin",
        "last_name": "System",
        "phone": "081234567890",
        "nip": "123456789",
        "position": "System Administrator",
        "department": "IT",
        "office_location": "Jakarta",
        "status": "active",
        "last_login_at": "2024-01-15T10:30:00Z",
        "roles": ["super-admin"],
        "permissions": ["*"]
    }
}
```

## POST /logout

Logout pengguna dan invalidate token.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "message": "Successfully logged out"
}
```

## POST /refresh

Refresh JWT token.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "data": {
        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "bearer",
        "expires_in": 3600
    }
}
```

---

# SSO Endpoints

## POST /sso/login

Inisiasi SSO login untuk service tertentu.

### Request Body
```json
{
    "service": "sahbandar",
    "redirect_url": "http://localhost:8001/dashboard"
}
```

### Response
```json
{
    "success": true,
    "data": {
        "sso_token": "sso_token_here",
        "redirect_url": "http://localhost:8001/auth/sso/callback?token=sso_token_here",
        "expires_at": "2024-01-15T11:30:00Z"
    }
}
```

## POST /sso/validate

Validasi SSO token dari service.

### Headers
```
X-SSO-Token: {sso_token}
```

### Request Body
```json
{
    "service": "sahbandar"
}
```

### Response
```json
{
    "success": true,
    "data": {
        "valid": true,
        "user": {
            "id": 1,
            "username": "admin",
            "email": "admin@pipp.kkp.go.id",
            "first_name": "Admin",
            "last_name": "System",
            "roles": ["super-admin"],
            "permissions": ["sahbandar.*"]
        },
        "service": "sahbandar",
        "expires_at": "2024-01-15T11:30:00Z"
    }
}
```

## GET /sso/sessions

Mendapatkan daftar SSO session aktif.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "service": "sahbandar",
            "last_activity": "2024-01-15T10:30:00Z",
            "expires_at": "2024-01-15T11:30:00Z",
            "is_active": true
        },
        {
            "id": 2,
            "service": "spb",
            "last_activity": "2024-01-15T10:25:00Z",
            "expires_at": "2024-01-15T11:25:00Z",
            "is_active": true
        }
    ]
}
```

## DELETE /sso/sessions/{id}

Terminate SSO session tertentu.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "message": "SSO session terminated successfully"
}
```

---

# Sahbandar Service Endpoints

## GET /sahbandar/profile

Mendapatkan profil pengguna untuk service Sahbandar.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "data": {
        "user": {
            "id": 1,
            "username": "admin",
            "email": "admin@pipp.kkp.go.id",
            "first_name": "Admin",
            "last_name": "System",
            "permissions": ["sahbandar.view", "sahbandar.create"]
        }
    }
}
```

## GET /sahbandar/dashboard

Mendapatkan data dashboard Sahbandar.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "data": {
        "total_vessels": 150,
        "active_clearances": 25,
        "pending_applications": 8,
        "completed_today": 12,
        "recent_activities": [
            {
                "id": 1,
                "vessel_name": "KM Bahari",
                "action": "clearance_issued",
                "timestamp": "2024-01-15T10:30:00Z"
            }
        ]
    }
}
```

## GET /sahbandar/vessels

Mendapatkan daftar kapal.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Query Parameters
- `page`: Halaman (default: 1)
- `per_page`: Jumlah per halaman (default: 15)
- `search`: Pencarian berdasarkan nama kapal
- `status`: Filter berdasarkan status

### Response
```json
{
    "success": true,
    "data": {
        "vessels": [
            {
                "id": 1,
                "name": "KM Bahari",
                "imo_number": "1234567",
                "call_sign": "YBAA",
                "flag": "Indonesia",
                "gross_tonnage": 500,
                "status": "active"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 150,
            "last_page": 10
        }
    }
}
```

## GET /sahbandar/vessels/{id}

Mendapatkan detail kapal.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "data": {
        "vessel": {
            "id": 1,
            "name": "KM Bahari",
            "imo_number": "1234567",
            "call_sign": "YBAA",
            "flag": "Indonesia",
            "gross_tonnage": 500,
            "length": 45.5,
            "beam": 8.2,
            "draft": 3.1,
            "owner": "PT Bahari Jaya",
            "status": "active",
            "last_port": "Jakarta",
            "next_port": "Surabaya"
        }
    }
}
```

## GET /sahbandar/clearances

Mendapatkan daftar clearance.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Query Parameters
- `page`: Halaman (default: 1)
- `per_page`: Jumlah per halaman (default: 15)
- `status`: Filter berdasarkan status
- `vessel_id`: Filter berdasarkan kapal

### Response
```json
{
    "success": true,
    "data": {
        "clearances": [
            {
                "id": 1,
                "vessel_id": 1,
                "vessel_name": "KM Bahari",
                "clearance_number": "CLR-2024-001",
                "type": "departure",
                "status": "approved",
                "issued_at": "2024-01-15T10:30:00Z",
                "valid_until": "2024-01-16T10:30:00Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 100,
            "last_page": 7
        }
    }
}
```

## POST /sahbandar/clearances

Membuat clearance baru.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Request Body
```json
{
    "vessel_id": 1,
    "type": "departure",
    "destination_port": "Surabaya",
    "departure_date": "2024-01-16T08:00:00Z",
    "cargo_description": "Ikan segar",
    "crew_count": 8
}
```

### Response
```json
{
    "success": true,
    "message": "Clearance created successfully",
    "data": {
        "clearance": {
            "id": 2,
            "vessel_id": 1,
            "clearance_number": "CLR-2024-002",
            "type": "departure",
            "status": "pending",
            "created_at": "2024-01-15T10:30:00Z"
        }
    }
}
```

---

# SPB Service Endpoints

## GET /spb/applications

Mendapatkan daftar aplikasi SPB.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Query Parameters
- `page`: Halaman (default: 1)
- `per_page`: Jumlah per halaman (default: 15)
- `status`: Filter berdasarkan status
- `vessel_name`: Filter berdasarkan nama kapal

### Response
```json
{
    "success": true,
    "data": {
        "applications": [
            {
                "id": 1,
                "application_number": "SPB-2024-001",
                "vessel_name": "KM Nelayan",
                "owner_name": "PT Nelayan Jaya",
                "status": "approved",
                "submitted_at": "2024-01-15T09:00:00Z",
                "approved_at": "2024-01-15T10:30:00Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 75,
            "last_page": 5
        }
    }
}
```

## POST /spb/applications

Membuat aplikasi SPB baru.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Request Body
```json
{
    "vessel_name": "KM Nelayan Baru",
    "vessel_type": "fishing",
    "gross_tonnage": 30,
    "length": 15.5,
    "owner_name": "PT Nelayan Baru",
    "owner_address": "Jakarta",
    "fishing_area": "WPP 571",
    "fishing_gear": "purse_seine"
}
```

### Response
```json
{
    "success": true,
    "message": "SPB application created successfully",
    "data": {
        "application": {
            "id": 2,
            "application_number": "SPB-2024-002",
            "vessel_name": "KM Nelayan Baru",
            "status": "pending",
            "submitted_at": "2024-01-15T10:30:00Z"
        }
    }
}
```

## GET /spb/certificates

Mendapatkan daftar sertifikat SPB.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "data": {
        "certificates": [
            {
                "id": 1,
                "certificate_number": "SPB-CERT-2024-001",
                "vessel_name": "KM Nelayan",
                "issued_at": "2024-01-15T10:30:00Z",
                "valid_until": "2025-01-15T10:30:00Z",
                "status": "active"
            }
        ]
    }
}
```

---

# SHTI Service Endpoints

## GET /shti/catch-reports

Mendapatkan daftar laporan hasil tangkap.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Query Parameters
- `page`: Halaman (default: 1)
- `per_page`: Jumlah per halaman (default: 15)
- `vessel_id`: Filter berdasarkan kapal
- `date_from`: Filter tanggal mulai
- `date_to`: Filter tanggal akhir

### Response
```json
{
    "success": true,
    "data": {
        "reports": [
            {
                "id": 1,
                "report_number": "SHTI-2024-001",
                "vessel_name": "KM Nelayan",
                "fishing_date": "2024-01-15",
                "fishing_area": "WPP 571",
                "total_catch": 500,
                "status": "verified"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 200,
            "last_page": 14
        }
    }
}
```

## POST /shti/catch-reports

Membuat laporan hasil tangkap baru.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Request Body
```json
{
    "vessel_id": 1,
    "fishing_date": "2024-01-15",
    "fishing_area": "WPP 571",
    "fishing_gear": "purse_seine",
    "catch_details": [
        {
            "species": "tuna",
            "quantity": 300,
            "unit": "kg"
        },
        {
            "species": "skipjack",
            "quantity": 200,
            "unit": "kg"
        }
    ]
}
```

### Response
```json
{
    "success": true,
    "message": "Catch report created successfully",
    "data": {
        "report": {
            "id": 2,
            "report_number": "SHTI-2024-002",
            "vessel_id": 1,
            "status": "pending",
            "created_at": "2024-01-15T10:30:00Z"
        }
    }
}
```

## GET /shti/fishing-quotas

Mendapatkan kuota penangkapan ikan.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "data": {
        "quotas": [
            {
                "species": "tuna",
                "area": "WPP 571",
                "total_quota": 10000,
                "used_quota": 7500,
                "remaining_quota": 2500,
                "unit": "kg"
            }
        ]
    }
}
```

---

# EPIT Service Endpoints

## GET /epit/port-systems

Mendapatkan daftar sistem pelabuhan.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "data": {
        "systems": [
            {
                "id": 1,
                "port_name": "Pelabuhan Perikanan Muara Baru",
                "port_code": "JKTMB",
                "location": "Jakarta",
                "status": "operational",
                "berths_total": 20,
                "berths_occupied": 12
            }
        ]
    }
}
```

## GET /epit/vessel-tracking

Mendapatkan data tracking kapal.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Query Parameters
- `vessel_id`: ID kapal
- `port_id`: ID pelabuhan

### Response
```json
{
    "success": true,
    "data": {
        "tracking": [
            {
                "vessel_id": 1,
                "vessel_name": "KM Bahari",
                "current_position": {
                    "latitude": -6.1234,
                    "longitude": 106.5678
                },
                "status": "at_port",
                "berth_number": "B-05",
                "arrival_time": "2024-01-15T08:00:00Z",
                "estimated_departure": "2024-01-16T06:00:00Z"
            }
        ]
    }
}
```

## GET /epit/berth-availability

Mendapatkan ketersediaan dermaga.

### Headers
```
Authorization: Bearer {jwt_token}
```

### Response
```json
{
    "success": true,
    "data": {
        "berths": [
            {
                "berth_number": "B-01",
                "status": "occupied",
                "vessel_name": "KM Bahari",
                "occupied_since": "2024-01-15T08:00:00Z",
                "estimated_departure": "2024-01-16T06:00:00Z"
            },
            {
                "berth_number": "B-02",
                "status": "available",
                "last_occupied": "2024-01-14T18:00:00Z"
            }
        ]
    }
}
```

---

# Service-to-Service Communication

## POST /services/sahbandar/sync

Sinkronisasi data dengan service Sahbandar.

### Headers
```
X-SSO-Token: {service_token}
```

### Request Body
```json
{
    "sync_type": "vessels",
    "last_sync": "2024-01-15T00:00:00Z"
}
```

### Response
```json
{
    "success": true,
    "data": {
        "synced_records": 25,
        "last_sync": "2024-01-15T10:30:00Z"
    }
}
```

---

# Admin Endpoints

## GET /admin/users

Mendapatkan daftar pengguna (admin only).

### Headers
```
Authorization: Bearer {jwt_token}
```

### Query Parameters
- `page`: Halaman (default: 1)
- `per_page`: Jumlah per halaman (default: 15)
- `search`: Pencarian berdasarkan username/email
- `role`: Filter berdasarkan role
- `status`: Filter berdasarkan status

### Response
```json
{
    "success": true,
    "data": {
        "users": [
            {
                "id": 1,
                "username": "admin",
                "email": "admin@pipp.kkp.go.id",
                "first_name": "Admin",
                "last_name": "System",
                "status": "active",
                "roles": ["super-admin"],
                "last_login_at": "2024-01-15T10:30:00Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 50,
            "last_page": 4
        }
    }
}
```

## POST /admin/users

Membuat pengguna baru (admin only).

### Headers
```
Authorization: Bearer {jwt_token}
```

### Request Body
```json
{
    "username": "newuser",
    "email": "user@example.com",
    "password": "password123",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "081234567890",
    "nip": "123456789",
    "position": "Staff",
    "department": "sahbandar",
    "office_location": "Jakarta",
    "roles": ["sahbandar-officer"]
}
```

### Response
```json
{
    "success": true,
    "message": "User created successfully",
    "data": {
        "user": {
            "id": 2,
            "username": "newuser",
            "email": "user@example.com",
            "status": "active",
            "roles": ["sahbandar-officer"]
        }
    }
}
```

## GET /admin/audit-logs

Mendapatkan audit logs (admin only).

### Headers
```
Authorization: Bearer {jwt_token}
```

### Query Parameters
- `page`: Halaman (default: 1)
- `per_page`: Jumlah per halaman (default: 15)
- `user_id`: Filter berdasarkan user
- `service`: Filter berdasarkan service
- `action`: Filter berdasarkan action
- `date_from`: Filter tanggal mulai
- `date_to`: Filter tanggal akhir

### Response
```json
{
    "success": true,
    "data": {
        "logs": [
            {
                "id": 1,
                "user_id": 1,
                "username": "admin",
                "action": "login",
                "service": "sso",
                "description": "User logged in successfully",
                "ip_address": "192.168.1.100",
                "user_agent": "Mozilla/5.0...",
                "created_at": "2024-01-15T10:30:00Z"
            }
        ],
        "pagination": {
            "current_page": 1,
            "per_page": 15,
            "total": 1000,
            "last_page": 67
        }
    }
}
```

---

# Error Codes

## Authentication Errors
- `AUTH_001`: Invalid credentials
- `AUTH_002`: Account locked
- `AUTH_003`: Token expired
- `AUTH_004`: Token invalid
- `AUTH_005`: Insufficient permissions

## SSO Errors
- `SSO_001`: Invalid SSO token
- `SSO_002`: SSO session expired
- `SSO_003`: Service not allowed
- `SSO_004`: Maximum sessions exceeded

## Service Errors
- `SVC_001`: Service unavailable
- `SVC_002`: Service timeout
- `SVC_003`: Invalid service response
- `SVC_004`: Service authentication failed

## Validation Errors
- `VAL_001`: Required field missing
- `VAL_002`: Invalid field format
- `VAL_003`: Field value out of range
- `VAL_004`: Duplicate value

---

# Webhooks

## SSO Session Events

Sistem dapat mengirim webhook notifications untuk events SSO tertentu.

### Session Created
```json
{
    "event": "sso.session.created",
    "timestamp": "2024-01-15T10:30:00Z",
    "data": {
        "user_id": 1,
        "service": "sahbandar",
        "session_id": "sess_123456"
    }
}
```

### Session Expired
```json
{
    "event": "sso.session.expired",
    "timestamp": "2024-01-15T11:30:00Z",
    "data": {
        "user_id": 1,
        "service": "sahbandar",
        "session_id": "sess_123456"
    }
}
```

---

# SDK Examples

## JavaScript/Node.js

```javascript
const axios = require('axios');

class SSOClient {
    constructor(baseUrl, apiKey) {
        this.baseUrl = baseUrl;
        this.apiKey = apiKey;
        this.token = null;
    }

    async login(username, password) {
        const response = await axios.post(`${this.baseUrl}/auth/login`, {
            username,
            password
        });
        
        this.token = response.data.data.access_token;
        return response.data;
    }

    async getProfile() {
        const response = await axios.get(`${this.baseUrl}/me`, {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });
        
        return response.data;
    }

    async ssoLogin(service, redirectUrl) {
        const response = await axios.post(`${this.baseUrl}/sso/login`, {
            service,
            redirect_url: redirectUrl
        }, {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });
        
        return response.data;
    }
}

// Usage
const client = new SSOClient('http://localhost:8000/api', 'your-api-key');
await client.login('admin', 'password123');
const profile = await client.getProfile();
```

## PHP

```php
<?php

class SSOClient {
    private $baseUrl;
    private $apiKey;
    private $token;

    public function __construct($baseUrl, $apiKey) {
        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    public function login($username, $password) {
        $response = $this->makeRequest('POST', '/auth/login', [
            'username' => $username,
            'password' => $password
        ]);

        $this->token = $response['data']['access_token'];
        return $response;
    }

    public function getProfile() {
        return $this->makeRequest('GET', '/me');
    }

    public function ssoLogin($service, $redirectUrl) {
        return $this->makeRequest('POST', '/sso/login', [
            'service' => $service,
            'redirect_url' => $redirectUrl
        ]);
    }

    private function makeRequest($method, $endpoint, $data = null) {
        $curl = curl_init();
        
        $headers = ['Content-Type: application/json'];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response, true);
    }
}

// Usage
$client = new SSOClient('http://localhost:8000/api', 'your-api-key');
$client->login('admin', 'password123');
$profile = $client->getProfile();
?>
```

---

**Dokumentasi ini akan terus diperbarui seiring dengan pengembangan sistem.**