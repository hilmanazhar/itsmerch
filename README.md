# My ITS Merch - Platform E-Commerce Merchandise ITS

Dokumentasi ini disusun sebagai laporan Final Project (FP) Pemrograman Web. Proyek ini adalah platform e-commerce yang memungkinkan pengguna membeli merchandise resmi ITS dengan fitur pembayaran digital dan perhitungan ongkos kirim otomatis.

---

## 📑 Daftar Isi
1. [Laporan Proyek](#1-laporan-proyek)
    - [Frontend & Backend Development](#11-frontend--backend-development)
    - [Database Implementation](#12-database-implementation)
    - [Integrasi API](#13-integrasi-api)
    - [Pengujian (Testing)](#14-pengujian-testing)
2. [Diagram Sistem](#2-diagram-sistem)
3. [User Guide](#3-user-guide)
4. [Pembagian Jobdesk](#4-pembagian-jobdesk)

---

## 1. Laporan Proyek

### 1.1 Frontend & Backend Development

**Frontend:**
Sisi antarmuka dibangun menggunakan pendekatan *Component-Based* menggunakan JavaScript Native (Vanilla JS) dan CSS Framework (Bootstrap) untuk memastikan responsivitas.
* **Struktur Halaman:** Menggunakan file HTML terpisah untuk setiap fitur utama seperti [`index.html`](src/index.html) (Beranda), [`catalog.html`](src/catalog.html) (Daftar Produk), [`product.html`](src/product.html) (Detail Produk), dan [`checkout.html`](src/checkout.html).
* **Komponen Dinamis:** Menggunakan JavaScript di [`src/js/app.js`](src/js/app.js) untuk merender elemen dinamis dengan pendekatan modular.
* **Interaktivitas:** Menggunakan AJAX/Fetch API untuk berkomunikasi dengan backend tanpa reload halaman penuh.

**Backend:**
Sisi server dibangun menggunakan **PHP Native** yang berfungsi sebagai REST API. Backend menangani logika bisnis, validasi data, dan komunikasi ke database.
* **API Endpoints:** Semua request dari frontend diproses melalui folder [`src/api/`](src/api/). Contohnya [`get_products.php`](src/api/get_products.php) untuk mengambil data produk dan [`checkout.php`](src/api/checkout.php) untuk memproses transaksi.
* **Autentikasi:** Menggunakan PHP Session dan validasi database untuk login ([`login.php`](src/api/login.php)) dan registrasi ([`register.php`](src/api/register.php)).

### 1.2 Database Implementation

Proyek ini menggunakan database relasional **MySQL/MariaDB**. Struktur database dirancang untuk menangani integritas data transaksi e-commerce.

* **File Konfigurasi:** Koneksi database diatur dalam file [`src/api/db.php`](src/api/db.php) yang mendukung konfigurasi environment (local maupun hosting seperti Railway/InfinityFree).
* **Skema Database:** Berdasarkan file SQL yang disertakan ([`its_merchandise (3).sql`](its_merchandise%20(3).sql)), tabel utama meliputi:
    * `users`: Menyimpan data pengguna dan peran (admin/user).
    * `products`: Menyimpan detail merchandise, harga, stok, dan gambar.
    * `carts`: Menyimpan item keranjang belanja sementara per user.
    * `orders` & `order_details`: Menyimpan riwayat transaksi dan status pembayaran.
    * `user_addresses`: Menyimpan alamat pengiriman untuk perhitungan ongkir.
    * `coupons`: Fitur potongan harga.

### 1.3 Integrasi API

Sistem ini terintegrasi dengan dua layanan eksternal utama untuk mensimulasikan proses e-commerce yang nyata:

1.  **RajaOngkir API (Starter):**
    * Digunakan untuk mengambil data Provinsi dan Kota di Indonesia secara *real-time*.
    * Menghitung biaya ongkos kirim berdasarkan kurir (JNE, POS, TIKI) dari lokasi toko ke alamat pembeli.
    * Implementasi terdapat pada [`rajaongkir_config.php`](src/api/rajaongkir_config.php) dan [`get_shipping_cost.php`](src/api/get_shipping_cost.php).

2.  **Midtrans Payment Gateway (Snap API):**
    * Digunakan untuk memproses pembayaran non-tunai (QRIS, Virtual Account, E-Wallet).
    * Backend menghasilkan `Snap Token` saat checkout, yang kemudian membuka popup pembayaran di frontend.
    * Status pembayaran diperbarui otomatis melalui mekanisme Webhook/Notification Handler ([`midtrans_notification.php`](src/api/midtrans_notification.php)).
    * Implementasi terdapat pada [`midtrans_config.php`](src/api/midtrans_config.php) dan logika checkout.

### 1.4 Pengujian (Testing)

Pengujian dilakukan untuk memastikan fungsionalitas sistem berjalan dengan baik:
* **Unit Testing (Koneksi):** Menggunakan script [`test_db.php`](test_db.php) untuk memverifikasi koneksi database berhasil sebelum deployment.
* **Functional Testing:**
    * *Alur Pembelian:* User Login -> Pilih Produk -> Tambah ke Keranjang -> Checkout (Pilih Alamat & Kurir) -> Bayar via Midtrans (Sandbox).
    * *Validasi Stok:* Memastikan stok berkurang setelah pesanan dibuat.
    * *Admin Panel:* Memastikan admin bisa menambah produk dan memantau order masuk ([`admin.html`](src/admin.html)).

---

## 2. Diagram Sistem

Berikut adalah gambaran umum arsitektur sistem "My ITS Merch":

* **Client Side:** HTML5, CSS3, JavaScript (Fetch API).
* **Server Side:** PHP (API Logic), Composer (Dependency Manager).
* **Database:** MySQL.
* **3rd Party Services:** RajaOngkir (Logistik), Midtrans (Payment).

*(Silakan merujuk pada file [`DIAGRAMS.md`](DIAGRAMS.md) yang disertakan dalam repository ini untuk diagram alur yang lebih rinci, seperti Entity Relationship Diagram atau Flowchart Transaksi)*.

---

## 3. User Guide

### Instalasi & Konfigurasi (Lokal/Hosting)

1.  **Database:**
    * Buat database baru di MySQL (misal: `its_merch`).
    * Import file [`its_merchandise (3).sql`](its_merchandise%20(3).sql) ke dalam database tersebut.
2.  **Konfigurasi API:**
    * Buka [`src/api/db.php`](src/api/db.php) dan sesuaikan kredensial database (`$host`, `$user`, `$pass`, `$db`).
    * Buka [`src/api/rajaongkir_config.php`](src/api/rajaongkir_config.php) dan masukkan API Key RajaOngkir Anda.
    * Buka [`src/api/midtrans_config.php`](src/api/midtrans_config.php) dan masukkan Server Key & Client Key dari Midtrans Sandbox Anda.
3.  **Menjalankan Proyek:**
    * Pastikan server lokal (Apache/Nginx/XAMPP) berjalan.
    * Akses `http://localhost/its-merch-bootstrap/src/index.html` di browser.

### Cara Penggunaan (Pembeli)
1.  **Registrasi/Login:** Buat akun baru atau masuk untuk mulai berbelanja.
2.  **Belanja:** Pilih produk di Katalog, atur varian (jika ada), dan masukkan ke keranjang.
3.  **Checkout:** Buka keranjang, klik checkout. Isi alamat pengiriman lengkap untuk mendapatkan opsi ongkir.
4.  **Pembayaran:** Pilih kurir pengiriman, lalu klik "Bayar". Selesaikan pembayaran di jendela Midtrans.
5.  **Cek Status:** Lihat status pesanan di menu Profil -> Riwayat Pesanan ([`orders.html`](src/orders.html)).

---

## 4. Pembagian Jobdesk

| Nama Anggota | NRP | Deskripsi Tugas |
| :--- | :--- | :--- |
| **Muhammad Hilman Azhar** | 5025241264 |Frontend |
| **A. Wildan Kevin Assyauqi** | 5025241265 |Database, UX |
| **Imam Baidhawi** | 5025241266 |Backend |

---
*© 2025 My ITS Merch - Final Project Pemrograman Web*
