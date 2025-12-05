<div align="center">
    <img src="public/assets/icon/favicon.png" alt="Logo BKPSDM Karawang" width="100" height="auto" style="margin-right: 20px;"/>
    <img src="public/assets/image/logo-besar.png" alt="Logo SIPRES" width="150" height="auto" style="margin-left: 20px;"/>
</div>

<h1 align="center">SIPRES</h1>
<p align="center">
    <strong>Sistem Presensi Acara Berbasis QR Code</strong><br>
    <em>Dikembangkan untuk BKPSDM Kabupaten Karawang</em>
</p>

<p align="center">
    <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel"></a>
    <a href="https://getbootstrap.com"><img src="https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap"></a>
    <a href="https://www.mysql.com/"><img src="https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL"></a>
</p>

---

## 📖 Tentang SIPRES

**SIPRES (Sistem Presensi)** adalah sebuah inovasi digital yang dirancang untuk mempermudah pengelolaan kehadiran dalam kegiatan, rapat, atau acara kedinasan di lingkungan **BKPSDM Kabupaten Karawang**.

Sistem ini menggantikan metode absensi konvensional (tanda tangan basah) dengan teknologi pemindaian **QR Code/Barcode**. Dengan SIPRES, proses registrasi kehadiran menjadi lebih cepat, akurat, dan transparan. Data kehadiran terekam secara *real-time*, meminimalkan antrean, serta memudahkan panitia dalam mencetak laporan dan sertifikat.

## 🚀 Fitur Unggulan

SIPRES dilengkapi dengan berbagai fitur untuk memudahkan administrator dan peserta:

-   **QR Code Attendance:** Absensi instan hanya dengan memindai kode QR unik milik peserta.
-   **Manajemen Acara & Peserta:** Kelola jadwal acara, lokasi, dan daftar peserta dengan mudah.
-   **Integrasi Data Pegawai:** Sinkronisasi otomatis data peserta dengan master data pegawai (NIP, Nama, SKPD).
-   **Kirim QR Massal:** Fitur pengiriman QR Code kehadiran melalui Email atau WhatsApp.
-   **Cetak ID Card Otomatis:** Pembuatan ID Card peserta yang dilengkapi QR Code siap cetak (PDF).
-   **Riwayat & Draft:** Melacak perubahan data peserta (seperti perbaikan nama) dengan log riwayat yang detail.
-   **Laporan Real-time:** Dashboard statistik kehadiran berdasarkan SKPD dan jumlah total peserta.
-   **Import Data CSV:** Kemudahan input data peserta dalam jumlah banyak sekaligus.

## 🛠️ Teknologi yang Digunakan

-   **Backend:** Laravel Framework
-   **Database:** MySQL
-   **Frontend:** Blade Templates, JavaScript (Vanilla ES6), Bootstrap
-   **Tools Tambahan:**
    -   `barryvdh/laravel-dompdf` (Cetak PDF)
    -   `simplesoftwareio/simple-qrcode` (Generate QR)

## 📦 Instalasi & Penggunaan

Ikuti langkah berikut untuk menjalankan proyek ini di mesin lokal Anda:

1.  **Clone Repositori**
    ```bash
    git clone [https://github.com/username/sipres.git](https://github.com/username/sipres.git)
    cd sipres
    ```

2.  **Instal Dependensi**
    ```bash
    composer install
    npm install && npm run build
    ```

3.  **Konfigurasi Environment**
    Salin file `.env.example` menjadi `.env` dan sesuaikan pengaturan database Anda.
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

4.  **Migrasi Database**
    ```bash
    php artisan migrate
    ```

5.  **Jalankan Aplikasi**
    ```bash
    php artisan serve
    ```
    Akses aplikasi melalui `http://localhost:8000`.

## 🔒 Keamanan

Jika Anda menemukan celah keamanan dalam aplikasi ini, harap segera laporkan kepada pengembang melalui email atau issue tracker di repositori ini.

## 📄 Lisensi

SIPRES adalah perangkat lunak *open-source* yang dilisensikan di bawah [MIT license](https://opensource.org/licenses/MIT).

---

<p align="center">
    &copy; 2025 BKPSDM Kabupaten Karawang. Created by <strong>Rendy Suwandi</strong>.
</p>