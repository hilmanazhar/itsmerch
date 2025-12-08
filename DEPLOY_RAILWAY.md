# 🚀 Panduan Deploy myITS Merchandise ke Railway

Dokumentasi lengkap untuk men-deploy aplikasi myITS Merchandise dari GitHub ke Railway.

---

## 📋 Daftar Isi

1. [Persiapan](#persiapan)
2. [Langkah 1: Buat Akun Railway](#langkah-1-buat-akun-railway)
3. [Langkah 2: Deploy dari GitHub](#langkah-2-deploy-dari-github)
4. [Langkah 3: Tambahkan MySQL Database](#langkah-3-tambahkan-mysql-database)
5. [Langkah 4: Import Database Schema](#langkah-4-import-database-schema)
6. [Langkah 5: Konfigurasi Environment Variables](#langkah-5-konfigurasi-environment-variables)
7. [Langkah 6: Generate Domain](#langkah-6-generate-domain)
8. [Troubleshooting](#troubleshooting)
9. [Kredensial Default](#kredensial-default)

---

## Persiapan

### Yang Diperlukan:
- ✅ Akun GitHub dengan repository `itsmerch-`
- ✅ Akun Railway (gratis, bisa login dengan GitHub)
- ✅ File `its_merchandise (3).sql` untuk import database

### Repository GitHub:
```
https://github.com/hilmanazhar/itsmerch-
```

---

## Langkah 1: Buat Akun Railway

1. Buka [railway.app](https://railway.app)
2. Klik **"Login"** atau **"Start a New Project"**
3. Pilih **"Login with GitHub"**
4. Authorize Railway untuk mengakses GitHub Anda

---

## Langkah 2: Deploy dari GitHub

### 2.1 Buat Project Baru
1. Di dashboard Railway, klik **"+ New Project"**
2. Pilih **"Deploy from GitHub repo"**
3. Cari dan pilih repository **`itsmerch-`**
4. Klik **"Deploy Now"**

### 2.2 Tunggu Build
- Railway akan otomatis mendeteksi ini adalah aplikasi PHP
- Proses build memakan waktu sekitar 2-5 menit
- Pantau progress di tab **"Deployments"**

---

## Langkah 3: Tambahkan MySQL Database

### 3.1 Buat MySQL Service
1. Di project Anda, klik **"+ New"**
2. Pilih **"Database"**
3. Pilih **"MySQL"**
4. Tunggu MySQL service online (status: **Online**)

### 3.2 Catat Kredensial MySQL
Buka MySQL service → Tab **"Variables"**, catat:
- `MYSQLHOST`
- `MYSQLPORT`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQLDATABASE` (biasanya `railway`)

**Untuk Public Access** (digunakan untuk import database):
Buka MySQL service → Tab **"Connect"** → **"Public Network"**

Catat:
- **Host:** `switchback.proxy.rlwy.net` (contoh)
- **Port:** `53877` (contoh)
- **Password:** lihat di Variables

---

## Langkah 4: Import Database Schema

### Opsi A: Via PHP Script (Direkomendasikan)

1. Buat file `import_railway.php` di local dengan isi:

```php
<?php
$host = 'PUBLIC_HOST_DARI_RAILWAY';  // contoh: switchback.proxy.rlwy.net
$port = PUBLIC_PORT;                  // contoh: 53877
$user = 'root';
$pass = 'PASSWORD_DARI_RAILWAY';
$db   = 'railway';

$mysqli = new mysqli($host, $user, $pass, $db, $port);
$mysqli->set_charset('utf8mb4');

$sql = file_get_contents('its_merchandise (3).sql');
$mysqli->multi_query($sql);

do {
    if ($result = $mysqli->store_result()) $result->free();
} while ($mysqli->next_result());

echo "✅ Import selesai!";
$mysqli->close();
?>
```

2. Jalankan di terminal:
```bash
php import_railway.php
```

### Opsi B: Via phpMyAdmin/MySQL Client

1. Gunakan kredensial **Public Network** dari Railway
2. Connect menggunakan MySQL client favorit Anda
3. Import file `its_merchandise (3).sql`

> ⚠️ **Penting:** Pastikan Anda mengimport ke database `railway`, bukan membuat database baru.

---

## Langkah 5: Konfigurasi Environment Variables

### 5.1 Buka Variables di Service PHP

1. Klik service **itsmerch-** (aplikasi PHP)
2. Buka tab **"Variables"**

### 5.2 Tambahkan Variables Berikut

#### Database (Internal Connection - Wajib)
| Variable | Value |
|----------|-------|
| `MYSQLHOST` | `mysql.railway.internal` |
| `MYSQLPORT` | `3306` |
| `MYSQLUSER` | `root` |
| `MYSQLPASSWORD` | `${{ MySQL.MYSQL_ROOT_PASSWORD }}` |
| `MYSQLDATABASE` | `railway` |

> **Catatan:** `mysql.railway.internal` adalah hostname internal untuk koneksi antar service di Railway. Port internal adalah `3306`, BUKAN port public.

#### PHP Extensions (Wajib)
| Variable | Value |
|----------|-------|
| `RAILPACK_PHP_EXTENSIONS` | `pdo_mysql,mysqli` |
| `RAILPACK_PHP_ROOT_DIR` | `/app/src` |

#### Midtrans - Payment Gateway
| Variable | Value |
|----------|-------|
| `MIDTRANS_MERCHANT_ID` | `YOUR_MERCHANT_ID` |
| `MIDTRANS_CLIENT_KEY` | `YOUR_CLIENT_KEY` |
| `MIDTRANS_SERVER_KEY` | `YOUR_SERVER_KEY` |
| `MIDTRANS_IS_PRODUCTION` | `false` |

> Dapatkan kredensial Midtrans di [dashboard.midtrans.com](https://dashboard.midtrans.com)

#### RajaOngkir - Shipping API
| Variable | Value |
|----------|-------|
| `RAJAONGKIR_API_KEY` | `YOUR_API_KEY` |

> Dapatkan API key di [rajaongkir.com](https://rajaongkir.com)

### 5.3 Menggunakan Reference Variables (Alternatif)

Untuk auto-link dengan MySQL service, gunakan syntax reference:

```
MYSQLHOST=${{ MySQL.RAILWAY_PRIVATE_DOMAIN }}
MYSQLPASSWORD=${{ MySQL.MYSQL_ROOT_PASSWORD }}
```

---

## Langkah 6: Generate Domain

### 6.1 Buat Public URL

1. Klik service **itsmerch-**
2. Buka tab **"Settings"**
3. Scroll ke **"Public Networking"**
4. Klik **"Generate Domain"**
5. Pilih port **8080**

### 6.2 Akses Aplikasi

Railway akan memberikan domain seperti:
```
https://itsmerch-production.up.railway.app
```

Buka URL tersebut di browser untuk mengakses aplikasi!

---

## Troubleshooting

### ❌ Error: "could not find driver"

**Penyebab:** PHP MySQL extension tidak terinstall.

**Solusi:** Tambahkan environment variable:
```
RAILPACK_PHP_EXTENSIONS=pdo_mysql,mysqli
```

### ❌ Error: "Database connection failed"

**Penyebab:** Kredensial database salah atau port salah.

**Solusi:**
- Untuk internal: gunakan `MYSQLHOST=mysql.railway.internal` dan `MYSQLPORT=3306`
- Untuk public: gunakan host dan port dari tab "Connect" → "Public Network"

### ❌ Error: Status 500

**Penyebab:** Ada error PHP di backend.

**Solusi:**
1. Cek tab **"Logs"** di service PHP
2. Cari error message dan perbaiki

### ❌ Produk tidak muncul

**Penyebab:** Database kosong atau koneksi gagal.

**Solusi:**
1. Pastikan database sudah di-import
2. Cek tab **"Database"** di MySQL service - harus ada tabel-tabel
3. Verifikasi environment variables sudah benar

### ❌ "Caddyfile input is not formatted"

**Ini hanya warning**, bukan error. Aplikasi tetap berjalan.

---

## Kredensial Default

Setelah deploy berhasil, gunakan kredensial berikut untuk login:

### Admin
| | |
|----------|------|
| **Email** | `admin@its.ac.id` |
| **Password** | `password` |

### User Test
| | |
|----------|------|
| **Email** | `hilman@its.ac.id` |
| **Password** | `password` |

---

## Struktur Environment Variables Lengkap

```env
# Database (Internal)
MYSQLHOST=mysql.railway.internal
MYSQLPORT=3306
MYSQLUSER=root
MYSQLPASSWORD=your_password_here
MYSQLDATABASE=railway

# PHP Config
RAILPACK_PHP_EXTENSIONS=pdo_mysql,mysqli
RAILPACK_PHP_ROOT_DIR=/app/src

# Midtrans
MIDTRANS_MERCHANT_ID=G324231142
MIDTRANS_CLIENT_KEY=Mid-client-xxxxx
MIDTRANS_SERVER_KEY=Mid-server-xxxxx
MIDTRANS_IS_PRODUCTION=false

# RajaOngkir
RAJAONGKIR_API_KEY=your_api_key_here
```

---

## Checklist Deployment

- [ ] Buat akun Railway dan login dengan GitHub
- [ ] Deploy repository `itsmerch-` dari GitHub
- [ ] Tambahkan MySQL database service
- [ ] Import `its_merchandise (3).sql` ke database `railway`
- [ ] Set semua environment variables
- [ ] Tambahkan `RAILPACK_PHP_EXTENSIONS=pdo_mysql,mysqli`
- [ ] Set `RAILPACK_PHP_ROOT_DIR=/app/src`
- [ ] Generate public domain
- [ ] Test login dan fitur-fitur

---

## Tips

1. **Free Tier:** Railway memberikan $5 credit gratis per bulan
2. **Auto Deploy:** Setiap push ke GitHub akan trigger auto-deploy
3. **Logs:** Selalu cek Logs jika ada masalah
4. **Internal vs Public:** Gunakan internal connection untuk performa lebih baik

---

**Selamat! Aplikasi Anda sudah live di Railway! 🎉**

Jika ada pertanyaan, hubungi developer atau buka issue di GitHub repository.
