# Sistem Pengiriman Galon Air - Depot Al Azzahra

![PHP](https://img.shields.io/badge/PHP-7.x-blue) ![MySQL](https://img.shields.io/badge/MySQL-5.7+-green) ![License](https://img.shields.io/badge/License-MIT-lightgrey)

Sistem web untuk pemesanan dan pengiriman galon air dari **Depot Al Azzahra**. Mendukung dashboard **Admin** dan **User**, autentikasi, manajemen pesanan, produk galon, metode pembayaran (transfer & COD), serta integrasi Google Maps.

---

## 🔹 Fitur Utama

**Admin**
- Kelola pesanan: verifikasi, ubah status, hapus.
- Kelola user dan metode pembayaran.
- Kelola produk galon: tambah, edit, hapus.
- Laporan pesanan: export ke PDF/Excel.
- Statistik pesanan dan pendapatan.
- Filter pesanan & peta lokasi pengiriman.

**User**
- Lihat daftar galon dengan harga, stok, dan tombol pesan.
- Riwayat pesanan dan status.
- Informasi depot di navbar.
- Peta lokasi pengiriman (Google Maps).

**Tambahan**
- Upload foto galon dan bukti pembayaran.
- Notifikasi sukses/error.
- Desain mobile-friendly, kompatibel Cordova.

---

## 🛠️ Teknologi
- **Backend:** PHP 7.x
- **Database:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript (vanilla)
- **Session:** PHP Sessions (cookie untuk Cordova)
- **Peta:** Google Maps iframe
- **Server:** Apache/Nginx
- **Keamanan:** Prepared statements, validasi input

---

## ⚙️ Instalasi Cepat

```bash
git clone https://github.com/username/dprd-rokan-hulu.git
cd dprd-rokan-hulu
```

1. Buat database `dprd_rokan_hulu` dan tabel sesuai file SQL.
2. Edit `koneksi.php` sesuai konfigurasi lokal.
3. Upload file ke server (buat folder `uploads/` writable).
4. Akses aplikasi: `http://localhost/dprd-rokan-hulu/index.php`

---

## 📂 Struktur Folder

```
dprd-rokan-hulu/
├── index.php
├── koneksi.php
├── dashboard_admin.php
├── dashboard.php
├── pesan-galon.php
├── riwayat-pesanan.php
├── lokasi-depot.php
├── laporan_export.php
├── uploads/
│   ├── galon/
│   └── ...
├── vendor/
└── README.md
```

---

## 🤝 Kontribusi
Fork repository, buat branch baru, dan ajukan pull request. Pastikan kode aman dan responsif.

---

## 📄 Lisensi
MIT License – lihat `LICENSE` untuk detail.

---

## ✉️ Kontak
Email: [support@depot-al-azzahra.site](mailto:support@depot-al-azzahra.site)

---

**Demo aplikasi**: [Klik di sini](http://depot-al-azzahra.site)
