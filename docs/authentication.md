# Autentikasi panel Hiring Agents

Panel menggunakan Filament 5 dan Livewire 4 dengan auth bawaan: login, logout, reset password, dan profil di `/admin`. Registrasi publik tidak diaktifkan.

## Setup

```bash
composer install
php artisan migrate
php artisan app:create-staff
```

Perintah interaktif meminta nama, email, role `admin`/`hr`, dan password beserta konfirmasinya. Setelah itu buka `/admin/login`. Tidak ada password default. Gunakan perintah ini untuk akun panel, bukan `make:filament-user`, karena akses memerlukan role dan status aktif.

Migration menambahkan `role` nullable dan `is_active` default false. Akun lama tidak otomatis memperoleh akses panel. `User::canAccessPanel()` membatasi akses pada admin/HR aktif, termasuk request dari sesi yang sudah login. Field role dan status aktif tidak mass-assignable lewat profil.

## Reset password

Gunakan tautan lupa password di halaman login. Konfigurasikan `APP_URL` agar URL reset mengarah ke host aplikasi yang benar. Dengan `MAIL_MAILER=log`, pesan reset masuk ke `storage/logs/laravel.log`; gunakan Mailpit/SMTP atau mail provider untuk pengiriman nyata. Notifikasi reset Filament menggunakan queue, sehingga worker perlu berjalan (`php artisan queue:work` atau `composer run dev`).

## Assets dan pemeriksaan

Assets bawaan Filament dipublikasikan oleh hook Composer `filament:upgrade`. Jika perlu, jalankan `php artisan filament:assets`. Panel memakai stylesheet Filament terpisah dari Tailwind website publik dan mendukung pilihan tema bawaannya.

```bash
php artisan test tests/Feature/PanelAuthTest.php
composer test
npm run build
```

Composer menggunakan platform PHP 8.2 untuk resolusi dependency agar lockfile tetap sesuai batas minimum aplikasi, meskipun development berjalan pada PHP yang lebih baru.
