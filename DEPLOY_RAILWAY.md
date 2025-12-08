# 🚀 Panduan Deploy ke Railway

## Langkah 1: Persiapan

### 1.1 Rename Config Files
Sebelum deploy, rename file sample menjadi file aktif:

```bash
# Di Railway, ini akan dilakukan via environment variables
# File sample sudah mendukung env vars
```

### 1.2 Push ke GitHub
Pastikan semua perubahan sudah di-push:
```bash
git add .
git commit -m "Prepare for Railway deployment"
git push
```

---

## Langkah 2: Setup Railway

### 2.1 Buat Akun Railway
1. Buka [railway.app](https://railway.app)
2. Klik **"Login"** atau **"Sign Up"**
3. Login dengan akun GitHub

### 2.2 Buat Project Baru
1. Klik **"New Project"**
2. Pilih **"Deploy from GitHub repo"**
3. Pilih repository **`itsmerch-`**
4. Klik **"Deploy Now"**

---

## Langkah 3: Tambahkan MySQL Database

### 3.1 Add MySQL Service
1. Di project Railway, klik **"+ New"**
2. Pilih **"Database"** → **"MySQL"**
3. Railway akan membuat instance MySQL

### 3.2 Konfigurasi Database
Railway otomatis menyediakan environment variables:
- `MYSQLHOST`
- `MYSQLUSER`
- `MYSQLPASSWORD`
- `MYSQLDATABASE`
- `MYSQLPORT`

### 3.3 Import Schema
1. Klik service MySQL
2. Pilih tab **"Data"**
3. Buka **"Query"** atau gunakan connection string
4. Import `complete_database_reset.sql`

---

## Langkah 4: Set Environment Variables

### 4.1 Buka Settings Service PHP
1. Klik service aplikasi PHP
2. Buka tab **"Variables"**

### 4.2 Tambahkan Variables
Tambahkan environment variables berikut:

```env
# Document Root untuk Railpack
RAILPACK_PHP_ROOT_DIR=/app/src

# Midtrans (get your keys from https://dashboard.midtrans.com)
MIDTRANS_MERCHANT_ID=YOUR_MERCHANT_ID
MIDTRANS_CLIENT_KEY=YOUR_CLIENT_KEY
MIDTRANS_SERVER_KEY=YOUR_SERVER_KEY
MIDTRANS_IS_PRODUCTION=false

# RajaOngkir (get your key from https://rajaongkir.com)
RAJAONGKIR_API_KEY=YOUR_API_KEY

# Optional: Application URL (untuk callbacks)
APP_URL=https://your-app.railway.app
```

> ⚠️ **Catatan:** Ganti dengan production keys Anda saat go-live!

---

## Langkah 5: Link Database ke App

### 5.1 Reference Database Variables
1. Di service PHP, buka **"Variables"**
2. Klik **"+ Add Variable Reference"**
3. Pilih MySQL service
4. Reference semua MySQL variables

Railway akan auto-link:
- `MYSQLHOST` → from MySQL
- `MYSQLUSER` → from MySQL
- `MYSQLPASSWORD` → from MySQL
- `MYSQLDATABASE` → from MySQL
- `MYSQLPORT` → from MySQL

---

## Langkah 6: Deploy

### 6.1 Trigger Deploy
1. Railway auto-deploy saat push ke GitHub
2. Atau klik **"Deploy"** manual

### 6.2 Monitor Logs
1. Klik service PHP
2. Buka tab **"Logs"**
3. Pastikan tidak ada error

### 6.3 Akses Aplikasi
1. Buka tab **"Settings"**
2. Di bagian **"Domains"**, klik **"Generate Domain"**
3. Akses URL yang diberikan

---

## Troubleshooting

### ❌ Error: Database connection failed
- Pastikan MySQL service sudah running
- Pastikan environment variables ter-link dengan benar
- Cek logs MySQL service

### ❌ Error: 404 Not Found
- Pastikan `RAILPACK_PHP_ROOT_DIR=/app/src`
- Cek Caddyfile sudah benar

### ❌ Error: PHP extensions missing
- Extensions sudah didefinisikan di `composer.json`
- Railway akan auto-install

### ❌ Error: Midtrans/RajaOngkir failed
- Pastikan environment variables Midtrans/RajaOngkir sudah diset
- Pastikan API keys valid

---

## Struktur Environment Variables

| Variable | Deskripsi | Contoh |
|----------|-----------|--------|
| `RAILPACK_PHP_ROOT_DIR` | Document root | `/app/src` |
| `MYSQLHOST` | Database host | `containers-xxx.railway.app` |
| `MYSQLUSER` | Database user | `root` |
| `MYSQLPASSWORD` | Database password | `xxx` |
| `MYSQLDATABASE` | Database name | `railway` |
| `MYSQLPORT` | Database port | `3306` |
| `MIDTRANS_SERVER_KEY` | Midtrans Server Key | `Mid-server-xxx` |
| `MIDTRANS_CLIENT_KEY` | Midtrans Client Key | `Mid-client-xxx` |
| `MIDTRANS_IS_PRODUCTION` | Production mode | `false` |
| `RAJAONGKIR_API_KEY` | RajaOngkir API Key | `xxx` |

---

## Checklist Deployment

- [ ] Push semua kode ke GitHub
- [ ] Buat project di Railway
- [ ] Deploy dari GitHub repo
- [ ] Tambahkan MySQL database
- [ ] Import schema database
- [ ] Set semua environment variables
- [ ] Link MySQL variables ke app
- [ ] Generate domain
- [ ] Test semua fitur

---

**Selamat! Aplikasi Anda sudah live di Railway! 🎉**
