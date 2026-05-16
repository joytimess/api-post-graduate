# 🎓 Post Graduate Analytics API

> Backend REST API for an enrollment analytics dashboard for postgraduate programs (S2/S3). Tracks the full enrollment pipeline from awareness through active enrollment, analyzing funnel conversion, attrition, retention, and traffic source performance.

![PHP](https://img.shields.io/badge/PHP-%3E%3D8.3-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13.7-FF2D20?logo=laravel&logoColor=white)
![Sanctum](https://img.shields.io/badge/Sanctum-4.0-FF2D20?logo=laravel&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green)
![Tests](https://img.shields.io/badge/Tests-PestPHP_4.7-9B59B6)

---

## 📋 Table of Contents

- [Features](#-features)
- [Tech Stack](#-tech-stack)
- [Requirements](#-requirements)
- [Installation & Setup](#-installation--setup)
- [Environment Variables](#-environment-variables)
- [API Documentation](#-api-documentation)
- [Scheduled Commands](#-scheduled-commands)
- [Running Tests](#-running-tests)
- [Database Schema](#-database-schema)
- [Default Credentials](#-default-credentials)

---

## ✨ Features

| Module | Description |
|---|---|
| 🔐 **Authentication** | Bearer token login via Laravel Sanctum (8-hour TTL), refresh, and logout |
| 📊 **Dashboard** | Comprehensive overview: funnel, attrition, retention, insights, and session summaries |
| 🔽 **Funnel Analysis** | Conversion & dropoff metrics across 9 enrollment stages with cross-session comparison |
| 📉 **Attrition Analysis** | Dropout rate per stage with risk levels (`low`, `medium`, `high`, `critical`), reasons, and heatmap |
| 🔁 **Retention Analysis** | Active vs. inactive student tracking by program, faculty, and session trend |
| 📡 **Traffic Analysis** | Performance metrics per traffic source: impressions, clicks, leads, enrollment, and conversion rate |
| 💡 **Insights** | Auto-generated recommendations per analysis type (`funnel`, `attrition`, `retention`, `traffic`) |
| 📄 **Reports & Export** | Export analytics reports as PDF, XLSX, or CSV; full export history |
| 🕐 **Enrollment Queue** | View pending enrollments awaiting processing |

---

## 🛠 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13.7 |
| Language | PHP >= 8.3 |
| Database | MySQL >= 8.0 |
| Authentication | Laravel Sanctum 4.0 (Bearer Token, TTL 8h) |
| PDF Export | barryvdh/laravel-dompdf 3.1 |
| Excel/CSV Export | maatwebsite/excel 3.1 |
| Testing | PestPHP 4.7 |

---

## 📦 Requirements

- PHP >= 8.3
- Composer >= 2.x
- MySQL >= 8.0
- Node.js >= 18 & npm (for asset building)
- Laragon / XAMPP / any local web server

---

## 🚀 Installation & Setup

```bash
# 1. Clone the repository
git clone <repo-url>
cd api-post-graduate

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Copy environment file
cp .env.example .env

# 5. Generate application key
php artisan key:generate

# 6. Configure your .env file (see Environment Variables section below)

# 7. Run migrations and seeders
php artisan migrate --seed

# 8. Calculate initial analytics data
php artisan analytics:calculate

# 9. Build assets (optional)
npm run build

# 10. Start the development server
php artisan serve
```

> **Tip:** You can also run all setup steps at once with `composer setup`.

---

## ⚙️ Environment Variables

Configure the following variables in your `.env` file:

```env
# Application Identity
APP_NAME="Post Graduate Analytics API"
APP_ENV=local
APP_KEY=                          # Auto-filled after php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_post_graduate     # Your database name
DB_USERNAME=root                  # Your MySQL username
DB_PASSWORD=                      # Your MySQL password

# Session, Cache & Queue
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Filesystem — local storage for exported files
FILESYSTEM_DISK=local

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug
```

---

## 📡 API Documentation

**Base URL:** `http://localhost:8000/api`

All protected endpoints require the following header:

```
Authorization: Bearer <your-token>
```

---

### 🔐 Authentication

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `POST` | `/auth/login` | Login with email & password, returns Bearer token | No |
| `POST` | `/auth/logout` | Invalidate current token | Yes |
| `POST` | `/auth/refresh` | Refresh the active token | Yes |
| `GET` | `/auth/me` | Get authenticated user profile | Yes |

**Login Request Body:**
```json
{
  "email": "superadmin@admin.com",
  "password": "password"
}
```

**Login Response:**
```json
{
  "token": "1|abc123...",
  "token_type": "Bearer",
  "expires_in": 28800
}
```

---

### 📊 Dashboard

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `GET` | `/dashboard` | Comprehensive dashboard summary | Yes |
| `GET` | `/dashboard/sessions` | List all analysis sessions | Yes |
| `GET` | `/dashboard/trend` | Dashboard metrics trend over sessions | Yes |

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `session_id` | integer | Filter by specific analysis session ID |

---

### 🔽 Funnel Analysis

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `GET` | `/funnel` | Funnel metrics for a given session | Yes |
| `GET` | `/funnel/stage` | Detailed metrics per funnel stage | Yes |
| `GET` | `/funnel/comparison` | Cross-session funnel trend comparison | Yes |

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `session_id` | integer | Target analysis session (required for most) |
| `stage_id` | integer | Filter by specific funnel stage |

---

### 📉 Attrition Analysis

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `GET` | `/attrition` | Attrition metrics per stage with risk levels | Yes |
| `GET` | `/attrition/stage` | Dropout student detail per stage | Yes |
| `GET` | `/attrition/comparison` | Cross-session attrition trend | Yes |
| `GET` | `/attrition/reasons` | Aggregated dropout reasons by category | Yes |
| `GET` | `/attrition/heatmap` | Attrition heatmap data (stage × session) | Yes |

**Risk Levels:** `low` · `medium` · `high` · `critical`

**Dropout Reason Categories:** `academic` · `financial` · `personal` · `administrative`

---

### 🔁 Retention Analysis

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `GET` | `/retention` | Retention rate for a session | Yes |
| `GET` | `/retention/students` | Student list by retention status | Yes |
| `GET` | `/retention/comparison` | Cross-session retention trend | Yes |
| `GET` | `/retention/by-program` | Retention breakdown per study program | Yes |
| `GET` | `/retention/by-faculty` | Retention breakdown per faculty | Yes |
| `GET` | `/retention/trend` | Retention trend with change detection | Yes |

---

### 📡 Traffic Analysis

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `GET` | `/traffic` | Traffic source performance for a session | Yes |
| `GET` | `/traffic/by-category` | Performance grouped by traffic category | Yes |
| `GET` | `/traffic/source` | Trend detail for a specific traffic source | Yes |
| `GET` | `/traffic/sources` | List all available traffic sources | Yes |

**Traffic Categories:** `social_media` · `search` · `event` · `referral` · `direct`

**Metrics per source:** impressions, clicks, leads, enrollments, conversion rate

---

### 💡 Insights

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `GET` | `/insights` | Auto-generated insights for a session | Yes |
| `GET` | `/insights/type` | Insights filtered by type | Yes |
| `GET` | `/insights/all` | All insights across sessions (paginated) | Yes |

**Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `session_id` | integer | Filter by analysis session |
| `type` | string | One of: `funnel`, `attrition`, `retention`, `traffic` |

---

### 📄 Reports & Export

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `GET` | `/reports` | Export history list | Yes |
| `GET` | `/reports/export/pdf` | Generate and download PDF report | Yes |
| `GET` | `/reports/export/xlsx` | Generate and download Excel report | Yes |
| `GET` | `/reports/export/csv` | Generate and download CSV report | Yes |

**Export Query Parameters:**
| Parameter | Type | Description |
|---|---|---|
| `session_id` | integer | Session to export (required) |

---

### 🕐 Enrollments

| Method | Endpoint | Description | Auth Required |
|---|---|---|---|
| `GET` | `/enrollments/pending` | List enrollments pending processing | Yes |

---

## 🕐 Scheduled Commands

### Manual Execution

```bash
# Recalculate all analytics data for all sessions
php artisan analytics:calculate
```

This command processes all analysis sessions and recalculates:
- Funnel entry metrics
- Attrition analysis per stage
- Retention analysis
- Traffic performance metrics
- Auto-generated insights

### Schedule Configuration

Add the following to your server's crontab to enable Laravel's scheduler:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 Running Tests

```bash
# Run the full test suite
composer test

# Or directly with Artisan
php artisan test

# Run with coverage report
php artisan test --coverage

# Run a specific test file
php artisan test tests/Feature/AuthTest.php

# Run tests in parallel (faster)
php artisan test --parallel
```

Test suites cover all API endpoints with feature tests using PestPHP.

---

## 🗄 Database Schema

### Entity Relationship Overview

```
users ──────────────────────── analysis_sessions
                                       │
               ┌───────────────────────┼────────────────────────────┐
               │                       │                            │
          funnel_entries    attrition_analyses    retention_analyses
               │                       │
          funnel_stages            funnel_stages
               │
          enrollments ──── students ──── programs
               │
          dropoff_reasons

analysis_sessions ──── traffic_performances ──── traffic_sources

analysis_sessions ──── insights
analysis_sessions ──── reports
```

### Table Reference

| Table | Description |
|---|---|
| `users` | System users with roles: `super_admin`, `admin`, `viewer` |
| `programs` | S2 & S3 study programs per faculty |
| `students` | Student data with source and program references |
| `funnel_stages` | 9 enrollment pipeline stages |
| `enrollments` | Student journey per stage (junction table) |
| `dropoff_reasons` | 17 dropout reasons across 5 categories |
| `analysis_sessions` | Analysis periods (semester-based) |
| `funnel_entries` | Funnel metric snapshot per session per stage |
| `attrition_analyses` | Attrition metrics per session per stage |
| `retention_analyses` | Retention metrics per session |
| `traffic_sources` | 18 student recruitment sources |
| `traffic_performances` | Traffic performance metrics per session per source |
| `insights` | Auto-generated insights and recommendations |
| `reports` | History of exported report files |

---

## 🔑 Default Credentials

> Available after running `php artisan migrate --seed`

| Role | Email | Password |
|---|---|---|
| Super Admin | `superadmin@admin.com` | `password` |
| Admin | `admin@admin.com` | `password` |
| Viewer | `hendri@example.com` | `password` |

> ⚠️ **Security Notice:** Change all default passwords before deploying to production.

---

## 📁 Project Structure

```
api-post-graduate/
├── app/
│   ├── Console/Commands/
│   │   └── CalculateAnalytics.php        # Artisan analytics recalculation command
│   ├── Exports/
│   │   └── AnalyticsExport.php           # Multi-sheet Excel export handler
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── FunnelController.php
│   │   ├── AttritionController.php
│   │   ├── RetentionController.php
│   │   ├── TrafficController.php
│   │   ├── InsightController.php
│   │   ├── ReportController.php
│   │   └── EnrollmentController.php
│   └── Models/
│       ├── User.php
│       ├── AnalysisSession.php
│       ├── Program.php
│       ├── Student.php
│       ├── FunnelStage.php
│       ├── FunnelEntry.php
│       ├── Enrollment.php
│       ├── DropoffReason.php
│       ├── AttritionAnalysis.php
│       ├── RetentionAnalysis.php
│       ├── TrafficSource.php
│       ├── TrafficPerformance.php
│       ├── Insight.php
│       └── Report.php
├── database/
│   ├── migrations/                       # 17 migration files
│   ├── seeders/                          # 9 seeders (users, programs, students, etc.)
│   └── factories/                        # UserFactory, StudentFactory
├── routes/
│   ├── api.php                           # All API endpoints
│   └── web.php
├── resources/views/reports/             # PDF report templates (Blade)
├── storage/app/                         # Exported files stored here
├── tests/
│   ├── Feature/                         # Feature/endpoint tests
│   └── Unit/                            # Unit tests
├── .env.example
├── composer.json
└── package.json
```

---

## 📜 License

This project is open-sourced under the [MIT License](LICENSE).
