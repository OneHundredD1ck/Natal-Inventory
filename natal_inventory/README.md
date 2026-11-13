# NATAL INVENTORY — Tahap 1

Tahap ini menyediakan koneksi PDO, autentikasi sesi, pembuatan satu admin awal, logout, token CSRF, serta pencatatan login/logout.

## Instalasi XAMPP

1. Impor `D:\natal_inventory.sql` melalui phpMyAdmin. Dump tersebut menyebut database `natal inventory`; bila database Anda bernama `natal_inventory`, ubah `DB_NAME` di `config/database.php`.
2. Salin folder `natal-inventory` ke `C:\xampp\htdocs\`.
3. Jalankan Apache dan MySQL dari XAMPP.
4. Buka `http://localhost/natal-inventory/create_admin.php` untuk membuat satu admin pertama, lalu hapus atau batasi akses file itu.
5. Masuk dari `http://localhost/natal-inventory/auth/login.php` dan uji logout.

## Fungsi file

- `config/database.php`: koneksi PDO tunggal dan konfigurasi database.
- `includes/auth_check.php`: sesi aman, CSRF, penjaga halaman, activity log.
- `auth/login.php`, `auth/logout.php`: autentikasi dan keluar.
- `create_admin.php`: setup terbatas ketika tabel `users` masih kosong.
- `index.php`: halaman internal terlindungi untuk verifikasi Tahap 1.

Catatan: dump yang diberikan belum mendefinisikan primary key/auto increment/foreign key. Aplikasi tidak mengubah struktur tersebut. Pastikan skema database yang diimpor memang menyediakan ID pengguna yang valid sebelum pengujian penuh.