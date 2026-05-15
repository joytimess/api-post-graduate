# API Post-Graduate Analytics

REST API untuk sistem analitik penerimaan mahasiswa pascasarjana (S2/S3). Sistem ini melacak seluruh pipeline penerimaan dari awareness hingga aktif kuliah, menganalisis konversi funnel, attrisi mahasiswa, retensi, serta performa sumber traffic per periode analisis.

---

## Tech Stack

| Layer            | Teknologi                                     |
| ---------------- | --------------------------------------------- |
| Framework        | Laravel 13.7                                  |
| PHP              | >= 8.3                                        |
| Database         | MySQL                                         |
| Authentication   | Laravel Sanctum 4.0 (Bearer Token, TTL 8 jam) |
| Export PDF       | barryvdh/laravel-dompdf 3.1                   |
| Export Excel/CSV | maatwebsite/excel 3.1                         |
| Build Tool       | Vite 8.0 + Tailwind CSS 4.0                   |
| Testing          | PestPHP 4.7                                   |

---

## Requirements

- PHP >= 8.3
- Composer
- MySQL >= 8.0
- Node.js >= 18 & npm
- Laragon / XAMPP / web server lokal lainnya

---

## Installation

```bash
# 1. Clone repository
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

# 6. Konfigurasi .env (lihat bagian Konfigurasi .env di bawah)

# 7. Jalankan migrasi & seeder
php artisan migrate --seed

# 8. Hitung data analitik awal
php artisan analytics:calculate

# 9. Build assets (opsional)
npm run build

# 10. Jalankan server
php artisan serve
```

---

## Konfigurasi .env

Sesuaikan variabel berikut di file `.env`:

```env
# Identitas Aplikasi
APP_NAME="API Post-Graduate"
APP_ENV=local
APP_KEY=                        # Diisi otomatis setelah php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=api_post_graduate   # Sesuaikan nama database
DB_USERNAME=root                # Sesuaikan username MySQL
DB_PASSWORD=                    # Sesuaikan password MySQL

# Session, Cache, Queue — gunakan database untuk setup minimal
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Filesystem — local untuk penyimpanan file export
FILESYSTEM_DISK=local

# Logging
LOG_CHANNEL=stack
```

---

## Fitur Utama

### Autentikasi

- Login dengan email & password, menghasilkan Bearer token (TTL 8 jam)
- Refresh token dan logout
- Role pengguna: `super_admin`, `admin`, `viewer`

### Dashboard

- Ringkasan komprehensif: funnel, attrisi, retensi, insight, dan overview per sesi analisis

### Analisis Funnel

- Metrik konversi & dropoff per tahap dari 9 tahap penerimaan
- Detail mahasiswa per tahap
- Perbandingan tren antar sesi

### Analisis Attrisi

- Tingkat attrisi per tahap dengan level risiko (`low`, `medium`, `high`, `critical`)
- Detail mahasiswa dropout beserta alasan
- Agregasi alasan dropout per kategori (akademik, finansial, personal, administratif)
- Perbandingan tren attrisi antar sesi

### Analisis Retensi

- Tingkat retensi mahasiswa aktif vs. tidak aktif
- Breakdown per program studi
- Tren retensi antar sesi dengan deteksi perubahan

### Analisis Traffic

- Performa per sumber traffic: impressi, klik, leads, enrollment, conversion rate
- Pengelompokan per kategori (social media, search, event, referral, direct)
- Tren performa per sumber lintas sesi

### Insights

- Insights otomatis per tipe: `funnel`, `attrition`, `retention`, `traffic`
- Rekomendasi berdasarkan kondisi masing-masing sesi

### Export Laporan

- Export PDF, XLSX, dan CSV per sesi analisis
- Riwayat laporan yang pernah dieksport

### Artisan Command

```bash
php artisan analytics:calculate   # Hitung ulang semua data analitik
```

---

## Struktur Database / ERD

```
users ────────────────────── analysis_sessions
                                     │
              ┌──────────────────────┼──────────────────────────────┐
              │                      │                              │
         funnel_entries    attrition_analyses    retention_analyses
              │                      │
         funnel_stages           funnel_stages
              │
         enrollments ──── students ──── programs
              │
         dropoff_reasons

analysis_sessions ──── traffic_performances ──── traffic_sources

analysis_sessions ──── insights
analysis_sessions ──── reports
```

### Tabel Utama

| Tabel                  | Deskripsi                                       |
| ---------------------- | ----------------------------------------------- |
| `users`                | Pengguna sistem (super_admin / admin / viewer)  |
| `programs`             | Program studi S2 & S3 per fakultas              |
| `students`             | Data mahasiswa                                  |
| `funnel_stages`        | 9 tahap pipeline penerimaan                     |
| `enrollments`          | Perjalanan mahasiswa per tahap (junction table) |
| `dropoff_reasons`      | 17 alasan dropout dalam 5 kategori              |
| `analysis_sessions`    | Periode analisis (semester)                     |
| `funnel_entries`       | Snapshot metrik funnel per sesi per tahap       |
| `attrition_analyses`   | Metrik attrisi per sesi per tahap               |
| `retention_analyses`   | Metrik retensi per sesi                         |
| `traffic_sources`      | 18 sumber rekrutmen mahasiswa                   |
| `traffic_performances` | Metrik performa traffic per sesi per sumber     |
| `insights`             | Insight dan rekomendasi otomatis                |
| `reports`              | Riwayat file laporan yang diexport              |

---

## Struktur Folder

```
api-post-graduate/
├── app/
│   ├── Console/Commands/
│   │   └── CalculateAnalytics.php      # Artisan command kalkulasi analitik
│   ├── Exports/
│   │   └── AnalyticsExport.php         # Multi-sheet Excel export
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── FunnelController.php
│   │   ├── AttritionController.php
│   │   ├── RetentionController.php
│   │   ├── TrafficController.php
│   │   ├── InsightController.php
│   │   └── ReportController.php
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
│
├── database/
│   ├── migrations/                     # 17 file migrasi
│   ├── seeders/                        # 9 seeder (users, programs, students, dll.)
│   └── factories/                      # UserFactory, StudentFactory
│
├── routes/
│   ├── api.php                         # Semua endpoint API
│   └── web.php
│
├── resources/views/reports/            # Template PDF
├── storage/app/                        # File export tersimpan di sini
├── .env.example
├── composer.json
└── package.json
```

---

## API Endpoints

Semua endpoint selain login membutuhkan header:

```
Authorization: Bearer <token>
```

| Method | Endpoint                    | Deskripsi                   |
| ------ | --------------------------- | --------------------------- |
| POST   | `/api/auth/login`           | Login                       |
| POST   | `/api/auth/logout`          | Logout                      |
| POST   | `/api/auth/refresh`         | Refresh token               |
| GET    | `/api/auth/me`              | Profil user aktif           |
| GET    | `/api/dashboard`            | Ringkasan dashboard         |
| GET    | `/api/dashboard/sessions`   | Daftar sesi analisis        |
| GET    | `/api/funnel`               | Metrik funnel per sesi      |
| GET    | `/api/funnel/stage`         | Detail per tahap            |
| GET    | `/api/funnel/comparison`    | Tren antar sesi             |
| GET    | `/api/attrition`            | Metrik attrisi              |
| GET    | `/api/attrition/stage`      | Detail dropout per tahap    |
| GET    | `/api/attrition/comparison` | Tren attrisi                |
| GET    | `/api/attrition/reasons`    | Alasan dropout              |
| GET    | `/api/retention`            | Metrik retensi              |
| GET    | `/api/retention/students`   | Daftar mahasiswa per status |
| GET    | `/api/retention/comparison` | Tren retensi                |
| GET    | `/api/retention/by-program` | Retensi per program         |
| GET    | `/api/traffic`              | Performa traffic            |
| GET    | `/api/traffic/by-category`  | Traffic per kategori        |
| GET    | `/api/traffic/source`       | Tren per sumber             |
| GET    | `/api/traffic/sources`      | Daftar sumber traffic       |
| GET    | `/api/insights`             | Insights per sesi           |
| GET    | `/api/insights/type`        | Insights per tipe           |
| GET    | `/api/insights/all`         | Semua insights (paginated)  |
| GET    | `/api/reports`              | Riwayat laporan             |
| GET    | `/api/reports/export/pdf`   | Export PDF                  |
| GET    | `/api/reports/export/xlsx`  | Export Excel                |
| GET    | `/api/reports/export/csv`   | Export CSV                  |

---

## Default Credentials (Seeder)

| Role        | Email                | Password |
| ----------- | -------------------- | -------- |
| Super Admin | superadmin@admin.com | password |
| Admin       | admin@admin.com      | password |
| Viewer      | hendri@example.com   | password |
