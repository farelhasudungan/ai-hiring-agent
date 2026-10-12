# Recruiting and Hiring Agents

Website rekrutmen berbasis **Laravel 12, Filament 5, dan Tailwind CSS**. Saat ini tersedia landing page, demo kandidat, halaman tim, serta autentikasi panel admin/HR. Fitur rekrutmen dan analisis AI dikembangkan bertahap.

## Persyaratan

- PHP 8.2+ beserta extension yang dibutuhkan Composer, termasuk SQLite (`pdo_sqlite`).
- Composer 2.
- Node.js 24 LTS (24.15+) dan npm yang kompatibel.
- Git.

## Instalasi

### 1. Clone repository

Ganti `<URL_REPOSITORY>` dengan URL repository ini.

```bash
git clone <URL_REPOSITORY> hiring-agents
cd hiring-agents
```

### 2. Pasang dependency dan siapkan environment

```bash
composer install
composer check-platform-reqs
npm ci
php -r "file_exists('.env') || copy('.env.example', '.env');"
php artisan key:generate
```

Langkah generate key hanya untuk instalasi baru. Jika memakai `.env` aplikasi yang sudah berjalan, pertahankan `APP_KEY` yang ada.

Atur `APP_URL=http://127.0.0.1:8000` pada `.env`. Konfigurasi default menggunakan SQLite untuk database, session, cache, dan queue.

### 3. Siapkan database dan akun panel

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan app:create-staff
```

Ikuti pertanyaan untuk mengisi nama, email, role `admin`/`hr`, dan password. Tidak ada akun atau password default.

Alternatif: isi kredensial `SEED_ADMIN_*` dan/atau `SEED_HR_*` pada `.env`, lalu jalankan `php artisan db:seed`. Lihat [panduan seeder akun](docs/authentication.md#seeder-akun).

### 4. Jalankan aplikasi

```bash
composer run dev
```

Perintah ini menjalankan server Laravel, queue listener, log viewer, dan Vite sekaligus.

- Website: [http://127.0.0.1:8000](http://127.0.0.1:8000)
- Login admin/HR: [http://127.0.0.1:8000/admin/login](http://127.0.0.1:8000/admin/login)

Reset password memerlukan queue worker dan konfigurasi email. Secara default, email dicatat di `storage/logs/laravel.log` melalui `MAIL_MAILER=log`.

## Pemeriksaan

```bash
composer test
npm run build
```

## Dokumentasi

Panduan project tersedia melalui folder **[docs/](docs/README.md)**, termasuk [autentikasi dan pengelolaan akun panel](docs/authentication.md). Indeks dokumentasi juga memuat tautan PRD dan panduan desain.
